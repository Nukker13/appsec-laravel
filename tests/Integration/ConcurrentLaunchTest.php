<?php

namespace Tests\Integration;

use App\Models\Lab;
use App\Models\User;
use App\Services\FlagService;
use App\Services\InstanceResolver;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Process\Process;
use Tests\Support\SharedFileLabRunner;
use Tests\Support\UsesRealRedis;
use Tests\TestCase;

/**
 * D3. Two real PHP processes (not forks: no shared database socket) run LaunchLab for the same instance at the
 * same time against real Redis and MySQL. Data is committed, so this test cleans up after itself instead of using
 * RefreshDatabase.
 */
#[Group('redis')]
class ConcurrentLaunchTest extends TestCase
{
    use UsesRealRedis;

    private string $stateDir;

    private ?User $user = null;

    private ?Lab $lab = null;

    private bool $createdLab = false;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', ['--force' => true]);
        $this->useRealRedis();

        $this->stateDir = sys_get_temp_dir().'/appsec-launch-'.bin2hex(random_bytes(4));
        mkdir($this->stateDir);
    }

    protected function tearDown(): void
    {
        $this->user?->delete();
        if ($this->createdLab) {
            $this->lab?->delete();
        }
        $this->releaseRealRedis();
        foreach (glob($this->stateDir.'/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->stateDir);

        parent::tearDown();
    }

    /** @return array<string, string> */
    private function workerEnvironment(): array
    {
        $db = config('database.connections.mysql');

        return $this->redisEnvironment() + [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'mysql',
            'DB_URL' => '',
            'DB_HOST' => (string) $db['host'],
            'DB_PORT' => (string) $db['port'],
            'DB_DATABASE' => (string) $db['database'],
            'DB_USERNAME' => (string) $db['username'],
            'DB_PASSWORD' => (string) $db['password'],
            'SESSION_DRIVER' => 'array',
        ];
    }

    public function test_concurrent_launches_start_instance_once(): void
    {
        $this->assertSame('testing', config('database.connections.mysql.database'), 'Refusing to run against a non-test database.');

        $this->user = User::factory()->create();
        $this->lab = Lab::query()->where('slug', 'osci')->first();
        if ($this->lab === null) {
            $this->lab = Lab::query()->create(['slug' => 'osci', 'name' => 'OS Command Injection', 'category' => 'backend', 'sort' => 1]);
            $this->createdLab = true;
        }
        $project = app(InstanceResolver::class)->project($this->user, $this->lab);
        $flagFile = app(FlagService::class)->flagFile($project, $this->lab);

        $worker = fn (): Process => new Process(
            [PHP_BINARY, __DIR__.'/launch_worker.php', (string) $this->user->id, (string) $this->lab->id, $this->stateDir],
            base_path(),
            $this->workerEnvironment(),
            timeout: 90,
        );

        // Double click / two workers picking the same job: start both before either can finish.
        [$first, $second] = [$worker(), $worker()];
        $first->start();
        $second->start();
        $first->wait();
        $second->wait();

        $output = "first:\n".$first->getOutput().$first->getErrorOutput()."\nsecond:\n".$second->getOutput().$second->getErrorOutput();
        $this->assertTrue($first->isSuccessful(), 'First worker failed: '.$output);
        $this->assertTrue($second->isSuccessful(), 'Second worker failed: '.$output);

        $runner = new SharedFileLabRunner($this->stateDir);
        $this->assertCount(1, $runner->upLog(), "The instance was started more than once:\n".implode("\n", $runner->upLog()));
        $this->assertTrue($runner->isRunning($project));

        $this->assertSame('ready', Cache::get('lab:'.$project.':status'));
        $this->assertSame(1, $this->user->labs()->where('labs.id', $this->lab->id)->count());

        // One flag was issued, and it is the one the instance holds.
        $flag = trim((string) file_get_contents($flagFile));
        @unlink($flagFile);
        $this->assertTrue(app(FlagService::class)->verify($project, $flag));
    }
}
