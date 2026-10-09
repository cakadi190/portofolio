# 06 — Celah yang Diketahui & Roadmap

Hasil reverse engineering; urutan bukan prioritas final.

## Celah / utang teknis

| #   | Temuan                                                                                                                                                            | Dampak                                                             | Saran                                                             |
| --- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------ | ----------------------------------------------------------------- |
| 1   | `UserRole` (admin/user) ada, tetapi seluruh `/admin/*` hanya mensyaratkan `auth` + `verified`; `FormRequest::authorize()` selalu `true`. Registrasi publik aktif. | Pengguna terdaftar mana pun dapat masuk admin dan mengelola media. | Middleware/policy `admin`, atau matikan registrasi.               |
| 2   | Isi artikel (HTML Tiptap) dirender tanpa sanitasi di sisi server.                                                                                                 | XSS bila akun admin dikompromikan.                                 | Sanitasi saat simpan/render.                                      |
| 3   | Pelacakan pemakaian media berbasis daftar kolom.                                                                                                                  | Kolom baru terlupa → media bisa terhapus saat dipakai.             | Tes yang memverifikasi `USAGES` mencakup semua kolom `MediaPath`. |
| 4   | `bun run types:check` gagal di lingkungan dev (konfigurasi TypeScript 7 + svelte-check).                                                                          | Tipe frontend belum tervalidasi otomatis.                          | Pasang TS 6 + `@typescript/native` sesuai pesan error.            |
| 5   | Tipe model Wayfinder (`types.d.ts`) tidak memuat semua kolom; halaman mendeklarasikan tipe lokal.                                                                 | Potensi drift tipe.                                                | Telusuri konfigurasi Wayfinder, lalu impor tipe generated.        |
| 6   | Data seed memakai path aset statis, bukan item media.                                                                                                             | Tidak tampil di Pustaka Media.                                     | Opsional: seeder mengimpor aset ke `media`.                       |
| 7   | Halaman publik `/awards` belum menampilkan/menyaring `AwardType`.                                                                                            | Jenis hanya terlihat di admin.                                     | Tambahkan badge/filter jenis.                                     |
| 8   | `/speaking` hanya berupa daftar kartu (tanpa halaman detail per acara) dan waktu acara disimpan sebagai waktu dinding WIB, sehingga status "akan datang" bisa meleset hingga 7 jam (zona aplikasi UTC). | Acara selesai bisa tampil "akan datang" beberapa jam lebih lama. | Tambah halaman detail/slug; simpan zona waktu acara. |

## Ide lanjutan

- Folder & tag media, thumbnail multi-ukuran, ganti berkas tanpa mengubah path.
- Formulir kontak fungsional, RSS/sitemap, SEO meta per artikel.
- Pratinjau artikel draf, penjadwalan terbit otomatis.
- Audit log perubahan admin.
