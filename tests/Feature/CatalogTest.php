<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\LabSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_rejects_unknown_category(): void
    {
        $this->seedLabs();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('labs.index', ['category' => 'mobile']))
            ->assertSessionHasErrors('category');

        $this->actingAs($user)->getJson(route('labs.index', ['category' => 'mobile']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('category');
    }

    public function test_catalog_filters_by_category(): void
    {
        $this->seedLabs();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('labs.index', ['category' => 'frontend']))
            ->assertOk()
            ->assertSeeText('DOM XSS')
            ->assertDontSeeText('OS Command Injection');

        $this->actingAs($user)->get(route('labs.index', ['category' => 'backend']))
            ->assertOk()
            ->assertSeeText('OS Command Injection')
            ->assertDontSeeText('DOM XSS');

        $this->actingAs($user)->get(route('labs.index'))
            ->assertOk()
            ->assertSeeText('DOM XSS')
            ->assertSeeText('OS Command Injection');
    }

    /** The catalog page of $user with everything that identifies the user or the request removed. */
    private function catalogOf(User $user): string
    {
        $html = $this->actingAs($user)->get(route('labs.index'))->assertOk()->getContent();
        $html = str_replace([e($user->name), $user->name, e($user->email), $user->email], 'USER', (string) $html);

        return (string) preg_replace('/(csrf-token"\s+content=|name="_token"\s+value=)"[^"]*"/', '$1"TOKEN"', $html);
    }

    public function test_catalog_shows_status_of_current_user_only(): void
    {
        $this->seedLabs();
        $solver = User::factory()->create(['name' => 'Solver Sam']);
        $starter = User::factory()->create(['name' => 'Starter Stan']);
        $newcomer = User::factory()->create(['name' => 'Newcomer Nik']);

        $this->progress($solver, $this->lab('osci'), now()->subHour(), 'completed', now()->subMinutes(30));
        $this->progress($starter, $this->lab('dom-xss'), now()->subMinutes(5));

        $forSolver = $this->catalogOf($solver);
        $forStarter = $this->catalogOf($starter);
        $forNewcomer = $this->catalogOf($newcomer);

        // Progress is shown per user. If completion leaked to everybody (D1), the newcomer's page would equal the solver's.
        $this->assertNotSame($forNewcomer, $forSolver, 'A user without progress sees the same catalog as a user who completed a lab.');
        $this->assertNotSame($forNewcomer, $forStarter, 'A started lab is not reflected in the catalog.');
        $this->assertNotSame($forSolver, $forStarter);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function seededLabs(): array
    {
        $labs = [];
        foreach (LabSeeder::LABS as $lab) {
            $labs[$lab['slug']] = [$lab['slug'], $lab['name']];
        }

        return $labs;
    }

    #[DataProvider('seededLabs')]
    public function test_lab_page_renders_for_every_seeded_lab(string $slug, string $name): void
    {
        $this->seedLabs();

        $this->actingAs(User::factory()->create())->get(route('labs.show', $slug))
            ->assertOk()
            ->assertSeeText($name);
    }
}
