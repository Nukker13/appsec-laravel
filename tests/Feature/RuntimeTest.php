<?php

namespace Tests\Feature;

use App\Jobs\LaunchLab;
use App\Models\User;
use App\Services\FlagService;
use App\Testing\FakeLabRunner;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RuntimeTest extends TestCase
{
    use RefreshDatabase;

    private FakeLabRunner $runner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLabs();
        $this->runner = $this->fakeLabRunner();
    }

    public function test_launch_dispatches_launch_job(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $lab = $this->lab('osci');

        $this->launch($user, $lab)
            ->assertStatus(202)
            ->assertJson(['status' => 'pending']);

        Queue::assertPushed(LaunchLab::class, fn (LaunchLab $job): bool => $job->user->is($user) && $job->lab->is($lab));
        $this->assertSame([], $this->runner->upCalls, 'The request must not start containers itself.');
        $this->assertDatabaseCount('lab_user', 0);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function inFlightStatuses(): array
    {
        return ['starting' => ['starting'], 'ready' => ['ready']];
    }

    #[DataProvider('inFlightStatuses')]
    public function test_launch_keeps_status_when_instance_is_already_starting_or_ready(string $status): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $lab = $this->lab('osci');
        Cache::put('lab:'.$this->projectFor($user, $lab).':status', $status);

        $this->launch($user, $lab)->assertStatus(202)->assertJson(['status' => $status]);

        $this->assertSame($status, $this->actingAs($user)->getJson(route('labs.status', $lab))->json('status'));
    }

    public function test_launch_job_marks_instance_ready(): void
    {
        $this->travelTo($now = Carbon::parse('2026-03-02 10:00:00'));
        $user = User::factory()->create();
        $lab = $this->lab('osci');
        $project = $this->projectFor($user, $lab);

        $this->launch($user, $lab)->assertStatus(202)->assertJsonStructure(['status']);

        $this->runner->assertStarted($project);
        $this->assertSame(1, $this->runner->startedCount($project));
        $this->assertSame($lab->slug, $this->runner->upCalls[0]['slug']);

        $host = str_replace('_', '-', $project).'.labs.localhost';
        $this->actingAs($user)->getJson(route('labs.status', $lab))
            ->assertOk()
            ->assertExactJson(['status' => 'ready', 'url' => 'http://'.$host]);

        $progress = $user->labs()->where('labs.id', $lab->id)->firstOrFail()->pivot;
        $this->assertSame('started', $progress->status);
        $this->assertTrue($progress->started_at->equalTo($now));
        $this->assertNull($progress->completed_at);

        // The flag was issued for this instance: the file holds a 32-byte hex secret that the service accepts.
        $flag = $this->flagFor($user, $lab);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $flag);
        $this->assertTrue(app(FlagService::class)->verify($project, $flag));
    }

    public function test_relaunch_does_not_overwrite_started_at(): void
    {
        $this->travelTo($first = Carbon::parse('2026-03-02 10:00:00'));
        $user = User::factory()->create();
        $lab = $this->lab('osci');
        $project = $this->projectFor($user, $lab);

        $this->launch($user, $lab)->assertStatus(202);

        // The instance went away (idle reaper, crash), the student launches again later.
        $this->runner->down($lab->slug, $project);
        $this->travelTo($first->copy()->addMinutes(40));
        $this->launch($user, $lab)->assertStatus(202);

        $this->assertSame(2, $this->runner->startedCount($project));
        $this->assertSame(1, $user->labs()->where('labs.id', $lab->id)->count());
        $this->assertTrue($user->labs()->where('labs.id', $lab->id)->firstOrFail()->pivot->started_at->equalTo($first));
    }

    public function test_launching_lab_does_not_stop_other_labs(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        // Alice runs osci, Bob runs dom-xss; then Alice launches a second lab and Bob re-launches his.
        $this->launch($alice, $this->lab('osci'))->assertStatus(202);
        $this->launch($bob, $this->lab('dom-xss'))->assertStatus(202);
        $this->launch($alice, $this->lab('rbac'))->assertStatus(202);
        $this->launch($bob, $this->lab('dom-xss'))->assertStatus(202);

        $this->assertSame([], $this->runner->downCalls, 'A launch must never stop any instance.');
        foreach ([[$alice, 'osci'], [$bob, 'dom-xss'], [$alice, 'rbac']] as [$user, $slug]) {
            $project = $this->projectFor($user, $this->lab($slug));
            $this->runner->assertNotStopped($project);
            $this->assertTrue($this->runner->isRunning($project), "{$slug} stopped running.");
            $this->actingAs($user)->getJson(route('labs.status', $this->lab($slug)))->assertJson(['status' => 'ready']);
        }
    }

    /**
     * @return array<string, array{string|null, string}>
     */
    public static function statuses(): array
    {
        return [
            'no key means stopped' => [null, 'stopped'],
            'pending' => ['pending', 'pending'],
            'starting' => ['starting', 'starting'],
            'ready' => ['ready', 'ready'],
            'failed' => ['failed', 'failed'],
            'stopped' => ['stopped', 'stopped'],
        ];
    }

    #[DataProvider('statuses')]
    public function test_status_endpoint_returns_instance_status(?string $cached, string $expected): void
    {
        $user = User::factory()->create();
        $lab = $this->lab('osci');
        $project = $this->projectFor($user, $lab);

        if ($cached !== null) {
            Cache::put('lab:'.$project.':status', $cached);
        }

        $response = $this->actingAs($user)->getJson(route('labs.status', $lab))->assertOk()->assertJson(['status' => $expected]);

        if ($expected === 'ready') {
            $response->assertJsonPath('url', 'http://'.str_replace('_', '-', $project).'.labs.localhost');
        } else {
            $this->assertArrayNotHasKey('url', $response->json(), 'url is only present when ready.');
        }
    }

    public function test_reaper_stops_only_expired_instances(): void
    {
        config()->set('labs.idle_minutes', 15);
        $this->travelTo($t0 = Carbon::parse('2026-03-02 10:00:00'));
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $stale = $this->lab('osci');
        $fresh = $this->lab('dom-xss');
        $staleProject = $this->projectFor($alice, $stale);
        $freshProject = $this->projectFor($bob, $fresh);

        $this->launch($alice, $stale)->assertStatus(202);
        $staleFlag = $this->flagFor($alice, $stale);

        $this->travelTo($t0->copy()->addMinutes(10));
        $this->launch($bob, $fresh)->assertStatus(202);
        $freshFlag = $this->flagFor($bob, $fresh);

        // 14 minutes after the first launch nothing is idle for more than 15 minutes yet.
        $this->travelTo($t0->copy()->addMinutes(14));
        $this->artisan('labs:reap')->assertExitCode(0);
        $this->assertSame([], $this->runner->downCalls);

        // 16 minutes: the first instance has been idle for 16, the second for 6.
        $this->travelTo($t0->copy()->addMinutes(16));
        $this->artisan('labs:reap')->assertExitCode(0);

        $this->runner->assertStopped($staleProject);
        $this->assertFalse($this->runner->isRunning($staleProject));
        $this->runner->assertNotStopped($freshProject);
        $this->assertTrue($this->runner->isRunning($freshProject));
        $this->assertCount(1, $this->runner->downCalls);

        $this->actingAs($alice)->getJson(route('labs.status', $stale))->assertJson(['status' => 'stopped']);
        $this->actingAs($bob)->getJson(route('labs.status', $fresh))->assertJson(['status' => 'ready']);
        $this->assertFalse(app(FlagService::class)->verify($staleProject, $staleFlag), 'The reaped instance keeps a valid flag.');
        $this->assertTrue(app(FlagService::class)->verify($freshProject, $freshFlag));

        // Reaping again is a no-op for the already stopped one.
        $this->artisan('labs:reap')->assertExitCode(0);
        $this->assertCount(1, $this->runner->downCalls);
    }

    public function test_reaper_is_scheduled_every_minute(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($event): bool => str_contains((string) $event->command, 'labs:reap'));

        $this->assertCount(1, $events);
        $this->assertSame('* * * * *', $events->first()->expression);
    }
}
