<?php

use App\Services\CertificateTrustService;

function selfSignedDer(string $name): array
{
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    $csr = openssl_csr_new(['commonName' => $name], $key);
    $certificate = openssl_csr_sign($csr, null, $key, 30);
    openssl_x509_export($certificate, $pem);

    return [$pem, base64_decode(preg_replace('/-----[A-Z ]+-----|\s+/', '', $pem))];
}

test('a root added by hand to the komdigi folder anchors as komdigi', function () {
    [$pem, $der] = selfSignedDer('Root CA Indonesia Uji');
    $directory = sys_get_temp_dir().'/komdigi-test-'.uniqid();
    mkdir($directory);
    file_put_contents($directory.'/root.txt', $pem);
    app()->instance(CertificateTrustService::class, new CertificateTrustService($directory));

    $this->postJson(route('pdf-trust'), ['certificate' => base64_encode($der)])
        ->assertOk()
        ->assertExactJson(['source' => 'komdigi', 'anchor' => 'Root CA Indonesia Uji']);

    unlink($directory.'/root.txt');
    rmdir($directory);
});

test('a certificate in no store is not trusted', function () {
    [, $der] = selfSignedDer('Root Asing Palsu');
    app()->instance(CertificateTrustService::class, new CertificateTrustService(sys_get_temp_dir().'/kosong-'.uniqid()));

    $this->postJson(route('pdf-trust'), ['certificate' => base64_encode($der)])
        ->assertOk()
        ->assertExactJson(['source' => null, 'anchor' => null]);
});

test('a root from the server ca bundle anchors as international', function () {
    $store = (new CertificateTrustService)->systemStore();
    $bundle = collect($store)->first(fn (string $path) => is_file($path));

    if ($bundle === null) {
        $this->markTestSkipped('No system CA bundle file on this machine.');
    }

    preg_match('/-----BEGIN CERTIFICATE-----.+?-----END CERTIFICATE-----/s', file_get_contents($bundle), $match);
    $der = base64_decode(preg_replace('/-----[A-Z ]+-----|\s+/', '', $match[0]));

    $this->postJson(route('pdf-trust'), ['certificate' => base64_encode($der)])
        ->assertOk()
        ->assertJsonPath('source', 'international');
});

test('malformed input is rejected', function () {
    $this->postJson(route('pdf-trust'), ['certificate' => '***'])->assertStatus(422);
    $this->postJson(route('pdf-trust'), [])->assertStatus(422);
});

test('the shipped Root CA Indonesia DS G1 matches the official fingerprint and anchors as komdigi', function () {
    $pem = file_get_contents(resource_path('trust/komdigi/RootCAIndonesiaDSG1.txt'));
    $der = base64_decode(preg_replace('/-----[A-Z ]+-----|\s+/', '', $pem));

    expect(hash('sha256', $der))->toBe('b5f36672fd7cfd401e01ef640b2fe61f816f32f447b280b76f536244cf42fcdf');

    $this->postJson(route('pdf-trust'), ['certificate' => base64_encode($der)])
        ->assertOk()
        ->assertExactJson(['source' => 'komdigi', 'anchor' => 'Root CA Indonesia DS G1']);
});

test('a system root is never mislabelled as komdigi', function () {
    $bundle = collect((new CertificateTrustService)->systemStore())->first(fn (string $path) => is_file($path));

    if ($bundle === null) {
        $this->markTestSkipped('No system CA bundle file on this machine.');
    }

    preg_match('/-----BEGIN CERTIFICATE-----.+?-----END CERTIFICATE-----/s', file_get_contents($bundle), $match);
    $der = base64_decode(preg_replace('/-----[A-Z ]+-----|\s+/', '', $match[0]));

    $this->postJson(route('pdf-trust'), ['certificate' => base64_encode($der)])
        ->assertJsonPath('source', 'international');
});

test('a self-signed root nobody trusts stays untrusted even though komdigi roots exist', function () {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    $csr = openssl_csr_new(['commonName' => 'Unknown Root'], $key);
    openssl_x509_export(openssl_csr_sign($csr, null, $key, 30, ['x509_extensions' => 'v3_ca']), $pem);
    $der = base64_decode(preg_replace('/-----[A-Z ]+-----|\s+/', '', $pem));

    $this->postJson(route('pdf-trust'), ['certificate' => base64_encode($der)])
        ->assertExactJson(['source' => null, 'anchor' => null]);
});

test('a root from Adobe\'s approved trust list anchors as aatl and is not mistaken for a system root', function () {
    preg_match('/-----BEGIN CERTIFICATE-----.+?-----END CERTIFICATE-----/s', file_get_contents(resource_path('trust/aatl/AdobeAATL.txt')), $match);
    $der = base64_decode(preg_replace('/-----[A-Z ]+-----|\s+/', '', $match[0]));

    $this->postJson(route('pdf-trust'), ['certificate' => base64_encode($der)])
        ->assertOk()
        ->assertJsonPath('source', 'aatl');
});
