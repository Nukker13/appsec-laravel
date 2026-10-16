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
 * up() copies the lab to {labs.instances_path}/{project}/lab and runs compose there; down() removes it again.
 * Both set env COMPOSE_PROJECT_NAME={project}, LAB_HOST={project with _ replaced by -}.labs.localhost and LAB_FLAG_FILE.
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
     * Copies labs/{slug} to the instance directory when absent, then runs compose from the copy.
     *
     * @throws LabRunnerException
     */
    public function up(string $slug, string $project): void
    {
        $lab = $this->lab($slug);
        $copy = $this->instanceCopy($project);

        $this->copyLab($slug, $copy);

        $this->run(
            ['docker', 'compose', '-f', $copy.'/docker/docker-compose.yml', '-p', $project, 'up', '-d', '--build', '--wait'],
            $copy.'/docker',
            $this->environment($project, $lab),
        );
    }

    /**
     * Idempotent. Runs compose down from the instance copy (from the original compose file when the copy is gone),
     * then deletes the instance directory.
     *
     * @throws LabRunnerException
     */
    public function down(string $slug, string $project): void
    {
        $lab = $this->lab($slug);
        $copy = $this->instanceCopy($project);

        $directory = is_file($copy.'/docker/docker-compose.yml') ? $copy : $this->labDirectory($slug);

        $this->run(
            ['docker', 'compose', '-f', $directory.'/docker/docker-compose.yml', '-p', $project, 'down', '-v', '--remove-orphans', '--rmi', 'local'],
            $directory.'/docker',
            $this->environment($project, $lab),
        );

        (new Filesystem)->deleteDirectory($this->instanceDirectory($project));
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

    private function instanceDirectory(string $project): string
    {
        return rtrim(config()->string('labs.instances_path'), '/').'/'.$project;
    }

    private function instanceCopy(string $project): string
    {
        return $this->instanceDirectory($project).'/lab';
    }

    /**
     * Copy labs/{slug} to $copy unless it is already there. Runtime state (contents of docker/storage and storage) is
     * not copied, the directories are. An existing flag.txt (issued before up) is never overwritten.
     */
    private function copyLab(string $slug, string $copy): void
    {
        $filesystem = new Filesystem;

        if (is_file($copy.'/docker/docker-compose.yml')) {
            return;
        }

        $source = $this->labDirectory($slug);
        if (! $filesystem->isDirectory($source)) {
            throw new LabRunnerException('Lab directory is missing.');
        }

        $filesystem->ensureDirectoryExists($copy);

        foreach ($filesystem->allFiles($source, true) as $file) {
            $relative = $file->getRelativePathname();

            if (str_starts_with($relative, 'docker/storage/') || str_starts_with($relative, 'storage/')) {
                continue;
            }

            $target = $copy.'/'.$relative;
            if ($relative === 'flag.txt' && is_file($target)) {
                continue;
            }

            $filesystem->ensureDirectoryExists(dirname($target));
            if (! $filesystem->copy($file->getPathname(), $target)) {
                throw new LabRunnerException('Could not copy the lab files.');
            }
        }

        $filesystem->ensureDirectoryExists($copy.'/docker/storage');
        $filesystem->ensureDirectoryExists($copy.'/storage');
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
    private function run(array $command, string $cwd, array $env): void
    {
        $process = $this->process($command, $cwd, $env);
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

    /**
     * @param  list<string>  $command
     * @param  array<string, string>  $env
     */
    protected function process(array $command, ?string $cwd = null, array $env = []): Process
    {
        return ($this->processFactory)($command, $cwd, $env);
    }
}
