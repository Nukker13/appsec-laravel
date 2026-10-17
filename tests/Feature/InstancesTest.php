<?php

namespace Tests\Feature;

use App\Contracts\LabRunner;
use App\Jobs\LaunchLab;
use App\Models\User;
use App\Services\FlagService;
use App\Services\InstanceRegistry;
use App\Testing\FakeLabRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Support\SpyLabRunner;
use Tests\TestCase;

/** Phase 6: one instance, one flag and one lifecycle per user and lab. */
class InstancesTest extends TestCase
{
    use RefreshDatabase;

    private FakeLabRunner $runner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLabs();
        $this->runner = $this->fakeLabRunner();
    }

    public function test_two_users_get_separate_instances_and_flags(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $lab = $this->lab('osci');
        $flags = app(FlagService::class);

        $this->launch($alice, $lab)->assertStatus(202);
        $this->launch($bob, $lab)->assertStatus(202);

        $aliceProject = $this->projectFor($alice, $lab);
        $bobProject = $this->projectFor($bob, $lab);
        $this->assertNotSame($aliceProject, $bobProject);
        $this->assertSame(1, $this->runner->startedCount($aliceProject));
        $this->assertSame(1, $this->runner->startedCount($bobProject));
        $this->assertNotSame($flags->flagFile($aliceProject, $lab), $flags->flagFile($bobProject, $lab));

        $aliceFlag = $this->flagFor($alice, $lab);
        $bobFlag = $this->flagFor($bob, $lab);
        $this->assertNotSame($aliceFlag, $bobFlag, 'Two users received the same flag.');

        // Bob cannot use Alice's flag.
        $this->submitFlag($bob, $lab, $aliceFlag)->assertOk()->assertExactJson(['correct' => false]);
        $this->assertSame('started', $bob->labs()->where('labs.id', $lab->id)->firstOrFail()->pivot->status);

        // Alice solving the lab stops Alice's instance only.
        $this->submitFlag($alice, $lab, $aliceFlag)->assertOk()->assertExactJson(['correct' => true]);

        $this->runner->assertStopped($aliceProject);
        $this->runner->assertNotStopped($bobProject);
        $this->assertFalse($this->runner->isRunning($aliceProject));
        $this->assertTrue($this->runner->isRunning($bobProject), 'Completing a lab stopped another user\'s instance.');
        $this->actingAs($alice)->getJson(route('labs.status', $lab))->assertJson(['status' => 'stopped']);
        $this->actingAs($bob)->getJson(route('labs.status', $lab))->assertJson(['status' => 'ready']);
        $this->assertTrue($flags->verify($bobProject, $bobFlag), 'Bob\'s flag was forgotten.');
        $this->assertFalse($flags->verify($aliceProject, $aliceFlag));

        $this->submitFlag($bob, $lab, $bobFlag)->assertOk()->assertExactJson(['correct' => true]);
    }

    public function test_instance_urls_are_per_user(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $lab = $this->lab('osci');
        $this->launch($alice, $lab);
        $this->launch($bob, $lab);

        $aliceUrl = $this->actingAs($alice)->getJson(route('labs.status', $lab))->json('url');
        $bobUrl = $this->actingAs($bob)->getJson(route('labs.status', $lab))->json('url');

        $this->assertSame("http://lab-osci-u{$alice->id}.labs.localhost", $aliceUrl);
        $this->assertSame("http://lab-osci-u{$bob->id}.labs.localhost", $bobUrl);
    }

    public function test_flag_file_exists_before_up(): void
    {
        $this->app->instance(LabRunner::class, $spy = new SpyLabRunner);
        $user = User::factory()->create();
        $lab = $this->lab('ssrf');

        $this->launch($user, $lab)->assertStatus(202);

        $this->assertCount(1, $spy->flagAtUp);
        $this->assertTrue($spy->flagAtUp[0]['exists'], 'The flag file did not exist when up() was called (ssrf mounts it as a single file).');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', (string) $spy->flagAtUp[0]['content']);
        // It is the flag of the instance, not one that was replaced later.
        $this->assertSame($spy->flagAtUp[0]['content'], $this->flagFor($user, $lab));
        $this->assertTrue(app(FlagService::class)->verify($this->projectFor($user, $lab), $spy->flagAtUp[0]['content']));
    }

    public function test_failed_up_forgets_flag(): void
    {
        $this->app->instance(LabRunner::class, $spy = new SpyLabRunner);
        $spy->failUp = true;
        $user = User::factory()->create();
        $lab = $this->lab('osci');
        $project = $this->projectFor($user, $lab);

        $this->launch($user, $lab)->assertStatus(202);

        // The flag existed when up() failed, so forgetting it is what makes isIssued() false below.
        $this->assertTrue($spy->flagAtUp[0]['exists'] ?? false, 'No flag was issued before up().');
        $this->assertFalse(app(FlagService::class)->isIssued($project), 'A failed launch left an issued flag behind.');
        $this->assertFalse(app(FlagService::class)->verify($project, (string) $spy->flagAtUp[0]['content']));
        $this->actingAs($user)->getJson(route('labs.status', $lab))->assertExactJson(['status' => 'failed']);
        $this->assertDatabaseCount('lab_user', 0);
        $this->runner->assertNotStopped($project);
    }

    public function test_launch_is_refused_at_capacity(): void
    {
        config()->set('labs.max_instances', 2);
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $carol = User::factory()->create();
        $this->launch($alice, $this->lab('osci'))->assertStatus(202);
        $this->launch($bob, $this->lab('dom-xss'))->assertStatus(202);

        Queue::fake();

        $this->actingAs($carol)->postJson(route('labs.launch', $this->lab('rbac')))
            ->assertStatus(503)
            ->assertExactJson(['status' => 'capacity']);

        Queue::assertNothingPushed();
        $this->assertCount(2, $this->runner->upCalls);
        $this->assertDatabaseMissing('lab_user', ['user_id' => $carol->id]);
        $this->actingAs($carol)->getJson(route('labs.status', $this->lab('rbac')))->assertExactJson(['status' => 'stopped']);

        // A second lab for someone who already runs one still counts as a new instance.
        $this->actingAs($alice)->postJson(route('labs.launch', $this->lab('rbac')))->assertStatus(503);

        // Relaunching an instance one already has is not a new instance.
        $this->actingAs($alice)->postJson(route('labs.launch', $this->lab('osci')))->assertStatus(202);
        Queue::assertPushed(LaunchLab::class, 1);
    }

    public function test_capacity_slot_is_reserved_at_request_time_not_in_the_job(): void
    {
        config()->set('labs.max_instances', 2);
        Queue::fake();
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $carol = User::factory()->create();

        // No job ever runs (Queue::fake): the slot must be taken by the controller itself.
        $this->actingAs($alice)->postJson(route('labs.launch', $this->lab('osci')))->assertStatus(202);
        $this->actingAs($bob)->postJson(route('labs.launch', $this->lab('dom-xss')))->assertStatus(202);
        Queue::assertPushed(LaunchLab::class, 2);

        $this->actingAs($carol)->postJson(route('labs.launch', $this->lab('rbac')))
            ->assertStatus(503)
            ->assertExactJson(['status' => 'capacity']);

        Queue::assertPushed(LaunchLab::class, 2);
        $this->assertCount(2, app(InstanceRegistry::class)->running());
        $this->assertArrayNotHasKey($this->projectFor($carol, $this->lab('rbac')), app(InstanceRegistry::class)->running());
    }

    public function test_capacity_frees_up_when_an_instance_stops(): void
    {
        config()->set('labs.max_instances', 2);
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $carol = User::factory()->create();
        $osci = $this->lab('osci');
        $this->launch($alice, $osci)->assertStatus(202);
        $this->launch($bob, $this->lab('dom-xss'))->assertStatus(202);
        $this->launch($carol, $this->lab('rbac'))->assertStatus(503);

        $this->submitFlag($alice, $osci, $this->flagFor($alice, $osci))->assertExactJson(['correct' => true]);

        $this->launch($carol, $this->lab('rbac'))->assertStatus(202);
        $this->assertTrue($this->runner->isRunning($this->projectFor($carol, $this->lab('rbac'))));
    }
}
