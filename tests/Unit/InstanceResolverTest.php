<?php

namespace Tests\Unit;

use App\Models\Lab;
use App\Models\User;
use App\Services\InstanceResolver;
use Database\Seeders\LabSeeder;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Asserts the phase 3 format. Phase 6 changes this test together with InstanceResolver::project(). */
class InstanceResolverTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function slugs(): array
    {
        return array_column(array_map(fn (array $lab): array => [$lab['slug'], [$lab['slug']]], LabSeeder::LABS), 1, 0);
    }

    #[DataProvider('slugs')]
    public function test_project_name_format(string $slug): void
    {
        $project = app(InstanceResolver::class)->project(new User(['email' => 'a@example.com']), new Lab(['slug' => $slug]));

        $this->assertSame('lab_'.str_replace('-', '_', $slug), $project);
    }

    public function test_project_name_is_stable_and_a_valid_compose_project_name(): void
    {
        $resolver = app(InstanceResolver::class);
        $user = new User;
        $lab = new Lab(['slug' => 'oauth-ato']);

        $this->assertSame('lab_oauth_ato', $resolver->project($user, $lab));
        $this->assertSame($resolver->project($user, $lab), $resolver->project($user, $lab));
        $this->assertMatchesRegularExpression('/^[a-z0-9][a-z0-9_-]*$/', $resolver->project($user, $lab));
    }
}
