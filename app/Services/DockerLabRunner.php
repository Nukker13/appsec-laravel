<?php

namespace App\Services;

use App\Contracts\LabRunner;
use App\Exceptions\LabRunnerException;
use App\Models\Lab;
use Closure;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ExceptionInterface as ProcessExceptionInterface;
use Symfony\Component\Process\Process;

/**
 * Drives docker compose through Symfony Process with an argument array. Rejects slugs that are not in the labs table.
 *
 * up() sets env COMPOSE_PROJECT_NAME={project}, LAB_HOST={project with _ replaced by -}.labs.localhost and LAB_FLAG_FILE.
 */
class DockerLabRunner implements LabRunner
{
    /** @var Closure(list<string>, ?string, array<string, string>): Process */
    private Closure $processFactory;

    /**
     * @param  (Closure(list<string>, ?string, array<string, string>): Process)|null  $processFactory  test seam; every process is created through it
     */
    public function __construct(?Closure $processFactory = null)
    {
        $this->processFactory = $processFactory
            ?? fn (array $command, ?string $cwd, array $env): Process => new Process($command, $cwd, $env);
    }

    /** Seconds before a compose call is killed; below the 180 s timeout of the queue job. */
    private const TIMEOUT = 150;

    /**
     * @throws LabRunnerException
     */
    public function up(string $slug, string $project): void
    {
        $lab = $this->lab($slug);

        $this->run(
            ['docker', 'compose', '-f', $this->composeFile($slug), '-p', $project, 'up', '-d', '--build', '--wait'],
            $slug,
            $this->environment($project, $lab),
        );
    }

    /**
     * @throws LabRunnerException
     */
    public function down(string $slug, string $project): void
    {
        $lab = $this->lab($slug);

        $this->run(
            ['docker', 'compose', '-f', $this->composeFile($slug), '-p', $project, 'down', '-v', '--remove-orphans'],
            $slug,
            $this->environment($project, $lab),
        );

        $base = $this->labDirectory($slug);
        $filesystem = new Filesystem;
        foreach ([$base.'/docker/storage', $base.'/storage'] as $directory) {
            $this->emptyDirectory($filesystem, $directory);
        }
    }

    public function isRunning(string $project): bool
    {
        $process = $this->process(['docker', 'ps', '--filter', 'label=com.docker.compose.project='.$project, '-q']);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new LabRunnerException('docker ps failed: '.trim($process->getErrorOutput()));
        }

        return trim($process->getOutput()) !== '';
    }

    private function lab(string $slug): Lab
    {
        $lab = $slug === '' ? null : Lab::query()->where('slug', $slug)->first();

        if ($lab === null) {
            throw new LabRunnerException('Unknown lab.');
        }

        return $lab;
    }

    private function labDirectory(string $slug): string
    {
        return rtrim(config()->string('labs.path'), '/').'/'.$slug;
    }

    private function composeFile(string $slug): string
    {
        return $this->labDirectory($slug).'/docker/docker-compose.yml';
    }

    /**
     * @return array<string, string>
     */
    private function environment(string $project, Lab $lab): array
    {
        return [
            'COMPOSE_PROJECT_NAME' => $project,
            'LAB_HOST' => str_replace('_', '-', $project).'.'.config()->string('labs.host_suffix'),
            'LAB_FLAG_FILE' => app(FlagService::class)->flagFile($project, $lab),
        ];
    }

    /**
     * @param  list<string>  $command
     * @param  array<string, string>  $env
     *
     * @throws LabRunnerException
     */
    private function run(array $command, string $slug, array $env): void
    {
        $process = $this->process($command, $this->labDirectory($slug).'/docker', $env);
        $process->setTimeout(self::TIMEOUT);

        try {
            $process->run();
        } catch (ProcessExceptionInterface $e) {
            throw new LabRunnerException('docker compose could not run: '.$e->getMessage(), 0, $e);
        }

        if (! $process->isSuccessful()) {
            throw new LabRunnerException('docker compose failed (exit '.$process->getExitCode().'): '.trim($process->getErrorOutput()));
        }
    }

    /** Remove everything below $directory except .gitkeep; the directory itself stays. */
    private function emptyDirectory(Filesystem $filesystem, string $directory): void
    {
        if (! $filesystem->isDirectory($directory)) {
            return;
        }

        foreach ($filesystem->directories($directory) as $child) {
            $filesystem->deleteDirectory($child);
        }

        foreach ($filesystem->files($directory, true) as $file) {
            if ($file->getFilename() !== '.gitkeep') {
                $filesystem->delete($file->getPathname());
            }
        }
    }

    /**
     * @param  list<string>  $command
     * @param  array<string, string>  $env
     */
    protected function process(array $command, ?string $cwd = null, array $env = []): Process
    {
        return ($this->processFactory)($command, $cwd, $env);
    }
}
