<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;

enum SystemSettingGroup: string
{
    use HasEnumOptions, HasEnumValues;

    case Information = 'information';
    case SocialMedia = 'social_media';
    case Mail = 'mail';
    case Seo = 'seo';
    case Security = 'security';
    case VisitorAnalytics = 'visitor_analytics';
    case IpInfo = 'ipinfo';
    case Monitoring = 'monitoring';

    public function label(): string
    {
        return match ($this) {
            self::Information => 'Informasi Kontak',
            self::SocialMedia => 'Sosial Media',
            self::Mail => 'Surel & Notifikasi',
            self::Seo => 'SEO & Analitik',
            self::Security => 'Keamanan',
            self::VisitorAnalytics => 'Analitik Pengunjung',
            self::IpInfo => 'Integrasi IPInfo',
            self::Monitoring => 'Sentry',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Information => 'Detail kontak yang ditampilkan pada halaman Hubungi Saya.',
            self::SocialMedia => 'Tautan akun sosial media yang ditampilkan pada halaman Hubungi Saya.',
            self::Mail => 'Alamat tujuan pesan dari formulir kontak.',
            self::Seo => 'Metadata mesin pencari, verifikasi kepemilikan situs, dan Google Analytics. Kosongkan untuk memakai nilai bawaan.',
            self::Security => 'Cloudflare Turnstile untuk melindungi formulir login, daftar, atur ulang kata sandi, kontak, dan ulasan. Aktif hanya jika Site Key dan Secret Key terisi.',
            self::VisitorAnalytics => 'Penghitung pengunjung berbasis data tersimpan: tayangan halaman, pengunjung unik (cookie pihak pertama), kunjungan, dan bot. Lihat docs/09-activity-and-analytics.md untuk definisi metrik.',
            self::IpInfo => 'Pengayaan lokasi/jaringan IP pengunjung lewat IPInfo. Token disimpan terenkripsi dan tidak pernah ditampilkan kembali; kosongkan kolom token untuk mempertahankannya.',
            self::Monitoring => 'Pelacakan error, performa, dan log lewat Sentry untuk backend (Laravel) dan frontend (Svelte). Data pribadi (IP, cookie, isi form, email, token) dibersihkan sebelum dikirim; pengguna hanya dikenali lewat ID semu. Lihat docs/09-activity-and-analytics.md.',
        };
    }

    /**
     * Setting keys whose values are write-only: never sent back to the browser.
     *
     * @return list<string>
     */
    public static function secretKeys(): array
    {
        $keys = [];

        foreach (self::cases() as $group) {
            foreach ($group->fields() as $field) {
                if ($field['type'] === 'secret') {
                    $keys[] = $field['key'];
                }
            }
        }

        return $keys;
    }

    /**
     * The fixed set of setting keys managed under this group. Input `type` is
     * one of text, email, tel, url, image (media library path), secret (masked text), gtag (Google tag ID), digits, toggle (0/1), integer or select (with `options`) and drives both validation and the form.
     *
     * @return list<array{key: string, label: string, type: string, placeholder: string, default?: bool, note?: string, min?: int, max?: int, options?: list<array{value: string, label: string}>}>
     */
    public function fields(): array
    {
        return match ($this) {
            self::Information => [
                ['key' => 'contact_address', 'label' => 'Alamat', 'type' => 'text', 'placeholder' => 'Mis: Ngawi, Jawa Timur', 'note' => 'Maaf, saya tidak bisa memberikan alamat lengkap rumah saya dengan alasan privasi. Mohon untuk menghargainya!'],
                ['key' => 'contact_timezone', 'label' => 'Zona Waktu', 'type' => 'text', 'placeholder' => 'Mis: GMT+07:00 (Indonesian Western Time / UTC+7)'],
                ['key' => 'contact_email', 'label' => 'Surat Elektronik', 'type' => 'email', 'placeholder' => 'nama@contoh.com'],
                ['key' => 'contact_whatsapp', 'label' => 'Whatsapp (hanya chat)', 'type' => 'tel', 'placeholder' => '08xxxxxxxxxx'],
                ['key' => 'contact_website', 'label' => 'Website', 'type' => 'url', 'placeholder' => 'https://'],
            ],
            self::SocialMedia => [
                ['key' => 'social_facebook', 'label' => 'Facebook', 'type' => 'url', 'placeholder' => 'https://www.facebook.com/...'],
                ['key' => 'social_instagram', 'label' => 'Instagram', 'type' => 'url', 'placeholder' => 'https://www.instagram.com/...'],
                ['key' => 'social_twitter', 'label' => 'Twitter', 'type' => 'url', 'placeholder' => 'https://x.com/...'],
                ['key' => 'social_youtube', 'label' => 'Youtube', 'type' => 'url', 'placeholder' => 'https://youtube.com/@...'],
                ['key' => 'social_linkedin', 'label' => 'LinkedIn', 'type' => 'url', 'placeholder' => 'https://linkedin.com/in/...'],
                ['key' => 'social_tiktok', 'label' => 'TikTok', 'type' => 'url', 'placeholder' => 'https://tiktok.com/@...'],
            ],
            self::Mail => [
                ['key' => 'contact_recipient_email', 'label' => 'Email Penerima Pesan Kontak', 'type' => 'email', 'placeholder' => 'nama@contoh.com', 'note' => 'Pesan dari formulir kontak dikirim ke alamat ini.'],
            ],
            self::Seo => [
                ['key' => 'seo_image', 'label' => 'Gambar Bawaan (Open Graph)', 'type' => 'image', 'placeholder' => '', 'note' => 'Dipakai saat dibagikan ke sosial media jika halaman tidak punya gambar sendiri. Disarankan 1200×630 px.'],
                ['key' => 'seo_description', 'label' => 'Deskripsi Situs', 'type' => 'text', 'placeholder' => 'Ringkasan situs (maks. 160 karakter ideal)', 'note' => 'Dipakai pada halaman yang tidak punya deskripsi sendiri.'],
                ['key' => 'seo_keywords', 'label' => 'Kata Kunci', 'type' => 'text', 'placeholder' => 'web developer, laravel, ngawi', 'note' => 'Dipisahkan koma.'],
                ['key' => 'seo_author', 'label' => 'Nama Penulis', 'type' => 'text', 'placeholder' => 'Mis: Amir Zuhdi Wibowo'],
                ['key' => 'seo_twitter', 'label' => 'Akun Twitter/X', 'type' => 'text', 'placeholder' => '@namaakun'],
                ['key' => 'google_analytics_id', 'label' => 'Google Analytics ID', 'type' => 'gtag', 'placeholder' => 'G-XXXXXXXXXX', 'note' => 'Format G-XXXXXXX, GTM-XXXXXXX, atau AW-XXXXXXX.'],
                ['key' => 'facebook_app_id', 'label' => 'Facebook App ID', 'type' => 'digits', 'placeholder' => '1234567890'],
                ['key' => 'google_site_verification', 'label' => 'Verifikasi Google Search Console', 'type' => 'text', 'placeholder' => 'Isi atribut content dari meta google-site-verification'],
                ['key' => 'bing_site_verification', 'label' => 'Verifikasi Bing Webmaster', 'type' => 'text', 'placeholder' => 'Isi atribut content dari meta msvalidate.01'],
                ['key' => 'yandex_site_verification', 'label' => 'Verifikasi Yandex Webmaster', 'type' => 'text', 'placeholder' => 'Isi atribut content dari meta yandex-verification'],
                ['key' => 'baidu_site_verification', 'label' => 'Verifikasi Baidu Webmaster', 'type' => 'text', 'placeholder' => 'Isi atribut content dari meta baidu-site-verification'],
                ['key' => 'facebook_domain_verification', 'label' => 'Verifikasi Domain Facebook', 'type' => 'text', 'placeholder' => 'Isi atribut content dari meta facebook-domain-verification'],
                ['key' => 'pinterest_site_verification', 'label' => 'Verifikasi Pinterest', 'type' => 'text', 'placeholder' => 'Isi atribut content dari meta p:domain_verify'],
                ['key' => 'tiktok_site_verification', 'label' => 'Verifikasi TikTok', 'type' => 'text', 'placeholder' => 'Isi atribut content dari meta tiktok-developers-site-verification'],
                ['key' => 'naver_site_verification', 'label' => 'Verifikasi Naver Webmaster', 'type' => 'text', 'placeholder' => 'Isi atribut content dari meta naver-site-verification'],
            ],
            self::Security => [
                ['key' => 'turnstile_site_key', 'label' => 'Turnstile Site Key', 'type' => 'text', 'placeholder' => '0x4AAAAAAA...', 'note' => 'Kunci publik dari dashboard Cloudflare Turnstile.'],
                ['key' => 'turnstile_secret_key', 'label' => 'Turnstile Secret Key', 'type' => 'secret', 'placeholder' => '0x4AAAAAAA...', 'note' => 'Kunci rahasia untuk verifikasi di server. Kosongkan salah satu kunci untuk menonaktifkan Turnstile.'],
            ],
            self::VisitorAnalytics => [
                ['key' => 'visitor_tracking_enabled', 'default' => true, 'label' => 'Lacak pengunjung', 'type' => 'toggle', 'placeholder' => '', 'note' => 'Bawaan: aktif.'],
                ['key' => 'visitor_bot_policy', 'label' => 'Kebijakan bot', 'type' => 'select', 'placeholder' => '', 'options' => [['value' => 'separate', 'label' => 'Catat terpisah dari manusia'], ['value' => 'exclude', 'label' => 'Abaikan sepenuhnya']], 'note' => 'Bawaan: catat terpisah.'],
                ['key' => 'visitor_store_ip', 'default' => true, 'label' => 'Simpan alamat IP', 'type' => 'toggle', 'placeholder' => '', 'note' => 'Bawaan: aktif. Jika nonaktif, IP tidak disimpan dan pengayaan IPInfo tidak dijalankan.'],
                ['key' => 'visitor_excluded_paths', 'label' => 'Path yang dikecualikan', 'type' => 'text', 'placeholder' => 'admin,settings,up', 'note' => 'Awalan path dipisahkan koma, ditambahkan ke bawaan (admin, settings, up, storage, build).'],
                ['key' => 'visitor_session_minutes', 'label' => 'Durasi sesi kunjungan (menit)', 'type' => 'integer', 'placeholder' => '30', 'min' => 5, 'max' => 1440, 'note' => 'Bawaan: 30. Tayangan setelah jeda lebih lama dihitung sebagai kunjungan baru.'],
                ['key' => 'visitor_retention_days', 'label' => 'Retensi data pengunjung (hari)', 'type' => 'integer', 'placeholder' => '180', 'min' => 7, 'max' => 3650, 'note' => 'Bawaan: 180. Baris pengunjung/kunjungan/IP lebih lama dihapus; statistik harian agregat (tanpa data pribadi) dipertahankan.'],
            ],
            self::IpInfo => [
                ['key' => 'ipinfo_enabled', 'label' => 'Aktifkan IPInfo', 'type' => 'toggle', 'placeholder' => '', 'note' => 'Bawaan: nonaktif.'],
                ['key' => 'ipinfo_plan', 'label' => 'Plan IPInfo', 'type' => 'select', 'placeholder' => '', 'options' => [['value' => 'lite', 'label' => 'Lite (negara & ASN)'], ['value' => 'core', 'label' => 'Core / Plus (kota, koordinat, zona waktu)']], 'note' => 'Menentukan endpoint resmi yang dipanggil. Bawaan: Lite.'],
                ['key' => 'ipinfo_token', 'label' => 'Token API IPInfo', 'type' => 'secret', 'placeholder' => 'Token dari dashboard ipinfo.io', 'note' => 'Plan Lite/Core/Plus; bidang yang tidak tersedia pada plan Anda dibiarkan kosong.'],
                ['key' => 'ipinfo_cache_ttl_days', 'label' => 'Masa simpan cache IP (hari)', 'type' => 'integer', 'placeholder' => '30', 'min' => 1, 'max' => 90, 'note' => 'Bawaan: 30. Patuhi ketentuan retensi plan IPInfo Anda.'],
            ],
            self::Monitoring => [
                ['key' => 'sentry_enabled', 'label' => 'Aktifkan Sentry', 'type' => 'toggle', 'placeholder' => '', 'note' => 'Bawaan: nonaktif. Aktif hanya jika DSN terisi.'],
                ['key' => 'sentry_backend_dsn', 'label' => 'DSN Backend (Laravel)', 'type' => 'secret', 'placeholder' => 'https://<key>@o0.ingest.us.sentry.io/0', 'note' => 'Disimpan terenkripsi, tidak ditampilkan kembali. Kosongkan untuk memakai SENTRY_LARAVEL_DSN dari .env.'],
                ['key' => 'sentry_frontend_dsn', 'label' => 'DSN Frontend (Svelte)', 'type' => 'url', 'placeholder' => 'https://<key>@o0.ingest.us.sentry.io/0', 'note' => 'DSN frontend bersifat publik (tampil di browser).'],
                ['key' => 'sentry_environment', 'label' => 'Environment', 'type' => 'text', 'placeholder' => 'production', 'note' => 'Bawaan: environment aplikasi.'],
                ['key' => 'sentry_release', 'label' => 'Release', 'type' => 'text', 'placeholder' => 'catatancakadi@1.0.0', 'note' => 'Versi/commit rilis agar error dapat dilacak ke deploy tertentu. Kosongkan untuk memakai SENTRY_RELEASE.'],
                ['key' => 'sentry_traces_sample_percent', 'label' => 'Sampling performa (%)', 'type' => 'integer', 'placeholder' => '10', 'min' => 0, 'max' => 100, 'note' => 'Bawaan: 10. Persentase transaksi yang dilacak (backend & frontend).'],
                ['key' => 'sentry_profiles_sample_percent', 'label' => 'Sampling profiling (%)', 'type' => 'integer', 'placeholder' => '0', 'min' => 0, 'max' => 100, 'note' => 'Bawaan: 0. Backend butuh ekstensi PHP Excimer; frontend butuh header Document-Policy: js-profiling.'],
                ['key' => 'sentry_enable_logs', 'label' => 'Kirim log ke Sentry', 'type' => 'toggle', 'placeholder' => '', 'note' => 'Bawaan: nonaktif. Log tetap dibersihkan dari data pribadi.'],
            ],
        };
    }
}
