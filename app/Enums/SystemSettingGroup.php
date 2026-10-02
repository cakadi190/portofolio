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

    public function label(): string
    {
        return match ($this) {
            self::Information => 'Informasi Kontak',
            self::SocialMedia => 'Sosial Media',
            self::Mail => 'Surel & Notifikasi',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Information => 'Detail kontak yang ditampilkan pada halaman Hubungi Saya.',
            self::SocialMedia => 'Tautan akun sosial media yang ditampilkan pada halaman Hubungi Saya.',
            self::Mail => 'Alamat tujuan pesan dari formulir kontak.',
        };
    }

    /**
     * The fixed set of setting keys managed under this group. Input `type` is
     * one of text, email, tel or url and drives both validation and the form.
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
        };
    }
}
