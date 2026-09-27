<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Signature('app:rotate-app-key
    {--force : Force the rotation to run without confirmation, even in production}
    {--keep=3 : Number of previous keys to retain for decryption fallback}')]
#[Description('Rotate the application encryption key and re-encrypt data encrypted with the previous key')]
class RotateAppKey extends Command
{
    use ConfirmableTrait;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->confirmToProceed('Rotating the application key will re-encrypt sensitive data. This action should not be interrupted.')) {
            return self::FAILURE;
        }

        $oldKey = (string) config('app.key');

        if ($oldKey === '') {
            $this->components->error('No existing APP_KEY found. Run `php artisan key:generate` first.');

            return self::FAILURE;
        }

        $newKey = 'base64:'.base64_encode(
            Encrypter::generateKey(config('app.cipher'))
        );

        $previousKeys = array_slice(
            array_values(array_unique(array_filter([
                $oldKey,
                ...config('app.previous_keys', []),
            ]))),
            0,
            (int) $this->option('keep')
        );

        if (! $this->writeKeysToEnvironmentFile($newKey, $previousKeys)) {
            return self::FAILURE;
        }

        config([
            'app.key' => $newKey,
            'app.previous_keys' => $previousKeys,
        ]);

        $this->components->info('New application key generated and written to .env.');

        $reencrypted = $this->reencryptUsers();

        $this->components->info("Re-encrypted sensitive columns for {$reencrypted} user(s).");
        $this->components->warn('Run `php artisan config:clear` (or restart queue workers) so all processes pick up the new key.');

        return self::SUCCESS;
    }

    /**
     * Re-encrypt every encrypted column on the users table with the current
     * (new) application key, falling back to the previous key for reads.
     */
    protected function reencryptUsers(): int
    {
        $count = 0;

        DB::transaction(function () use (&$count) {
            User::query()->chunkById(100, function ($users) use (&$count) {
                foreach ($users as $user) {
                    $dirty = false;

                    foreach (['nik', 'date_of_birth'] as $attribute) {
                        if ($user->getRawOriginal($attribute) !== null) {
                            $user->setAttribute($attribute, $user->getAttribute($attribute));
                            $dirty = true;
                        }
                    }

                    if ($dirty) {
                        $user->save();
                        $count++;
                    }

                    if ($this->reencryptTwoFactorColumns($user)) {
                        $count++;
                    }
                }
            });
        });

        return $count;
    }

    /**
     * Fortify stores two-factor columns as raw Crypt ciphertext outside of
     * Eloquent casts, so they need to be decrypted and re-encrypted directly.
     */
    protected function reencryptTwoFactorColumns(User $user): bool
    {
        $updates = [];

        foreach (['two_factor_secret', 'two_factor_recovery_codes'] as $column) {
            $raw = $user->getRawOriginal($column);

            if ($raw === null) {
                continue;
            }

            try {
                $updates[$column] = Crypt::encryptString(Crypt::decryptString($raw));
            } catch (Throwable) {
                $this->components->warn("Could not decrypt {$column} for user #{$user->id}; leaving it untouched.");
            }
        }

        if ($updates === []) {
            return false;
        }

        DB::table('users')->where('id', $user->id)->update($updates);

        return true;
    }

    /**
     * Write the new APP_KEY and APP_PREVIOUS_KEYS values into the .env file.
     *
     * @param  array<int, string>  $previousKeys
     */
    protected function writeKeysToEnvironmentFile(string $newKey, array $previousKeys): bool
    {
        $path = $this->laravel->environmentFilePath();
        $contents = file_get_contents($path);

        $contents = preg_replace(
            '/^APP_KEY=.*/m',
            'APP_KEY='.$newKey,
            $contents,
            count: $appKeyReplacements
        );

        $previousKeysValue = implode(',', $previousKeys);

        if (preg_match('/^APP_PREVIOUS_KEYS=.*/m', (string) $contents)) {
            $contents = preg_replace(
                '/^APP_PREVIOUS_KEYS=.*/m',
                'APP_PREVIOUS_KEYS='.$previousKeysValue,
                $contents
            );
        } else {
            $contents = rtrim((string) $contents)."\nAPP_PREVIOUS_KEYS={$previousKeysValue}\n";
        }

        if ($appKeyReplacements === 0 || $contents === null) {
            $this->components->error('Unable to locate APP_KEY in the .env file.');

            return false;
        }

        file_put_contents($path, $contents);

        return true;
    }
}
