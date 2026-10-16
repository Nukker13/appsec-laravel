<?php

namespace Tests;

use App\Contracts\LabRunner;
use App\Models\Lab;
use App\Models\User;
use App\Services\FlagService;
use App\Services\InstanceResolver;
use App\Testing\FakeLabRunner;
use Carbon\CarbonInterface;
use Database\Seeders\LabSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    /** Fresh per test: instance directories and flag files never touch labs/ or storage/. */
    protected string $instancesDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instancesDir = sys_get_temp_dir().'/appsec-instances-'.bin2hex(random_bytes(6));
        mkdir($this->instancesDir, 0777, true);
        config()->set('labs.instances_path', $this->instancesDir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->instancesDir);

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

    /** Insert a lab_user row directly (progress that existed before the test started). */
    protected function progress(User $user, Lab $lab, ?CarbonInterface $startedAt = null, string $status = 'started', ?CarbonInterface $completedAt = null): void
    {
        $user->labs()->attach($lab->id, [
            'status' => $status,
            'started_at' => $startedAt ?? now(),
            'completed_at' => $completedAt,
        ]);
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

        return trim((string) file_get_contents($file));
    }

    protected function submitFlag(User $user, Lab $lab, string $flag): TestResponse
    {
        return $this->actingAs($user)->postJson(route('labs.flag', $lab), ['flag' => $flag]);
    }
}
