<?php

namespace Database\Seeders;

use App\Models\Lab;
use Illuminate\Database\Seeder;

/**
 * Source of truth for the lab catalog (docs/MIGRATION-PLAN.md, Phase 0). Idempotent: upserts by slug.
 */
class LabSeeder extends Seeder
{
    /** @var list<array{sort: int, slug: string, name: string, category: string}> */
    public const LABS = [
        ['sort' => 1, 'slug' => 'osci', 'name' => 'OS Command Injection', 'category' => 'backend'],
        ['sort' => 2, 'slug' => 'dom-xss', 'name' => 'DOM XSS', 'category' => 'frontend'],
        ['sort' => 3, 'slug' => 'reflect-xss', 'name' => 'Reflected XSS', 'category' => 'frontend'],
        ['sort' => 4, 'slug' => 'stored-xss', 'name' => 'Stored XSS', 'category' => 'frontend'],
        ['sort' => 5, 'slug' => 'ssrf', 'name' => 'Server-Side Request Forgery', 'category' => 'backend'],
        ['sort' => 6, 'slug' => 'oauth-ato', 'name' => 'OAuth Account Takeover', 'category' => 'backend'],
        ['sort' => 7, 'slug' => 'rbac', 'name' => 'Incorrect RBAC', 'category' => 'backend'],
        ['sort' => 8, 'slug' => 'path-trav', 'name' => 'Path Traversal', 'category' => 'backend'],
        ['sort' => 9, 'slug' => 'type-juggle', 'name' => 'Type Juggling', 'category' => 'backend'],
        ['sort' => 10, 'slug' => 'insec-deser', 'name' => 'Insecure Deserialization', 'category' => 'backend'],
    ];

    public function run(): void
    {
        foreach (self::LABS as $lab) {
            Lab::query()->updateOrCreate(
                ['slug' => $lab['slug']],
                ['name' => $lab['name'], 'category' => $lab['category'], 'sort' => $lab['sort']],
            );
        }
    }
}
