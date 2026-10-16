<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Throwable;

class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        /** @var GoogleProvider $provider */
        $provider = Socialite::driver('google');

        return $provider->scopes(['email', 'profile'])->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            report($e);

            return $this->failed('Google sign-in failed. Please try again.');
        }

        $googleId = (string) $google->getId();
        $email = (string) $google->getEmail();

        if ($googleId === '' || $email === '') {
            return $this->failed('Google did not return an account id and email.');
        }

        // Known email bound to a different Google account: refuse rather than take over or crash on the unique index.
        $conflict = User::query()->where('email', $email)->where('google_id', '!=', $googleId)->exists();
        if ($conflict) {
            return $this->failed('This email is already linked to a different Google account. Ask an admin for help.');
        }

        // D6: only google_id, email, name and last_login_at are written. "role" is never touched.
        $user = User::query()->firstOrNew(['google_id' => $googleId]);
        $user->email = $email;
        $user->name = (string) ($google->getName() ?: $email);
        $user->last_login_at = now();
        $user->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('labs.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function failed(string $message): RedirectResponse
    {
        return redirect()->route('home')->with('error', $message);
    }
}
