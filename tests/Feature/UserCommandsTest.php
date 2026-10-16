<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_promote_makes_the_user_an_admin(): void
    {
        $user = User::factory()->create(['email' => 'teacher@example.com']);
        $other = User::factory()->create();

        $this->artisan('user:promote', ['email' => 'teacher@example.com'])->assertExitCode(0);

        $this->assertSame('admin', $user->fresh()->role);
        $this->assertSame('user', $other->fresh()->role);
        $this->artisan('user:promote', ['email' => 'teacher@example.com'])->assertExitCode(0);
        $this->assertSame('admin', $user->fresh()->role);
    }

    public function test_demote_makes_the_user_a_regular_user(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'teacher@example.com']);
        $otherAdmin = User::factory()->admin()->create();

        $this->artisan('user:demote', ['email' => 'teacher@example.com'])->assertExitCode(0);

        $this->assertSame('user', $admin->fresh()->role);
        $this->assertSame('admin', $otherAdmin->fresh()->role);
    }

    public function test_unknown_email_exits_with_1_and_changes_nothing(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->artisan('user:promote', ['email' => 'nobody@example.com'])->assertExitCode(1);
        $this->artisan('user:demote', ['email' => 'nobody@example.com'])->assertExitCode(1);

        $this->assertSame('admin', $admin->fresh()->role);
        $this->assertSame('user', $user->fresh()->role);
    }
}
