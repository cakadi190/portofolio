<?php

namespace App\Http\Controllers\Auth;

use App\Enums\SocialiteProvider;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Socialite\ConfigResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;

/**
 * Log in or register a user through a Socialite OAuth provider's consent
 * screen.
 *
 * Whether a provider is currently enabled, and which credentials it
 * redirects with, are both administrator-managed and resolved dynamically
 * through {@see ConfigResolver} rather than
 * hardcoded here.
 */
class SocialiteController extends Controller
{
    public function __construct(private readonly ConfigResolver $socialiteConfig) {}

    /**
     * Redirect to the given provider's OAuth consent screen.
     *
     * Returns a 404 for a provider an administrator has not enabled, before
     * any credentials for it are resolved or exposed.
     */
    public function redirect(SocialiteProvider $provider)
    {
        abort_unless($this->socialiteConfig->isActive($provider), 404);

        $this->socialiteConfig->apply($provider);

        return Socialite::driver($provider->value)->redirect();
    }

    /**
     * Handle the OAuth provider's callback: log the matching user in, or
     * register a new passwordless account from the provider's profile when
     * no account exists for the email yet.
     */
    public function callback(SocialiteProvider $provider): RedirectResponse
    {
        abort_unless($this->socialiteConfig->isActive($provider), 404);

        $this->socialiteConfig->apply($provider);

        try {
            $socialiteUser = Socialite::driver($provider->value)->user();
        } catch (\Throwable) {
            return redirect()->route('login')->with('error', 'Gagal masuk dengan '.$provider->label().'. Silakan coba lagi.');
        }

        $user = $this->findOrCreateUser($provider, $socialiteUser);

        Auth::login($user);

        request()->session()->regenerate();

        request()->merge([Fortify::username() => $user->email]);

        return redirect()->route('dashboard');
    }

    /**
     * Find the user matching the provider account, or register a new
     * passwordless account from its profile. OAuth providers verify the
     * email themselves, so the account is created pre-verified.
     *
     * Matches first by the provider's linked identifier (see
     * {@see User::socialProviderId()}), falling back to email for an
     * account that predates the provider being linked. Either way, the
     * provider identifier is (re)persisted on the matched user so future
     * logins resolve by ID alone.
     */
    protected function findOrCreateUser(SocialiteProvider $provider, SocialiteUserContract $socialiteUser): User
    {
        $providerId = (string) $socialiteUser->getId();
        $email = Str::lower((string) $socialiteUser->getEmail());

        $user = User::query()
            ->whereJsonContains('social_providers', ['id' => $provider->value, 'value' => $providerId])
            ->first()
            ?? User::query()->where('email', $email)->first();

        if ($user) {
            if ($user->socialProviderId($provider) !== $providerId) {
                $user->setSocialProviderId($provider, $providerId);
            }

            return $user;
        }

        $user = User::create([
            'name' => trim((string) $socialiteUser->getName()) ?: (string) $socialiteUser->getNickname(),
            'email' => $email,
            'avatar' => $socialiteUser->getAvatar(),
            'password' => null,
            'email_verified_at' => now(),
        ]);

        $user->setSocialProviderId($provider, $providerId);

        return $user;
    }
}
