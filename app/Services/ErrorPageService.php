<?php

namespace App\Services;

class ErrorPageService
{
    /**
     * Status codes that get a dedicated Inertia error page.
     *
     * @var array<int, array{title: string, text: string, image: string}>
     */
    private const PAGES = [
        400 => ['title' => 'Permintaan Tidak Valid', 'text' => 'Permintaan yang Anda kirim tidak dapat diproses.', 'image' => '400'],
        401 => ['title' => 'Sesi Anda Berakhir', 'text' => 'Silakan masuk kembali untuk melanjutkan.', 'image' => '403'],
        403 => ['title' => 'Akses Ditolak', 'text' => 'Anda tidak memiliki izin untuk membuka halaman ini.', 'image' => '403'],
        404 => ['title' => 'Tidak Ditemukan', 'text' => 'Halaman yang Anda cari tidak tersedia atau sudah dipindahkan.', 'image' => '404'],
        419 => ['title' => 'Sesi Kedaluwarsa', 'text' => 'Muat ulang halaman lalu coba lagi.', 'image' => '400'],
        429 => ['title' => 'Terlalu Banyak Permintaan', 'text' => 'Coba lagi sebentar lagi.', 'image' => '429'],
        500 => ['title' => 'Ups, Terjadi Kesalahan', 'text' => 'Saat ini kami sedang memperbaiki kesalahan ini.', 'image' => '500'],
        503 => ['title' => 'Sedang Dalam Pemeliharaan', 'text' => 'Kami akan segera kembali. Silakan coba beberapa saat lagi.', 'image' => '503'],
    ];

    /**
     * Build the Inertia props for the given HTTP status, or null when the
     * status has no dedicated page.
     *
     * @return array{status: int, title: string, text: string, image: string}|null
     */
    public function propsFor(int $status): ?array
    {
        $page = self::PAGES[$status] ?? null;

        if ($page === null) {
            return null;
        }

        return [
            'status' => $status,
            'title' => $page['title'],
            'text' => $page['text'],
            'image' => "/images/errors/{$page['image']}.svg",
        ];
    }
}
