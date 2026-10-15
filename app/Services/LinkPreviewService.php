<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Builds the card shown by the editor's link preview block from a page's
 * Open Graph metadata.
 *
 * The URL is typed by an editor, so the fetch is hardened against SSRF: only
 * http(s) on the default web ports, every redirect hop is re-validated, and the
 * connection is pinned to an address that is not private or reserved.
 */
class LinkPreviewService
{
    private const MAX_REDIRECTS = 3;

    private const MAX_BYTES = 1_000_000;

    /**
     * @return array{url: string, title: string, image: string, siteName: string}|null
     */
    public function preview(string $url): ?array
    {
        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            $target = $this->resolve($url);

            if ($target === null) {
                return null;
            }

            try {
                $response = Http::timeout(5)
                    ->withUserAgent('Mozilla/5.0 (compatible; CatatanCakadiBot/1.0; link-preview)')
                    ->withHeaders(['Accept' => 'text/html,application/xhtml+xml'])
                    ->withOptions([
                        'allow_redirects' => false,
                        'curl' => [CURLOPT_RESOLVE => ["{$target['host']}:{$target['port']}:{$target['ip']}"]],
                    ])
                    ->get($url);
            } catch (Throwable) {
                return null;
            }

            if ($response->redirect()) {
                $url = $this->absolute((string) $response->header('Location'), $url);

                continue;
            }

            if (! $response->successful() || ! str_contains(strtolower((string) $response->header('Content-Type')), 'html')) {
                return null;
            }

            return $this->parse(substr($response->body(), 0, self::MAX_BYTES), $url);
        }

        return null;
    }

    /**
     * @return array{host: string, port: int, ip: string}|null
     */
    private function resolve(string $url): ?array
    {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = $parts['host'] ?? '';

        if (! in_array($scheme, ['http', 'https'], true) || $host === '' || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        if (! in_array($port, [80, 443], true)) {
            return null;
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : (gethostbyname($host) ?: '');

        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }

        return ['host' => $host, 'port' => $port, 'ip' => $ip];
    }

    private function absolute(string $location, string $base): string
    {
        if (preg_match('#^https?://#i', $location) === 1) {
            return $location;
        }

        $parts = parse_url($base);
        $origin = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '');

        return str_starts_with($location, '/') ? $origin.$location : $origin.'/'.$location;
    }

    /**
     * @return array{url: string, title: string, image: string, siteName: string}|null
     */
    private function parse(string $html, string $url): ?array
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($document);
        $meta = fn (string $key): string => trim((string) $xpath
            ->query("//meta[@property='{$key}' or @name='{$key}']/@content")
            ?->item(0)?->nodeValue);

        $title = $meta('og:title') ?: $meta('twitter:title') ?: trim((string) $document->getElementsByTagName('title')->item(0)?->textContent);

        if ($title === '') {
            return null;
        }

        $image = $meta('og:image') ?: $meta('twitter:image');
        $image = $image === '' ? '' : $this->absolute($image, $url);
        $host = (string) parse_url($url, PHP_URL_HOST);

        return [
            'url' => $url,
            'title' => mb_substr($title, 0, 160),
            'image' => preg_match('#^https?://#i', $image) === 1 ? $image : '',
            'siteName' => mb_substr($meta('og:site_name') ?: preg_replace('/^www\./', '', $host), 0, 60),
        ];
    }
}
