<?php

namespace Tests\Feature;

use App\Models\AdminAction;
use App\Models\FlagAttempt;
use App\Models\Lab;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLabs();
    }

    private function admin(): User
    {
        return User::factory()->admin()->create(['name' => 'Root Admin']);
    }

    /** One flag attempt at a given moment. */
    private function attempt(User $user, Lab $lab, bool $correct, Carbon $at): void
    {
        $this->travelTo($at);
        FlagAttempt::query()->create(['user_id' => $user->id, 'lab_id' => $lab->id, 'is_correct' => $correct]);
    }

    /** $wrong wrong attempts, then the correct one at completion time, then $after more wrong ones. */
    private function solve(User $user, Lab $lab, Carbon $start, int $minutes, int $seconds, int $wrong, int $after = 0): void
    {
        $done = $start->copy()->addMinutes($minutes)->addSeconds($seconds);
        $this->progress($user, $lab, $start, 'completed', $done);
        for ($i = 1; $i <= $wrong; $i++) {
            $this->attempt($user, $lab, false, $start->copy()->addSeconds($i));
        }
        $this->attempt($user, $lab, true, $done);
        for ($i = 1; $i <= $after; $i++) {
            $this->attempt($user, $lab, false, $done->copy()->addMinutes($i));
        }
        $this->travelBack();
    }

    private function pageText(User $viewer, string $routeName): string
    {
        $html = (string) $this->actingAs($viewer)->get(route($routeName))->assertOk()->getContent();

        return html_entity_decode(strip_tags($html));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function adminRoutes(): array
    {
        return [
            'admin.progress' => ['GET', '/admin'],
            'admin.labs' => ['GET', '/admin/labs'],
            'admin.users.show' => ['GET', '/admin/users/{user}'],
            'admin.progress.reset' => ['POST', '/admin/users/{user}/labs/osci/reset'],
            'admin.progress.complete' => ['POST', '/admin/users/{user}/labs/osci/complete'],
        ];
    }

    #[DataProvider('adminRoutes')]
    public function test_non_admin_gets_403_on_every_admin_route(string $method, string $uri): void
    {
        $student = User::factory()->create();
        $target = User::factory()->create();
        $this->progress($target, $this->lab('osci'), now()->subHour());

        $this->actingAs($student)
            ->call($method, str_replace('{user}', (string) $target->id, $uri), ['reason' => 'sneaky'])
            ->assertForbidden();

        $this->assertSame('started', $target->labs()->first()->pivot->status);
        $this->assertDatabaseCount('admin_actions', 0);
    }

    public function test_progress_matrix_shows_status_time_and_attempts(): void
    {
        $this->travelTo($t0 = Carbon::parse('2026-01-05 10:00:00'));
        $alice = User::factory()->create(['name' => 'Alice Tester']);
        $bob = User::factory()->create(['name' => 'Bob Tester']);
        $carol = User::factory()->create(['name' => 'Carol Idle']);

        // Alice: osci solved in 47 min 20 s, 7 attempts up to and including the correct one (2 more after it).
        $this->solve($alice, $this->lab('osci'), $t0, 47, 20, wrong: 6, after: 2);
        // Bob: dom-xss started, 3 wrong attempts.
        $this->progress($bob, $this->lab('dom-xss'), $t0);
        foreach ([1, 2, 3] as $i) {
            $this->attempt($bob, $this->lab('dom-xss'), false, $t0->copy()->addMinutes($i));
        }
        $this->travelBack();

        $text = $this->pageText($this->admin(), 'admin.progress');

        foreach (['Alice Tester', 'Bob Tester', 'Carol Idle', 'OS Command Injection', 'DOM XSS'] as $expected) {
            $this->assertStringContainsString($expected, $text);
        }
        $this->assertStringContainsStringIgnoringCase('completed', $text);
        $this->assertStringContainsStringIgnoringCase('started', $text);
        $this->assertStringContainsString('47', $text, 'Solve time (minutes) is missing.');
        $this->assertMatchesRegularExpression('/(?<!\d)7(?!\d)/', $text, 'Attempt count (7) is missing.');
    }

    public function test_lab_analytics_computes_completion_rate_and_median_time(): void
    {
        $this->travelTo($t0 = Carbon::parse('2026-01-05 10:00:00'));
        $osci = $this->lab('osci');
        $users = User::factory()->count(5)->create();

        // Solve times 11, 23 and 41 minutes (median 23, mean 25); attempts 6, 14 and 22 (mean 14).
        $this->solve($users[0], $osci, $t0, 11, 0, wrong: 5);
        $this->solve($users[1], $osci, $t0, 23, 0, wrong: 13);
        $this->solve($users[2], $osci, $t0, 41, 0, wrong: 21);
        // Two students started and never finished: they count as started, not as completed, and not in the averages.
        foreach ([$users[3], $users[4]] as $unfinished) {
            $this->progress($unfinished, $osci, $t0);
            foreach ([1, 2, 3] as $i) {
                $this->attempt($unfinished, $osci, false, $t0->copy()->addMinutes($i));
            }
        }
        $this->travelBack();

        $text = $this->pageText($this->admin(), 'admin.labs');

        $this->assertStringContainsString('OS Command Injection', $text);
        $this->assertMatchesRegularExpression('/(?<!\d)60(?!\d)/', $text, 'Completion rate 3 of 5 = 60% is missing.');
        $this->assertMatchesRegularExpression('/(?<!\d)23(?!\d)/', $text, 'Median solve time (23 min) is missing.');
        $this->assertMatchesRegularExpression('/(?<!\d)14(?!\d)/', $text, 'Average attempts before success (14) is missing.');
    }

    public function test_lab_analytics_reports_hints_released_at_completion(): void
    {
        // A hint every 3 minutes from minute 3 to minute 60; solved after 38 min -> 12 hints were released.
        config()->set('hints.dom-xss', array_map(
            fn (int $i): array => ['after' => $i * 3, 'text' => "hint {$i}"],
            range(1, 20),
        ));
        $this->travelTo($t0 = Carbon::parse('2026-01-05 10:00:00'));
        $this->solve(User::factory()->create(), $this->lab('dom-xss'), $t0, 38, 0, wrong: 3);

        $text = $this->pageText($this->admin(), 'admin.labs');

        $this->assertStringContainsString('DOM XSS', $text);
        $this->assertMatchesRegularExpression('/(?<!\d)38(?!\d)/', $text);
        $this->assertMatchesRegularExpression('/(?<!\d)12(?!\d)/', $text, 'Hints released at completion (12) is missing.');
    }

    public function test_user_page_shows_progress_and_override_forms(): void
    {
        $target = User::factory()->create(['name' => 'Target Student']);
        $this->progress($target, $this->lab('osci'), now()->subHour());

        $html = (string) $this->actingAs($this->admin())->get(route('admin.users.show', $target))->assertOk()->getContent();

        $this->assertStringContainsString('Target Student', html_entity_decode(strip_tags($html)));
        $this->assertStringContainsString('OS Command Injection', $html);
        $this->assertStringContainsString(route('admin.progress.reset', [$target, 'osci'], false), $html);
        $this->assertStringContainsString(route('admin.progress.complete', [$target, 'osci'], false), $html);
    }

    /**
     * @return array<string, array{string, array<string, mixed>}>
     */
    public static function missingReasons(): array
    {
        return [
            'reset: absent' => ['admin.progress.reset', []],
            'reset: empty' => ['admin.progress.reset', ['reason' => '']],
            'reset: blank' => ['admin.progress.reset', ['reason' => '   ']],
            'complete: absent' => ['admin.progress.complete', []],
            'complete: empty' => ['admin.progress.complete', ['reason' => '']],
            'complete: blank' => ['admin.progress.complete', ['reason' => '   ']],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('missingReasons')]
    public function test_override_requires_reason(string $routeName, array $payload): void
    {
        $target = User::factory()->create();
        $lab = $this->lab('osci');
        $this->progress($target, $lab, now()->subHour());

        $this->actingAs($this->admin())->from(route('admin.users.show', $target))
            ->post(route($routeName, [$target, $lab]), $payload)
            ->assertSessionHasErrors('reason');

        $row = $target->labs()->first()->pivot;
        $this->assertSame('started', $row->status);
        $this->assertNull($row->completed_at);
        $this->assertDatabaseCount('admin_actions', 0);
    }

    public function test_override_reset_and_complete_write_audit_record(): void
    {
        $this->travelTo($now = Carbon::parse('2026-03-02 10:00:00'));
        $admin = $this->admin();
        $target = User::factory()->create();
        $bystander = User::factory()->create();
        $osci = $this->lab('osci');
        $rbac = $this->lab('rbac');
        $page = route('admin.users.show', $target);

        // Reset: the target solved osci; the bystander solved it too and must stay solved.
        $start = $now->copy()->subDay();
        $this->progress($target, $osci, $start, 'completed', $start->copy()->addMinutes(30));
        $this->progress($bystander, $osci, $start, 'completed', $start->copy()->addMinutes(20));
        FlagAttempt::query()->create(['user_id' => $target->id, 'lab_id' => $osci->id, 'is_correct' => true]);

        $this->actingAs($admin)->from($page)
            ->post(route('admin.progress.reset', [$target, $osci]), ['reason' => 'Student asked for a retry'])
            ->assertRedirect($page);

        $this->assertNull($target->labs()->where('labs.id', $osci->id)->first(), 'Reset must remove the progress row.');
        $this->assertSame('completed', $bystander->labs()->where('labs.id', $osci->id)->firstOrFail()->pivot->status);
        $this->assertSame(1, FlagAttempt::query()->where('user_id', $target->id)->count(), 'Reset keeps the attempt history.');

        $reset = AdminAction::query()->where('action', 'progress_reset')->sole();
        $this->assertSame($admin->id, $reset->admin_id);
        $this->assertSame($target->id, $reset->target_user_id);
        $this->assertSame($osci->id, $reset->lab_id);
        $this->assertSame('Student asked for a retry', $reset->reason);
        $this->assertStringContainsString('completed', (string) json_encode($reset->meta), 'meta must carry the previous state.');
        $this->assertTrue($reset->created_at->equalTo($now));

        // Complete: the target started rbac a while ago; the bystander started it too.
        $this->progress($target, $rbac, $now->copy()->subHours(2));
        $this->progress($bystander, $rbac, $now->copy()->subHours(2));

        $this->actingAs($admin)->from($page)
            ->post(route('admin.progress.complete', [$target, $rbac]), ['reason' => 'Solved offline in class'])
            ->assertRedirect($page);

        $row = $target->labs()->where('labs.id', $rbac->id)->firstOrFail()->pivot;
        $this->assertSame('completed', $row->status);
        $this->assertTrue($row->completed_at->equalTo($now));
        $this->assertTrue($row->started_at->equalTo($now->copy()->subHours(2)), 'complete keeps started_at.');
        $this->assertSame('started', $bystander->labs()->where('labs.id', $rbac->id)->firstOrFail()->pivot->status);

        $complete = AdminAction::query()->where('action', 'progress_complete')->sole();
        $this->assertSame([$admin->id, $target->id, $rbac->id, 'Solved offline in class'], [$complete->admin_id, $complete->target_user_id, $complete->lab_id, $complete->reason]);
        $this->assertStringContainsString('started', (string) json_encode($complete->meta));
        $this->assertDatabaseCount('admin_actions', 2);
    }

    public function test_override_without_previous_progress_records_null_meta(): void
    {
        $this->travelTo($now = Carbon::parse('2026-03-02 10:00:00'));
        $admin = $this->admin();
        $target = User::factory()->create();
        $dom = $this->lab('dom-xss');
        $ssrf = $this->lab('ssrf');

        $this->actingAs($admin)->post(route('admin.progress.complete', [$target, $dom]), ['reason' => 'Granted'])->assertRedirect();

        $row = $target->labs()->where('labs.id', $dom->id)->firstOrFail()->pivot;
        $this->assertSame('completed', $row->status);
        $this->assertTrue($row->started_at->equalTo($now));
        $this->assertTrue($row->completed_at->equalTo($now));
        $this->assertNull(AdminAction::query()->where('action', 'progress_complete')->sole()->meta);

        $this->actingAs($admin)->post(route('admin.progress.reset', [$target, $ssrf]), ['reason' => 'Nothing there'])->assertRedirect();

        $this->assertNull(AdminAction::query()->where('action', 'progress_reset')->sole()->meta);
        $this->assertSame(1, $target->labs()->count());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function overrides(): array
    {
        return ['reset' => ['admin.progress.reset'], 'complete' => ['admin.progress.complete']];
    }

    #[DataProvider('overrides')]
    public function test_override_and_audit_are_atomic(string $routeName): void
    {
        $target = User::factory()->create();
        $lab = $this->lab('osci');
        $this->progress($target, $lab, now()->subHour());

        // The audit insert goes through the AdminAction model; make it fail.
        $auditAttempts = 0;
        AdminAction::creating(function () use (&$auditAttempts): void {
            $auditAttempts++;

            throw new RuntimeException('audit log unavailable');
        });

        $this->actingAs($this->admin())->from(route('admin.users.show', $target))
            ->post(route($routeName, [$target, $lab]), ['reason' => 'Because']);

        $this->assertSame(1, $auditAttempts, 'The audit record was never written through the AdminAction model.');
        $this->assertDatabaseCount('admin_actions', 0);
        $row = $target->labs()->where('labs.id', $lab->id)->first()?->pivot;
        $this->assertNotNull($row, 'The override was applied although its audit record failed.');
        $this->assertSame('started', $row->status);
        $this->assertNull($row->completed_at);
    }
}
