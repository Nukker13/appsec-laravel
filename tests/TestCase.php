<?php

namespace Tests;

use App\Contracts\LabRunner;
use App\Models\Lab;
use App\Models\User;
use App\Services\FlagService;
use App\Services\InstanceResolver;
use App\Testing\FakeLabRunner;
use Database\Seeders\LabSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use Throwable;

abstract class TestCase extends BaseTestCase
{
    /** @var list<string> flag files written by a test, removed in tearDown */
    private array $flagFiles = [];

    protected function tearDown(): void
    {
        // Phase 3 writes the flag into labs/{slug}/flag.txt: never leave one behind.
        try {
            foreach (Lab::query()->get() as $lab) {
                foreach (User::query()->get() as $user) {
                    $this->flagFiles[] = app(FlagService::class)->flagFile(app(InstanceResolver::class)->project($user, $lab), $lab);
                }
            }
        } catch (Throwable) {
            // stubs throw, or no database in this test: nothing to sweep
        }

        foreach (array_unique($this->flagFiles) as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    /** Swap the in-memory runner in; with QUEUE_CONNECTION=sync, LaunchLab/StopLab then run inline against it. */
    protected function fakeLabRunner(): FakeLabRunner
    {
        $this->app->instance(LabRunner::class, $fake = new FakeLabRunner);

        return $fake;
    }

    protected function seedLabs(): void
    {
        $this->seed(LabSeeder::class);
    }

    protected function lab(string $slug): Lab
    {
        return Lab::query()->where('slug', $slug)->firstOrFail();
    }

    protected function projectFor(User $user, Lab $lab): string
    {
        return app(InstanceResolver::class)->project($user, $lab);
    }

    /** POST labs.launch as $user (the job runs inline on the sync queue). */
    protected function launch(User $user, Lab $lab): TestResponse
    {
        return $this->actingAs($user)->postJson(route('labs.launch', $lab));
    }

    /** The plaintext flag of the instance, read from the instance flag file (the only way a student gets it). */
    protected function flagFor(User $user, Lab $lab): string
    {
        $file = app(FlagService::class)->flagFile($this->projectFor($user, $lab), $lab);
        $this->flagFiles[] = $file;

        return trim((string) file_get_contents($file));
    }

    protected function submitFlag(User $user, Lab $lab, string $flag): TestResponse
    {
        return $this->actingAs($user)->postJson(route('labs.flag', $lab), ['flag' => $flag]);
    }
}
