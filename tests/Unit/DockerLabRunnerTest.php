<?php

namespace Tests\Unit;

use App\Exceptions\LabRunnerException;
use App\Services\DockerLabRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * D11. Every process is created through the runner's factory seam, which is typed "array $command":
 * a shell string handed to it fails with a TypeError. The processes returned are harmless (true/false).
 */
class DockerLabRunnerTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array{command: array<mixed>, cwd: ?string, env: array<string, string>}> */
    private array $calls = [];

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
            'phase 3 name' => ['osci', 'lab_osci', 'lab-osci.labs.localhost'],
            'slug with dashes' => ['type-juggle', 'lab_type_juggle', 'lab-type-juggle.labs.localhost'],
            'per-user name' => ['oauth-ato', 'lab_oauth_ato_u7', 'lab-oauth-ato-u7.labs.localhost'],
        ];
    }

    #[DataProvider('instances')]
    public function test_up_builds_an_argument_array_and_passes_the_instance_environment(string $slug, string $project, string $host): void
    {
        $this->seedLabs();

        $this->runner()->up($slug, $project);

        $this->assertNoShellStrings();

        $up = array_values(array_filter($this->calls, fn (array $c): bool => in_array('up', $c['command'], true)));
        $this->assertNotEmpty($up, 'No "docker compose ... up" process.');
        $call = $up[0];
        $this->assertContains('compose', $call['command']);
        $this->assertStringContainsString("labs/{$slug}/docker", implode(' ', $call['command']).' '.$call['cwd'], 'The lab compose file is not used.');
        $this->assertSame($project, $call['env']['COMPOSE_PROJECT_NAME'] ?? null);
        $this->assertSame($host, $call['env']['LAB_HOST'] ?? null);
        $this->assertStringStartsWith('/', $call['env']['LAB_FLAG_FILE'] ?? '', 'LAB_FLAG_FILE must be an absolute path.');
    }

    public function test_down_and_is_running_use_argument_arrays_too(): void
    {
        $this->seedLabs();
        $runner = $this->runner();

        $runner->down('osci', 'lab_osci');
        $this->assertNoShellStrings();
        $down = array_values(array_filter($this->calls, fn (array $c): bool => in_array('down', $c['command'], true)));
        $this->assertNotEmpty($down, 'No "docker compose ... down" process.');
        $this->assertTrue(
            ($down[0]['env']['COMPOSE_PROJECT_NAME'] ?? null) === 'lab_osci' || in_array('lab_osci', $down[0]['command'], true),
            'down does not target the project.',
        );

        $this->calls = [];
        $this->assertIsBool($runner->isRunning('lab_osci'));
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
        $this->seedLabs();
        $runner = $this->runner();

        try {
            $runner->up($slug, 'lab_osci');
            $this->fail('up() accepted a slug that is not in the labs table.');
        } catch (LabRunnerException) {
            // expected
        }

        try {
            $runner->down($slug, 'lab_osci');
        } catch (LabRunnerException) {
            // rejecting is allowed, running a process is not
        }

        $this->assertSame([], $this->calls, 'A process was created for a rejected slug.');
    }

    public function test_up_failure_is_reported_as_lab_runner_exception(): void
    {
        $this->seedLabs();

        $this->expectException(LabRunnerException::class);

        $this->runner('false')->up('osci', 'lab_osci');
    }
}
