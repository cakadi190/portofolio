<?php

use App\Models\User;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function withTemporaryEnvironmentFile(string $key): string
{
    $directory = sys_get_temp_dir();
    $file = 'rotate-app-key-test-'.Str::random(8).'.env';

    file_put_contents($directory.DIRECTORY_SEPARATOR.$file, "APP_KEY={$key}\n");

    app()->useEnvironmentPath($directory);
    app()->loadEnvironmentFrom($file);

    return $directory.DIRECTORY_SEPARATOR.$file;
}

test('rotates the application key and re-encrypts encrypted user columns', function () {
    $oldKey = 'base64:'.base64_encode(Encrypter::generateKey(config('app.cipher')));
    $envPath = withTemporaryEnvironmentFile($oldKey);

    config(['app.key' => $oldKey, 'app.previous_keys' => []]);

    $user = User::factory()->create([
        'nik' => '3201234567890001',
        'date_of_birth' => '1990-05-15',
    ]);

    DB::table('users')->where('id', $user->id)->update([
        'two_factor_secret' => Crypt::encryptString('SECRET123'),
        'two_factor_recovery_codes' => Crypt::encryptString('code-one,code-two'),
    ]);

    $originalRow = DB::table('users')->where('id', $user->id)->first();

    $this->artisan('app:rotate-app-key', ['--force' => true])
        ->assertSuccessful();

    $envContents = file_get_contents($envPath);

    expect($envContents)->toContain('APP_KEY=base64:')
        ->and($envContents)->not->toContain('APP_KEY='.$oldKey)
        ->and($envContents)->toContain('APP_PREVIOUS_KEYS='.$oldKey);

    $newKey = config('app.key');
    expect($newKey)->not->toBe($oldKey);
    expect(config('app.previous_keys'))->toContain($oldKey);

    $rotatedRow = DB::table('users')->where('id', $user->id)->first();

    expect($rotatedRow->nik)->not->toBe($originalRow->nik)
        ->and($rotatedRow->date_of_birth)->not->toBe($originalRow->date_of_birth)
        ->and($rotatedRow->two_factor_secret)->not->toBe($originalRow->two_factor_secret)
        ->and($rotatedRow->two_factor_recovery_codes)->not->toBe($originalRow->two_factor_recovery_codes);

    $fresh = $user->fresh();

    expect($fresh->nik)->toBe('3201234567890001')
        ->and($fresh->date_of_birth->format('Y-m-d'))->toBe('1990-05-15')
        ->and(Crypt::decryptString($rotatedRow->two_factor_secret))->toBe('SECRET123')
        ->and(Crypt::decryptString($rotatedRow->two_factor_recovery_codes))->toBe('code-one,code-two');

    @unlink($envPath);
});

test('caps the number of retained previous keys', function () {
    $oldKey = 'base64:'.base64_encode(Encrypter::generateKey(config('app.cipher')));
    $envPath = withTemporaryEnvironmentFile($oldKey);

    $existingPreviousKeys = [
        'base64:'.base64_encode(Encrypter::generateKey(config('app.cipher'))),
        'base64:'.base64_encode(Encrypter::generateKey(config('app.cipher'))),
    ];

    config(['app.key' => $oldKey, 'app.previous_keys' => $existingPreviousKeys]);

    $this->artisan('app:rotate-app-key', ['--force' => true, '--keep' => 2])
        ->assertSuccessful();

    expect(config('app.previous_keys'))->toHaveCount(2)
        ->and(config('app.previous_keys')[0])->toBe($oldKey);

    @unlink($envPath);
});
