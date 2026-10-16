<?php

namespace Tests\Feature;

use App\Models\FlagAttempt;
use App\Models\User;
use App\Services\FlagService;
use App\Testing\FakeLabRunner;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlagTest extends TestCase
{
    use RefreshDatabase;

    private FakeLabRunner $runner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLabs();
        $this->runner = $this->fakeLabRunner();
    }

    private function attempts(User $user, string $slug): int
    {
        return FlagAttempt::query()->where('user_id', $user->id)->where('lab_id', $this->lab($slug)->id)->count();
    }

    private function pivot(User $user, string $slug): ?object
    {
        return $user->labs()->where('labs.id', $this->lab($slug)->id)->first()?->pivot;
    }

    public function test_viewing_lab_page_does_not_change_flag(): void
    {
        $student = User::factory()->create();
        $classmate = User::factory()->create();
        $lab = $this->lab('osci');
        $project = $this->projectFor($student, $lab);

        $this->launch($student, $lab)->assertStatus(202);
        $flag = $this->flagFor($student, $lab);

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($student)->get(route('labs.show', $lab))->assertOk();
            $this->actingAs($classmate)->get(route('labs.show', $lab))->assertOk();
            $this->actingAs($student)->getJson(route('labs.status', $lab))->assertOk();
            $this->actingAs($student)->getJson(route('labs.hints', $lab))->assertOk();
        }

        $this->assertSame($flag, $this->flagFor($student, $lab), 'The flag file changed after page views.');
        $this->assertTrue(app(FlagService::class)->verify($project, $flag), 'The issued flag no longer verifies after page views.');
        $this->assertSame(1, $this->runner->startedCount($project));
    }

    public function test_viewing_a_lab_that_was_never_launched_issues_no_flag(): void
    {
        $student = User::factory()->create();
        $lab = $this->lab('rbac');

        $this->actingAs($student)->get(route('labs.show', $lab))->assertOk();

        $this->assertFileDoesNotExist(app(FlagService::class)->flagFile($this->projectFor($student, $lab), $lab));
        $this->submitFlag($student, $lab, 'anything')->assertOk()->assertExactJson(['correct' => false]);
    }

    public function test_relaunching_running_instance_keeps_flag(): void
    {
        $student = User::factory()->create();
        $lab = $this->lab('osci');
        $project = $this->projectFor($student, $lab);

        $this->launch($student, $lab)->assertStatus(202);
        $flag = $this->flagFor($student, $lab);
        $this->launch($student, $lab)->assertStatus(202);

        $this->assertSame(1, $this->runner->startedCount($project), 'A running instance was started again.');
        $this->assertSame($flag, $this->flagFor($student, $lab));
        $this->assertTrue(app(FlagService::class)->verify($project, $flag));
    }

    public function test_correct_flag_completes_lab_for_current_user_only(): void
    {
        $this->travelTo($t0 = Carbon::parse('2026-03-02 10:00:00'));
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $carol = User::factory()->create();
        $osci = $this->lab('osci');

        // Carol solved osci long ago; Alice and Bob are working on it now; Bob also works on rbac.
        $carolDone = $t0->copy()->subDays(3);
        $this->progress($carol, $osci, $carolDone->copy()->subHour(), 'completed', $carolDone);
        $this->launch($alice, $osci)->assertStatus(202);
        $this->launch($bob, $osci)->assertStatus(202);
        $this->launch($bob, $this->lab('rbac'))->assertStatus(202);
        $flag = $this->flagFor($alice, $osci);

        $this->travelTo($t0->copy()->addMinutes(9));
        $this->submitFlag($alice, $osci, $flag)->assertOk()->assertExactJson(['correct' => true]);

        $a = $this->pivot($alice, 'osci');
        $this->assertSame('completed', $a->status);
        $this->assertTrue($a->completed_at->equalTo($t0->copy()->addMinutes(9)));
        $this->assertTrue($a->started_at->equalTo($t0));

        $b = $this->pivot($bob, 'osci');
        $this->assertSame('started', $b->status, 'Completing a lab marked it completed for another user (D1).');
        $this->assertNull($b->completed_at);
        $this->assertSame('started', $this->pivot($bob, 'rbac')->status);

        $c = $this->pivot($carol, 'osci');
        $this->assertSame('completed', $c->status);
        $this->assertTrue($c->completed_at->equalTo($carolDone), 'Another user\'s completion time was overwritten (D1).');

        $this->assertDatabaseCount('lab_user', 4);
        $this->assertSame(1, $this->attempts($alice, 'osci'));
        $this->assertTrue(FlagAttempt::query()->where('user_id', $alice->id)->value('is_correct'));
        $this->assertSame(0, $this->attempts($bob, 'osci'));
        $this->assertSame(0, $this->attempts($carol, 'osci'));
    }

    public function test_wrong_flag_records_attempt_and_keeps_progress(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $osci = $this->lab('osci');
        $project = $this->projectFor($alice, $osci);

        $this->launch($alice, $osci)->assertStatus(202);
        $this->launch($bob, $osci)->assertStatus(202);
        $flag = $this->flagFor($alice, $osci);

        $this->submitFlag($alice, $osci, 'not-the-flag')->assertOk()->assertExactJson(['correct' => false]);

        $this->assertSame(1, $this->attempts($alice, 'osci'));
        $this->assertFalse(FlagAttempt::query()->where('user_id', $alice->id)->value('is_correct'));
        $this->assertSame(0, $this->attempts($bob, 'osci'));
        foreach ([$alice, $bob] as $user) {
            $this->assertSame('started', $this->pivot($user, 'osci')->status);
            $this->assertNull($this->pivot($user, 'osci')->completed_at);
        }
        $this->runner->assertNotStopped($project);
        $this->assertTrue(app(FlagService::class)->verify($project, $flag), 'A wrong guess invalidated the real flag.');
        $this->submitFlag($alice, $osci, $flag)->assertOk()->assertExactJson(['correct' => true]);
    }

    public function test_flag_without_running_instance_is_incorrect_and_still_recorded(): void
    {
        $student = User::factory()->create();

        $this->submitFlag($student, $this->lab('osci'), str_repeat('a', 64))->assertOk()->assertExactJson(['correct' => false]);

        $this->assertSame(1, $this->attempts($student, 'osci'));
        $this->assertDatabaseCount('lab_user', 0);
    }

    public function test_flag_field_is_required(): void
    {
        $student = User::factory()->create();
        $lab = $this->lab('osci');

        $this->actingAs($student)->postJson(route('labs.flag', $lab), [])->assertStatus(422)->assertJsonValidationErrors('flag');
        $this->actingAs($student)->postJson(route('labs.flag', $lab), ['flag' => ['a']])->assertStatus(422);

        $this->assertSame(0, $this->attempts($student, 'osci'));
    }

    public function test_flag_check_is_rate_limited(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $osci = $this->lab('osci');
        $this->launch($alice, $osci)->assertStatus(202);
        $flag = $this->flagFor($alice, $osci);

        for ($i = 1; $i <= 10; $i++) {
            $this->submitFlag($alice, $osci, "guess-{$i}")->assertOk();
        }

        $this->submitFlag($alice, $osci, 'guess-11')->assertStatus(429);
        // Throttled means no more guesses at all, the real flag included.
        $this->submitFlag($alice, $osci, $flag)->assertStatus(429);
        $this->assertSame('started', $this->pivot($alice, 'osci')->status);
        $this->assertSame(10, $this->attempts($alice, 'osci'), 'Throttled requests must not be recorded.');

        // The limit is per user and per lab.
        $this->submitFlag($bob, $osci, 'guess')->assertOk();
        $this->submitFlag($alice, $this->lab('dom-xss'), 'guess')->assertOk();
    }

    public function test_correct_flag_stops_instance_and_forgets_flag(): void
    {
        $student = User::factory()->create();
        $lab = $this->lab('osci');
        $project = $this->projectFor($student, $lab);

        $this->launch($student, $lab)->assertStatus(202);
        $flag = $this->flagFor($student, $lab);
        $this->assertTrue($this->runner->isRunning($project));

        $this->submitFlag($student, $lab, $flag)->assertOk()->assertExactJson(['correct' => true]);

        $this->runner->assertStopped($project);
        $this->assertFalse($this->runner->isRunning($project));
        $this->assertFalse(app(FlagService::class)->verify($project, $flag), 'The flag is still valid after the lab was completed.');
        $this->actingAs($student)->getJson(route('labs.status', $lab))->assertJson(['status' => 'stopped']);
    }

    public function test_relaunch_after_completion_issues_a_new_flag(): void
    {
        $student = User::factory()->create();
        $lab = $this->lab('osci');
        $project = $this->projectFor($student, $lab);

        $this->launch($student, $lab)->assertStatus(202);
        $old = $this->flagFor($student, $lab);
        $this->submitFlag($student, $lab, $old)->assertExactJson(['correct' => true]);

        $this->launch($student, $lab)->assertStatus(202);
        $new = $this->flagFor($student, $lab);

        $this->assertNotSame($old, $new);
        $this->assertTrue(app(FlagService::class)->verify($project, $new));
        $this->assertFalse(app(FlagService::class)->verify($project, $old));
    }
}
