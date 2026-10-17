<?php

namespace Tests\Unit;

use App\Models\LabProgress;
use App\Models\User;
use App\Services\HintService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Hints are anchored to the $since argument (the current instance launch), never to the pivot's started_at. */
class HintServiceTest extends TestCase
{
    use RefreshDatabase;

    private function progressOf(User $user, string $slug): LabProgress
    {
        return $user->labs()->where('labs.id', $this->lab($slug)->id)->firstOrFail()->pivot;
    }

    public function test_due_returns_the_hints_whose_delay_has_passed(): void
    {
        $this->seedLabs();
        config()->set('hints.osci', [
            ['after' => 2, 'text' => 'one'],
            ['after' => 4, 'text' => 'two'],
            ['after' => 6, 'text' => 'three'],
        ]);
        $this->travelTo($since = Carbon::parse('2026-03-02 10:00:00'));
        $user = User::factory()->create();
        $this->progress($user, $this->lab('osci'));
        $progress = $this->progressOf($user, 'osci');
        $service = app(HintService::class);
        $texts = fn (): array => array_column($service->due($progress, $since), 'text');

        $this->assertSame([], $texts());
        $this->travelTo($since->copy()->addSeconds(119));
        $this->assertSame([], $texts());
        $this->travelTo($since->copy()->addMinutes(2));
        $this->assertSame(['one'], $texts());
        $this->travelTo($since->copy()->addMinutes(5));
        $this->assertSame(['one', 'two'], $texts());
        $this->travelTo($since->copy()->addHour());
        $this->assertSame(['one', 'two', 'three'], $texts());
        $this->assertSame(['n', 'text'], array_keys($service->due($progress, $since)[0]));
        $this->assertSame([1, 2, 3], array_column($service->due($progress, $since), 'n'));
    }

    public function test_the_anchor_is_the_since_argument(): void
    {
        $this->seedLabs();
        config()->set('hints.osci', [
            ['after' => 2, 'text' => 'one'],
            ['after' => 4, 'text' => 'two'],
        ]);
        $this->travelTo($now = Carbon::parse('2026-03-02 10:00:00'));
        $user = User::factory()->create();
        $this->progress($user, $this->lab('osci'), $now->copy()->subDay());
        $progress = $this->progressOf($user, 'osci');
        $service = app(HintService::class);

        $this->assertSame([], $service->due($progress, $now));
        $this->assertSame(['one'], array_column($service->due($progress, $now->copy()->subMinutes(3)), 'text'));
        $this->assertSame(['one', 'two'], array_column($service->due($progress, $now->copy()->subMinutes(4)), 'text'));
    }

    public function test_next_at_is_the_earliest_unreleased_hint_relative_to_since(): void
    {
        $this->seedLabs();
        config()->set('hints.osci', [
            ['after' => 2, 'text' => 'one'],
            ['after' => 4, 'text' => 'two'],
            ['after' => 6, 'text' => 'three'],
        ]);
        $this->travelTo($since = Carbon::parse('2026-03-02 10:00:00'));
        $user = User::factory()->create();
        $this->progress($user, $this->lab('osci'));
        $progress = $this->progressOf($user, 'osci');
        $service = app(HintService::class);

        $this->assertTrue($service->nextAt($progress, $since)?->equalTo($since->copy()->addMinutes(2)) ?? false);
        $this->travelTo($since->copy()->addMinutes(2));
        $this->assertTrue($service->nextAt($progress, $since)?->equalTo($since->copy()->addMinutes(4)) ?? false);
        $this->travelTo($since->copy()->addMinutes(5));
        $this->assertTrue($service->nextAt($progress, $since)?->equalTo($since->copy()->addMinutes(6)) ?? false);
        $this->travelTo($since->copy()->addMinutes(6));
        $this->assertNull($service->nextAt($progress, $since));
    }

    public function test_a_lab_without_a_schedule_has_no_hints(): void
    {
        $this->seedLabs();
        config()->set('hints', []);
        $user = User::factory()->create();
        $this->progress($user, $this->lab('osci'), now()->subDay());
        $progress = $this->progressOf($user, 'osci');

        $this->assertSame([], app(HintService::class)->due($progress, now()->subDay()));
        $this->assertNull(app(HintService::class)->nextAt($progress, now()->subDay()));
    }
}
