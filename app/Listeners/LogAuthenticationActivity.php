<?php

namespace App\Listeners;

use App\Models\Activity;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Fortify\Events\PasswordUpdatedViaController;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;

/**
 * Translate authentication and security events into audit entries.
 *
 * Failed attempts are rate limited per IP address and identity so a
 * brute-force run cannot flood the log; suppressed attempts are not stored.
 */
class LogAuthenticationActivity
{
    private const int FAILED_LOG_WINDOW_SECONDS = 60;

    public const string LOG_NAME = 'auth';

    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(object $event): void
    {
        match (true) {
            $event instanceof Login => $this->record('login', 'Login berhasil', $event->user, ['guard' => $event->guard, 'remember' => $event->remember]),
            $event instanceof Logout => $this->record('logout', 'Logout', $event->user, ['guard' => $event->guard]),
            $event instanceof Failed => $this->recordFailedLogin($event),
            $event instanceof Lockout => $this->record('lockout', 'Login dikunci karena terlalu banyak percobaan', null, [], Activity::OUTCOME_FAILURE),
            $event instanceof Registered => $this->record('registered', 'Akun baru terdaftar', $event->user),
            $event instanceof PasswordReset => $this->record('password_reset', 'Kata sandi diatur ulang', $event->user),
            $event instanceof PasswordResetLinkSent => $this->record('password_reset_link_sent', 'Tautan atur ulang kata sandi dikirim', null),
            $event instanceof Verified => $this->record('email_verified', 'Email terverifikasi', $event->user),
            $event instanceof PasswordUpdatedViaController => $this->record('password_updated', 'Kata sandi diperbarui', $event->user),
            $event instanceof TwoFactorAuthenticationEnabled => $this->record('two_factor_enabled', '2FA diaktifkan', $event->user),
            $event instanceof TwoFactorAuthenticationConfirmed => $this->record('two_factor_confirmed', '2FA dikonfirmasi', $event->user),
            $event instanceof TwoFactorAuthenticationDisabled => $this->record('two_factor_disabled', '2FA dinonaktifkan', $event->user),
            $event instanceof TwoFactorAuthenticationFailed => $this->record('two_factor_failed', 'Kode 2FA salah', $event->user, [], Activity::OUTCOME_FAILURE),
            $event instanceof RecoveryCodesGenerated => $this->record('recovery_codes_generated', 'Kode pemulihan 2FA dibuat ulang', $event->user),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function record(string $event, string $description, mixed $user, array $properties = [], string $outcome = Activity::OUTCOME_SUCCESS): void
    {
        $user = $user instanceof User ? $user : null;

        $this->audit->record(
            logName: self::LOG_NAME,
            event: $event,
            description: $description,
            subject: $user,
            properties: $properties,
            outcome: $outcome,
            causer: $outcome === Activity::OUTCOME_SUCCESS && ! in_array($event, ['password_reset_link_sent'], true) ? $user : null,
        );
    }

    private function recordFailedLogin(Failed $event): void
    {
        $identity = Str::lower(trim((string) ($event->credentials['email'] ?? '')));
        $identityHash = $identity === '' ? null : hash_hmac('sha256', $identity, (string) config('app.key'));
        $throttleKey = 'auth-failed-log:'.sha1(request()->ip().'|'.$identityHash);

        if (! Cache::add($throttleKey, true, self::FAILED_LOG_WINDOW_SECONDS)) {
            return;
        }

        $this->record('login_failed', 'Login gagal', null, [
            'guard' => $event->guard,
            'identity_hash' => $identityHash,
            'user_exists' => $event->user !== null,
        ], Activity::OUTCOME_FAILURE);
    }
}
