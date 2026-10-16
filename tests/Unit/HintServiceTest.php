<?php

namespace Tests\Unit;

use App\Models\LabProgress;
use App\Models\User;
use App\Services\HintService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
        $this->travelTo($start = Carbon::parse('2026-03-02 10:00:00'));
        $user = User::factory()->create();
        $this->progress($user, $this->lab('osci'), $start);
        $service = app(HintService::class);
        $texts = fn (): array => array_column($service->due($this->progressOf($user, 'osci')), 'text');

        $this->assertSame([], $texts());
        $this->travelTo($start->copy()->addSeconds(119));
        $this->assertSame([], $texts());
        $this->travelTo($start->copy()->addMinutes(2));
        $this->assertSame(['one'], $texts());
        $this->travelTo($start->copy()->addMinutes(5));
        $this->assertSame(['one', 'two'], $texts());
        $this->travelTo($start->copy()->addHour());
        $this->assertSame(['one', 'two', 'three'], $texts());
        $this->assertSame(['n', 'text'], array_keys($service->due($this->progressOf($user, 'osci'))[0]));
    }

    public function test_a_lab_without_a_schedule_has_no_hints(): void
    {
        $this->seedLabs();
        config()->set('hints', []);
        $user = User::factory()->create();
        $this->progress($user, $this->lab('osci'), now()->subDay());

        $this->assertSame([], app(HintService::class)->due($this->progressOf($user, 'osci')));
    }
}
