<?php

namespace Tests\Feature;

use App\Models\User;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request as PsrRequest;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Throwable;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function protectedRoutes(): array
    {
        return [
            'logout' => ['POST', '/logout'],
            'labs.index' => ['GET', '/labs'],
            'labs.show' => ['GET', '/labs/osci'],
            'labs.launch' => ['POST', '/labs/osci/launch'],
            'labs.status' => ['GET', '/labs/osci/status'],
            'labs.hints' => ['GET', '/labs/osci/hints'],
            'labs.flag' => ['POST', '/labs/osci/flag'],
            'admin.progress' => ['GET', '/admin'],
            'admin.labs' => ['GET', '/admin/labs'],
            'admin.users.show' => ['GET', '/admin/users/1'],
            'admin.progress.reset' => ['POST', '/admin/users/1/labs/osci/reset'],
            'admin.progress.complete' => ['POST', '/admin/users/1/labs/osci/complete'],
        ];
    }

    #[DataProvider('protectedRoutes')]
    public function test_guest_is_redirected_from_every_protected_route(string $method, string $uri): void
    {
        $this->seedLabs();

        $this->call($method, $uri, ['flag' => 'x', 'reason' => 'x'])->assertRedirectToRoute('home');
        $this->assertGuest();
    }

    /** Mock the Google provider; tolerant of stateless()/scopes()/with() chains the implementation may add. */
    private function googleReturns(SocialiteUser|Throwable $result): void
    {
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('stateless', 'scopes', 'with', 'setScopes')->zeroOrMoreTimes()->andReturnSelf();
        $expectation = $provider->shouldReceive('user')->zeroOrMoreTimes();
        $result instanceof Throwable ? $expectation->andThrow($result) : $expectation->andReturn($result);

        Socialite::shouldReceive('driver')->zeroOrMoreTimes()->andReturn($provider);
    }

    private function googleUser(string $id, string $email, string $name): SocialiteUser
    {
        return (new SocialiteUser)->map(['id' => $id, 'name' => $name, 'email' => $email, 'nickname' => null, 'avatar' => null]);
    }

    public function test_google_callback_creates_user_with_user_role(): void
    {
        $this->googleReturns($this->googleUser('1234567890', 'student@example.com', 'Stu Dent'));

        $this->get(route('auth.callback', ['code' => 'abc', 'state' => 'xyz']))->assertRedirectToRoute('labs.index');

        $user = User::query()->where('google_id', '1234567890')->firstOrFail();
        $this->assertSame('student@example.com', $user->email);
        $this->assertSame('Stu Dent', $user->name);
        $this->assertSame('user', $user->role);
        $this->assertFalse($user->isAdmin());
        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::query()->count());
    }

    public function test_admin_role_survives_relogin(): void
    {
        $admin = User::factory()->admin()->create(['google_id' => '777', 'email' => 'boss@example.com', 'name' => 'Boss']);
        $this->googleReturns($this->googleUser('777', 'boss@example.com', 'Boss Renamed'));

        $this->get(route('auth.callback', ['code' => 'abc', 'state' => 'xyz']))->assertRedirectToRoute('labs.index');

        $this->assertAuthenticatedAs($admin);
        $this->assertSame('admin', $admin->fresh()->role);
        $this->assertSame(1, User::query()->count());
    }

    /**
     * @return array<string, array{Throwable}>
     */
    public static function socialiteFailures(): array
    {
        return [
            'invalid state' => [new InvalidStateException],
            'token endpoint rejected the missing code' => [new ClientException(
                'invalid_request',
                new PsrRequest('POST', 'https://oauth2.googleapis.com/token'),
                new PsrResponse(400),
            )],
        ];
    }

    #[DataProvider('socialiteFailures')]
    public function test_callback_without_code_redirects_with_error(Throwable $failure): void
    {
        $this->googleReturns($failure);

        // No "code" query parameter at all (also the shape of ?error=access_denied).
        $this->get(route('auth.callback', ['error' => 'access_denied']))
            ->assertRedirectToRoute('home')
            ->assertSessionHas('error');

        $this->assertGuest();
        $this->assertSame(0, User::query()->count());
    }

    public function test_logout_requires_csrf_token(): void
    {
        // Laravel skips CSRF checks while the app environment is "testing". Report any other environment
        // so the real middleware (PreventRequestForgery) is enforced, as it is in production.
        $this->app->detectEnvironment(fn (): string => 'local');
        $this->assertFalse($this->app->runningUnitTests());

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'))->assertStatus(419);
        $this->assertAuthenticatedAs($user);

        $this->actingAs($user)->withSession(['_token' => 'valid-token'])
            ->post(route('logout'), ['_token' => 'valid-token'])
            ->assertRedirectToRoute('home');
        $this->assertGuest();
    }
}
