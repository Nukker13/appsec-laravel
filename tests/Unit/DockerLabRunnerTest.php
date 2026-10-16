<?php

namespace Tests\Unit;

use App\Exceptions\LabRunnerException;
use App\Services\DockerLabRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * D11 and phase 6. Every process is created through the runner's factory seam, which is typed "array $command":
 * a shell string handed to it fails with a TypeError. The processes returned are harmless (true/false).
 * labs.path points at a temporary directory with tiny fake labs, labs.instances_path (see TestCase) at another one.
 */
class DockerLabRunnerTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array{command: array<mixed>, cwd: ?string, env: array<string, string>}> */
    private array $calls = [];

    private string $labsDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->labsDir = sys_get_temp_dir().'/appsec-labs-'.bin2hex(random_bytes(6));
        foreach (['osci', 'type-juggle', 'oauth-ato'] as $slug) {
            $this->fakeLab($slug);
        }
        config()->set('labs.path', $this->labsDir);
        $this->seedLabs();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->labsDir);

        parent::tearDown();
    }

    private function fakeLab(string $slug): void
    {
        $base = "{$this->labsDir}/{$slug}";
        foreach (["{$base}/docker/storage/mysql", "{$base}/docker/storage/tmp", "{$base}/storage", "{$base}/src"] as $dir) {
            mkdir($dir, 0777, true);
        }
        file_put_contents("{$base}/docker/docker-compose.yml", "services:\n  nginx:\n    image: nginx\n");
        file_put_contents("{$base}/docker/Dockerfile", "FROM scratch\n");
        file_put_contents("{$base}/docker/storage/.gitkeep", '');
        file_put_contents("{$base}/docker/storage/mysql/ibdata1", 'DATABASE-STATE');
        file_put_contents("{$base}/docker/storage/tmp/session", 'TMP-STATE');
        file_put_contents("{$base}/storage/upload.bin", 'UPLOAD-STATE');
        file_put_contents("{$base}/src/index.php", '<?php echo 1;');
        file_put_contents("{$base}/flag.txt", 'SOURCE-FLAG');
    }

    /** @return array<string, string> relative path => md5, for every file below $dir */
    private function snapshot(string $dir): array
    {
        $files = [];
        foreach (File::allFiles($dir, true) as $file) {
            $files[$file->getRelativePathname()] = md5_file($file->getPathname());
        }
        ksort($files);

        return $files;
    }

    private function instanceCopy(string $project): string
    {
        return "{$this->instancesDir}/{$project}/lab";
    }

    private function runner(string $binary = 'true'): DockerLabRunner
    {
        $this->calls = [];

        return new DockerLabRunner(function (array $command, ?string $cwd, array $env) use ($binary): Process {
            $this->calls[] = ['command' => $command, 'cwd' => $cwd, 'env' => $env];

            return new Process([$binary]);
        });
    }

    private function assertNoShellStrings(): void
    {
        $this->assertNotEmpty($this->calls, 'No process was created through the factory.');

        foreach ($this->calls as $call) {
            $this->assertGreaterThan(1, count($call['command']), 'A single string looks like a shell command line.');
            foreach ($call['command'] as $part) {
                $this->assertIsString($part);
                $this->assertNotSame('', $part);
                $this->assertDoesNotMatchRegularExpression('/[;&|`<>]/', $part, 'Shell metacharacter in an argument.');
            }
            $this->assertDoesNotMatchRegularExpression('/\s/', $call['command'][0], 'The executable contains whitespace.');
        }
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function instances(): array
    {
        return [
            'osci, user 3' => ['osci', 'lab_osci_u3', 'lab-osci-u3.labs.localhost'],
            'slug with dashes' => ['type-juggle', 'lab_type_juggle_u12', 'lab-type-juggle-u12.labs.localhost'],
            'oauth-ato, user 7' => ['oauth-ato', 'lab_oauth_ato_u7', 'lab-oauth-ato-u7.labs.localhost'],
        ];
    }

    #[DataProvider('instances')]
    public function test_up_runs_compose_from_the_instance_copy_with_an_argument_array(string $slug, string $project, string $host): void
    {
        $this->runner()->up($slug, $project);

        $this->assertNoShellStrings();

        $copy = $this->instanceCopy($project);
        $up = array_values(array_filter($this->calls, fn (array $c): bool => in_array('up', $c['command'], true)));
        $this->assertNotEmpty($up, 'No "docker compose ... up" process.');
        $call = $up[0];
        $this->assertContains('compose', $call['command']);
        $this->assertContains($copy.'/docker/docker-compose.yml', $call['command'], 'Compose must use the compose file of the instance copy.');
        $this->assertSame($copy.'/docker', $call['cwd'], 'Compose must run from the instance copy.');
        $this->assertStringNotContainsString($this->labsDir, implode(' ', $call['command']).' '.$call['cwd'].' '.implode(' ', $call['env']), 'The shared lab directory is used at runtime.');
        $this->assertSame($project, $call['env']['COMPOSE_PROJECT_NAME'] ?? null);
        $this->assertSame($host, $call['env']['LAB_HOST'] ?? null);
        $this->assertSame($copy.'/flag.txt', $call['env']['LAB_FLAG_FILE'] ?? null);
    }

    public function test_runner_up_copies_lab_and_down_removes_instance_dir(): void
    {
        $source = "{$this->labsDir}/osci";
        $before = $this->snapshot($source);
        $runner = $this->runner();
        $copy = $this->instanceCopy('lab_osci_u3');

        $runner->up('osci', 'lab_osci_u3');

        $this->assertFileExists($copy.'/docker/docker-compose.yml');
        $this->assertFileExists($copy.'/docker/Dockerfile');
        $this->assertFileExists($copy.'/src/index.php');
        // Runtime state is never copied, but the directories the compose file mounts exist.
        $this->assertDirectoryExists($copy.'/docker/storage');
        $this->assertDirectoryExists($copy.'/storage');
        $this->assertFileDoesNotExist($copy.'/docker/storage/mysql/ibdata1');
        $this->assertFileDoesNotExist($copy.'/docker/storage/tmp/session');
        $this->assertFileDoesNotExist($copy.'/storage/upload.bin');
        $this->assertSame($before, $this->snapshot($source), 'labs/{slug} was modified by up().');

        // Another user's instance is a separate copy.
        $runner->up('osci', 'lab_osci_u4');
        $this->assertDirectoryExists($this->instanceCopy('lab_osci_u4'));
        $this->assertNotSame($copy, $this->instanceCopy('lab_osci_u4'));

        $runner->down('osci', 'lab_osci_u3');

        $this->assertDirectoryDoesNotExist("{$this->instancesDir}/lab_osci_u3", 'down() left the instance directory behind.');
        $this->assertDirectoryExists($this->instanceCopy('lab_osci_u4'), 'down() removed another instance.');
        $this->assertSame($before, $this->snapshot($source), 'labs/{slug} was modified by down().');

        // Idempotent: the directory is already gone.
        $runner->down('osci', 'lab_osci_u3');
        $this->assertDirectoryDoesNotExist("{$this->instancesDir}/lab_osci_u3");
    }

    public function test_up_keeps_a_flag_that_was_issued_before_the_copy_existed(): void
    {
        // LaunchLab issues the flag first, which creates {instance}/lab/flag.txt; up() must still copy the lab.
        $copy = $this->instanceCopy('lab_osci_u3');
        mkdir($copy, 0777, true);
        file_put_contents($copy.'/flag.txt', 'ISSUED-FLAG');

        $this->runner()->up('osci', 'lab_osci_u3');

        $this->assertSame('ISSUED-FLAG', file_get_contents($copy.'/flag.txt'), 'The copy overwrote the issued flag with the lab\'s own flag.txt.');
        $this->assertFileExists($copy.'/docker/docker-compose.yml', 'The lab was not copied because the instance directory already existed.');
    }

    public function test_down_removes_volumes_and_local_images_from_the_instance_copy(): void
    {
        $runner = $this->runner();
        $copy = $this->instanceCopy('lab_osci_u3');
        $runner->up('osci', 'lab_osci_u3');

        $runner->down('osci', 'lab_osci_u3');

        $this->assertNoShellStrings();
        $down = array_values(array_filter($this->calls, fn (array $c): bool => in_array('down', $c['command'], true)));
        $this->assertNotEmpty($down, 'No "docker compose ... down" process.');
        $command = $down[0]['command'];
        $this->assertContains('-v', $command);
        $this->assertContains('--remove-orphans', $command);
        $at = array_search('--rmi', $command, true);
        $this->assertNotFalse($at, 'down must remove images with --rmi.');
        $this->assertSame('local', $command[$at + 1] ?? null);
        $this->assertContains($copy.'/docker/docker-compose.yml', $command);
        $this->assertSame($copy.'/docker', $down[0]['cwd']);
        $this->assertTrue(
            ($down[0]['env']['COMPOSE_PROJECT_NAME'] ?? null) === 'lab_osci_u3' || in_array('lab_osci_u3', $command, true),
            'down does not target the project.',
        );

        $this->calls = [];
        $this->assertIsBool($runner->isRunning('lab_osci_u3'));
        $this->assertNoShellStrings();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function foreignSlugs(): array
    {
        return [
            'unknown lab' => ['ghost'],
            'shell injection' => ['osci; touch /tmp/pwned'],
            'command substitution' => ['$(id)'],
            'path traversal' => ['../osci'],
            'empty' => [''],
        ];
    }

    #[DataProvider('foreignSlugs')]
    public function test_a_slug_that_is_not_in_the_labs_table_is_rejected_before_any_process_runs(string $slug): void
    {
        $runner = $this->runner();

        try {
            $runner->up($slug, 'lab_osci_u3');
            $this->fail('up() accepted a slug that is not in the labs table.');
        } catch (LabRunnerException) {
            // expected
        }

        try {
            $runner->down($slug, 'lab_osci_u3');
        } catch (LabRunnerException) {
            // rejecting is allowed, running a process is not
        }

        $this->assertSame([], $this->calls, 'A process was created for a rejected slug.');
        $this->assertSame([], glob($this->instancesDir.'/*') ?: [], 'An instance directory was created for a rejected slug.');
    }

    public function test_up_failure_is_reported_as_lab_runner_exception(): void
    {
        $this->expectException(LabRunnerException::class);

        $this->runner('false')->up('osci', 'lab_osci_u3');
    }
}
