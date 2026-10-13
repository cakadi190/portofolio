<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Decides which trust store vouches for the top certificate of a PDF signature chain, like a browser does
 * for TLS. Indonesian roots (Komdigi) are kept by hand in `resources/trust/komdigi`; every other certificate
 * is checked against the server's own CA bundle, so international CAs follow the operating system's updates.
 * Adobe's Approved Trust List (`resources/trust/aatl`) is the last, separate source for document-signing roots.
 */
class CertificateTrustService
{
    public const string KOMDIGI = 'komdigi';

    public const string INTERNATIONAL = 'international';

    public const string AATL = 'aatl';

    public function __construct(private ?string $komdigiPath = null, private ?string $aatlPath = null) {}

    /**
     * @return array{source: string, anchor: string}|null
     */
    public function anchorFor(string $der): ?array
    {
        $pem = "-----BEGIN CERTIFICATE-----\n".chunk_split(base64_encode($der), 64, "\n")."-----END CERTIFICATE-----\n";

        return Cache::remember('pdf-trust:'.hash('sha256', $der), now()->addHour(), fn (): ?array => $this->resolve($pem));
    }

    /**
     * @return array{source: string, anchor: string}|null
     */
    private function resolve(string $pem): ?array
    {
        $certificate = @openssl_x509_read($pem);
        $parsed = $certificate ? openssl_x509_parse($certificate) : false;

        if (! $parsed) {
            return null;
        }

        $name = $parsed['subject'] === $parsed['issuer']
            ? ($parsed['subject']['CN'] ?? 'Root CA')
            : ($parsed['issuer']['CN'] ?? 'Root CA');

        if ($this->vouchedByRoots($certificate, $parsed, $this->rootsIn($this->komdigiPath ?? resource_path('trust/komdigi')))) {
            return ['source' => self::KOMDIGI, 'anchor' => $name];
        }

        $store = $this->systemStore();

        if ($store !== [] && openssl_x509_checkpurpose($certificate, X509_PURPOSE_ANY, $store) === true) {
            return ['source' => self::INTERNATIONAL, 'anchor' => $name];
        }

        return $this->vouchedByRoots($certificate, $parsed, $this->rootsIn($this->aatlPath ?? resource_path('trust/aatl')))
            ? ['source' => self::AATL, 'anchor' => $name]
            : null;
    }

    /**
     * Whether the certificate is one of `$roots` or was signed by one. Matching is done by signature on
     * purpose: OpenSSL always adds the system store to a verification, so using these folders as CA files would
     * also "trust" every system root, and it cannot anchor on a trusted intermediate.
     *
     * @param  array<string, mixed>  $parsed
     * @param  list<array{certificate: \OpenSSLCertificate, parsed: array<string, mixed>}>  $roots
     */
    private function vouchedByRoots(\OpenSSLCertificate $certificate, array $parsed, array $roots): bool
    {
        foreach ($roots as $root) {
            $isSame = $root['parsed']['serialNumberHex'] === $parsed['serialNumberHex'] && $root['parsed']['subject'] === $parsed['subject'];
            $isIssuer = $root['parsed']['subject'] === $parsed['issuer'];

            if (($isSame || $isIssuer) && openssl_x509_verify($certificate, openssl_pkey_get_public($root['certificate'])) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Every certificate in the PEM files (`.pem/.crt/.cer/.txt`) of a folder.
     *
     * @return list<array{certificate: \OpenSSLCertificate, parsed: array<string, mixed>}>
     */
    private function rootsIn(string $directory): array
    {
        $roots = [];

        foreach (array_filter(glob($directory.'/*') ?: [], fn (string $path): bool => is_file($path) && in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['pem', 'crt', 'cer', 'txt'], true)) as $file) {
            preg_match_all('/-----BEGIN CERTIFICATE-----.+?-----END CERTIFICATE-----/s', (string) file_get_contents($file), $matches);

            foreach ($matches[0] as $pem) {
                $certificate = @openssl_x509_read($pem);
                $parsed = $certificate ? openssl_x509_parse($certificate) : false;

                if ($parsed) {
                    $roots[] = ['certificate' => $certificate, 'parsed' => $parsed];
                }
            }
        }

        return $roots;
    }

    /**
     * CA bundle files and hashed directories of the operating system.
     *
     * @return list<string>
     */
    public function systemStore(): array
    {
        $locations = openssl_get_cert_locations();
        $paths = [
            $locations['ini_cafile'] ?? '',
            $locations['default_cert_file'] ?? '',
            '/etc/ssl/certs/ca-certificates.crt',
            $locations['ini_capath'] ?? '',
            $locations['default_cert_dir'] ?? '',
            '/etc/ssl/certs',
        ];

        return array_values(array_unique(array_filter($paths, fn (string $path): bool => $path !== '' && file_exists($path))));
    }
}
