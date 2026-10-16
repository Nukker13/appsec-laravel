<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HintsTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array{after: int, text: string}> */
    private const SCHEDULE = [
        ['after' => 2, 'text' => 'HINT-ONE-zq81 first nudge'],
        ['after' => 4, 'text' => 'HINT-TWO-kx42 second nudge'],
        ['after' => 6, 'text' => 'HINT-THREE-vm07 third nudge'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('hints.osci', self::SCHEDULE);
        $this->seedLabs();
    }

    public function test_hints_are_hidden_before_launch(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        // Somebody else (and this user on another lab) has progress; this user never launched osci.
        $this->progress($other, $this->lab('osci'), now()->subHour());
        $this->progress($user, $this->lab('dom-xss'), now()->subHour());

        $this->actingAs($user)->getJson(route('labs.hints', 'osci'))
            ->assertOk()
            ->assertExactJson(['hints' => []]);

        $this->travel(30)->minutes();

        $this->actingAs($user)->getJson(route('labs.hints', 'osci'))
            ->assertOk()
            ->assertExactJson(['hints' => []]);
    }

    /**
     * @param  list<string>  $texts
     */
    private function assertHints(User $user, array $texts, ?Carbon $nextAt): void
    {
        $response = $this->actingAs($user)->getJson(route('labs.hints', 'osci'))->assertOk();

        $this->assertSame($texts, $response->json('hints.*.text'));
        $response->assertJsonStructure(['hints' => ['*' => ['n', 'text']]]);

        if ($nextAt === null) {
            $this->assertArrayNotHasKey('next_at', $response->json());

            return;
        }

        $this->assertNotNull($response->json('next_at'), 'next_at is missing while hints are still pending.');
        $this->assertTrue(Carbon::parse($response->json('next_at'))->equalTo($nextAt), 'next_at is '.$response->json('next_at').', expected '.$nextAt->toIso8601String());
    }

    public function test_hints_are_released_at_2_4_6_minutes_after_start(): void
    {
        $this->travelTo($start = Carbon::parse('2026-03-02 10:00:00'));
        $user = User::factory()->create();
        $this->progress($user, $this->lab('osci'), $start);
        [$one, $two, $three] = array_column(self::SCHEDULE, 'text');

        $this->assertHints($user, [], $start->copy()->addMinutes(2));

        $this->travelTo($start->copy()->addSeconds(119));
        $this->assertHints($user, [], $start->copy()->addMinutes(2));

        $this->travelTo($start->copy()->addMinutes(2));
        $this->assertHints($user, [$one], $start->copy()->addMinutes(4));

        // Reloading the lab page must not restart the clock (the original page reset its timer on every view).
        $this->travelTo($start->copy()->addSeconds(230));
        $this->actingAs($user)->get(route('labs.show', 'osci'))->assertOk();
        $this->assertHints($user, [$one], $start->copy()->addMinutes(4));

        $this->travelTo($start->copy()->addMinutes(4));
        $this->assertHints($user, [$one, $two], $start->copy()->addMinutes(6));

        $this->travelTo($start->copy()->addMinutes(6));
        $this->assertHints($user, [$one, $two, $three], null);

        $this->travelTo($start->copy()->addHours(5));
        $this->assertHints($user, [$one, $two, $three], null);

        $this->assertTrue($user->labs()->where('labs.id', $this->lab('osci')->id)->firstOrFail()->pivot->started_at->equalTo($start));
    }

    public function test_hints_follow_each_users_own_start_time(): void
    {
        $this->travelTo($now = Carbon::parse('2026-03-02 10:00:00'));
        $early = User::factory()->create();
        $late = User::factory()->create();
        $this->progress($early, $this->lab('osci'), $now->copy()->subMinutes(5));
        $this->progress($late, $this->lab('osci'), $now->copy()->subSeconds(30));

        $this->assertHints($early, [self::SCHEDULE[0]['text'], self::SCHEDULE[1]['text']], $now->copy()->addMinute());
        $this->assertHints($late, [], $now->copy()->addSeconds(90));
    }

    public function test_lab_page_html_contains_no_hint_text(): void
    {
        $this->travelTo($now = Carbon::parse('2026-03-02 10:00:00'));
        $user = User::factory()->create();
        $this->progress($user, $this->lab('osci'), $now->copy()->subMinutes(3));

        $fresh = User::factory()->create();

        // $user has hint one released, hints two and three still locked; $fresh has not launched at all.
        foreach ([$user, $fresh] as $viewer) {
            $html = (string) $this->actingAs($viewer)->get(route('labs.show', 'osci'))->assertOk()->getContent();

            foreach (self::SCHEDULE as $hint) {
                $this->assertStringNotContainsString($hint['text'], $html, 'Hint text is embedded in the page HTML.');
            }
        }

        $this->travelTo($now->copy()->addHours(2));
        $html = (string) $this->actingAs($user)->get(route('labs.show', 'osci'))->assertOk()->getContent();
        foreach (self::SCHEDULE as $hint) {
            $this->assertStringNotContainsString($hint['text'], $html, 'Hint text is embedded in the page HTML even when due.');
        }
    }
}
