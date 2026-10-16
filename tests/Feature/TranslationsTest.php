<?php

use App\Enums\ContactReason;
use Illuminate\Support\Arr;

/**
 * @return list<string>
 */
function translationGroups(): array
{
    return collect(glob(dirname(__DIR__, 2).'/lang/id/*.php'))
        ->map(fn (string $file): string => basename($file, '.php'))
        ->all();
}

it('has the same keys in every language file', function (string $group) {
    $id = Arr::dot(require dirname(__DIR__, 2)."/lang/id/{$group}.php");
    $en = Arr::dot(require dirname(__DIR__, 2)."/lang/en/{$group}.php");

    expect(array_keys($en))->toEqualCanonicalizing(array_keys($id));
})->with(fn () => translationGroups());

it('has no empty translation lines', function (string $group, string $locale) {
    $lines = Arr::dot(require dirname(__DIR__, 2)."/lang/{$locale}/{$group}.php");

    expect(array_filter($lines, fn ($line) => is_string($line) && trim($line) === ''))->toBeEmpty();
})->with(fn () => translationGroups())->with(['id', 'en']);

it('uses the same placeholders in both languages', function (string $group) {
    $id = Arr::dot(require dirname(__DIR__, 2)."/lang/id/{$group}.php");
    $en = Arr::dot(require dirname(__DIR__, 2)."/lang/en/{$group}.php");

    foreach (array_filter($id, 'is_string') as $key => $line) {
        preg_match_all('/:[a-z_]+/i', (string) $line, $idTokens);
        preg_match_all('/:[a-z_]+/i', (string) $en[$key], $enTokens);

        expect($enTokens[0])->toEqualCanonicalizing($idTokens[0], "placeholders differ for {$group}.{$key}");
    }
})->with(fn () => translationGroups());

it('validates in the language of the page', function (string $url, string $expected) {
    $this->post($url, [])->assertSessionHasErrors('name');

    expect(session('errors')->first('name'))->toBe($expected);
})->with([
    'indonesian' => ['/contact', 'nama lengkap wajib diisi.'],
    'english' => ['/en/contact', 'The full name field is required.'],
]);

it('translates enum labels for the active language', function () {
    app()->setLocale('id');

    expect(ContactReason::Correction->label())->toBe('Koreksi / Hak Jawab');

    app()->setLocale('en');

    expect(ContactReason::Correction->label())->toBe('Correction / Right of Reply');
});

it('localizes the page title and description for SEO', function () {
    $indonesian = $this->get('/contact')->getContent();
    $english = $this->get('/en/contact')->getContent();

    expect($indonesian)->toContain('<title>Hubungi Saya')
        ->and($english)->toContain('<title>Contact Me');
});
