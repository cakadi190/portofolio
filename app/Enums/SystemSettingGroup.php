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

    public function label(): string
    {
        return match ($this) {
            self::Information => 'Informasi Kontak',
            self::SocialMedia => 'Sosial Media',
            self::Mail => 'Surel & Notifikasi',
            self::Seo => 'SEO & Analitik',
            self::Security => 'Keamanan',
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
        };
    }

    /**
     * The fixed set of setting keys managed under this group. Input `type` is
     * one of text, email, tel, url, image (media library path), secret (masked text), gtag (Google tag ID) or digits and drives both validation and the form.
     *
     * @return list<array{key: string, label: string, type: string, placeholder: string, note?: string}>
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
        };
    }
}
