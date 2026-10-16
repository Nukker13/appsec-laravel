<?php

namespace Tests\Feature;

use Database\Seeders\LabSeeder;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

/**
 * Facts about labs/{slug}/ and the lab views that the platform relies on. No database needed: the list of labs
 * is LabSeeder::LABS, the source of truth.
 */
class LabDataInvariantsTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function slugs(): array
    {
        $slugs = [];
        foreach (LabSeeder::LABS as $lab) {
            $slugs[$lab['slug']] = [$lab['slug']];
        }

        return $slugs;
    }

    private function composeFile(string $slug): string
    {
        return rtrim((string) config('labs.path'), '/')."/{$slug}/docker/docker-compose.yml";
    }

    /** @return array<string, mixed> */
    private function compose(string $slug): array
    {
        $file = $this->composeFile($slug);
        $this->assertFileExists($file, "labs/{$slug}/docker/docker-compose.yml is missing.");

        $compose = Yaml::parseFile($file);
        $this->assertIsArray($compose);
        $this->assertIsArray($compose['services'] ?? null, "{$slug}: no services.");

        return $compose;
    }

    #[DataProvider('slugs')]
    public function test_every_seeded_lab_has_view_and_lab_folder_with_compose_file(string $slug): void
    {
        $this->assertFileExists(resource_path("views/labs/{$slug}.blade.php"), "Blade view for {$slug} is missing.");
        $this->assertDirectoryExists(rtrim((string) config('labs.path'), '/')."/{$slug}", "labs/{$slug}/ is missing.");
        $this->assertFileExists($this->composeFile($slug));
    }

    #[DataProvider('slugs')]
    public function test_no_lab_compose_file_mounts_docker_socket_or_defines_stopper(string $slug): void
    {
        $compose = $this->compose($slug);

        foreach (array_keys($compose['services']) as $service) {
            $this->assertStringNotContainsStringIgnoringCase('stopper', (string) $service, "{$slug}: service {$service} is the removed stopper.");
        }

        // Anywhere in the file: a volume (short or long syntax), a command, an environment value.
        $found = [];
        array_walk_recursive($compose, function (mixed $value, mixed $key) use (&$found): void {
            if (is_string($value) && str_contains($value, 'docker.sock')) {
                $found[] = $value;
            }
        });
        $this->assertSame([], $found, "{$slug}: docker.sock is referenced.");
    }

    #[DataProvider('slugs')]
    public function test_no_lab_service_has_container_name_or_published_ports(string $slug): void
    {
        foreach ($this->compose($slug)['services'] as $name => $service) {
            $this->assertIsArray($service);
            $this->assertArrayNotHasKey('container_name', $service, "{$slug}/{$name}: container_name makes two instances collide.");
            $this->assertArrayNotHasKey('ports', $service, "{$slug}/{$name}: host ports make two labs collide; route through Traefik.");
        }
    }

    #[DataProvider('slugs')]
    public function test_entry_service_is_routed_by_traefik_on_labs_proxy(string $slug): void
    {
        $compose = $this->compose($slug);
        $entry = $slug === 'ssrf' ? 'app' : 'nginx';

        $this->assertArrayHasKey($entry, $compose['services'], "{$slug}: entry service {$entry} is missing.");

        $network = $compose['networks']['labs_proxy'] ?? null;
        $this->assertNotNull($network, "{$slug}: labs_proxy is not declared.");
        $this->assertNotEmpty($network['external'] ?? null, "{$slug}: labs_proxy must be the external network.");

        foreach ($compose['services'] as $name => $service) {
            $networks = $service['networks'] ?? [];
            $joined = array_key_exists('labs_proxy', $networks) || in_array('labs_proxy', $networks, true);
            $this->assertSame($name === $entry, $joined, "{$slug}/{$name}: ".($name === $entry ? 'the entry service must join labs_proxy' : 'only the entry service may join labs_proxy'));
        }

        $labels = [];
        foreach ($compose['services'][$entry]['labels'] ?? [] as $key => $value) {
            if (is_int($key)) {
                [$key, $value] = array_pad(explode('=', (string) $value, 2), 2, '');
            }
            $labels[(string) $key] = (string) $value;
        }
        $rules = array_filter($labels, fn (string $value, string $key): bool => str_starts_with($key, 'traefik.') && str_contains($value, 'Host('), ARRAY_FILTER_USE_BOTH);

        $this->assertNotEmpty($rules, "{$slug}/{$entry}: no Traefik Host() rule label.");
        $this->assertStringContainsString('${LAB_HOST}', (string) array_values($rules)[0], "{$slug}/{$entry}: the Host() rule must use \${LAB_HOST}.");
    }
}
