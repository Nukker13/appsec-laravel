<?php

namespace Tests\Unit;

use App\Models\Lab;
use App\Models\User;
use App\Services\InstanceResolver;
use Database\Seeders\LabSeeder;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Phase 6 format: lab_{slug with - as _}_u{user id}. */
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
        $user = new User;
        $user->id = 42;

        $project = app(InstanceResolver::class)->project($user, new Lab(['slug' => $slug]));

        $this->assertSame('lab_'.str_replace('-', '_', $slug).'_u42', $project);
    }

    public function test_project_name_is_stable_and_a_valid_compose_project_name(): void
    {
        $resolver = app(InstanceResolver::class);
        $user = new User;
        $user->id = 7;
        $other = new User;
        $other->id = 8;
        $lab = new Lab(['slug' => 'oauth-ato']);

        $this->assertSame('lab_oauth_ato_u7', $resolver->project($user, $lab));
        $this->assertSame('lab_oauth_ato_u8', $resolver->project($other, $lab));
        $this->assertSame($resolver->project($user, $lab), $resolver->project($user, $lab));
        $this->assertMatchesRegularExpression('/^[a-z0-9][a-z0-9_-]*$/', $resolver->project($user, $lab));
    }
}
