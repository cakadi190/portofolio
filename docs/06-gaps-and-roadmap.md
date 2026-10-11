# 06 — Celah yang Diketahui & Roadmap

Hasil reverse engineering; urutan bukan prioritas final.

## Celah / utang teknis

| #   | Temuan                                                                                                                                                            | Dampak                                                             | Saran                                                             |
| --- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------ | ----------------------------------------------------------------- |
| 1   | ~~`UserRole` tidak ditegakkan di `/admin/*`~~ **Teratasi**: gate `manage-site`/`manage-blog` di level rute (lihat `02`). Registrasi publik hanya membuat `user`. | — | — |
| 2   | ~~Isi HTML Tiptap dirender tanpa sanitasi~~ **Teratasi**: `HtmlSanitizer` menyaring konten saat dirender (lihat `02` bagian D). Sisa risiko: penyaringan ada di sisi render, bukan saat simpan; setiap `{@html}` baru harus memakainya. | — | Pertahankan tes `RichTextOutputTest`. |
| 3   | Pelacakan pemakaian media berbasis daftar kolom.                                                                                                                  | Kolom baru terlupa → media bisa terhapus saat dipakai.             | Tes yang memverifikasi `USAGES` mencakup semua kolom `MediaPath`. |
| 4   | `bun run types:check` gagal di lingkungan dev (konfigurasi TypeScript 7 + svelte-check).                                                                          | Tipe frontend belum tervalidasi otomatis.                          | Pasang TS 6 + `@typescript/native` sesuai pesan error.            |
| 5   | Tipe model Wayfinder (`types.d.ts`) tidak memuat semua kolom; halaman mendeklarasikan tipe lokal.                                                                 | Potensi drift tipe.                                                | Telusuri konfigurasi Wayfinder, lalu impor tipe generated.        |
| 6   | Data seed memakai path aset statis, bukan item media.                                                                                                             | Tidak tampil di Pustaka Media.                                     | Opsional: seeder mengimpor aset ke `media`.                       |
| 7   | Halaman publik `/awards` belum menampilkan/menyaring `AwardType`.                                                                                            | Jenis hanya terlihat di admin.                                     | Tambahkan badge/filter jenis.                                     |
| 8   | `/speaking` hanya berupa daftar kartu (tanpa halaman detail per acara) dan waktu acara disimpan sebagai waktu dinding WIB, sehingga status "akan datang" bisa meleset hingga 7 jam (zona aplikasi UTC). | Acara selesai bisa tampil "akan datang" beberapa jam lebih lama. | Tambah halaman detail/slug; simpan zona waktu acara. |
| 9   | Klarifikasi (`/clarifications`) adalah klaim sepihak pemilik situs. JSON-LD memakai `BlogPosting`, bukan `ClaimReview` (Google menghentikan tampilan rich result fact-check sejak Juni 2025). Isi HTML Tiptap tidak disanitasi (sama seperti #2). | Tidak ada manfaat rich result; halaman bisa dibaca sebagai pembelaan sepihak bila bukti lemah. | Bukti wajib (≥ 1 sumber saat terbit), arsipkan tangkapan layar ke Pustaka Media, naskah serius ditinjau advokat. |
| 10  | Belum ada Content-Security-Policy. Situs memakai data inline Inertia, Google Analytics, Turnstile, dan Iconify API sehingga kebijakan perlu disusun dan diuji dulu dengan `Content-Security-Policy-Report-Only`. | XSS yang lolos sanitizer tidak dibatasi lapisan kedua. | Mulai dengan report-only, lalu perketat. |
| 11  | Referensi nginx (`deploy/nginx`) memakai `client_max_body_size 0` (tanpa batas) dan `proxy_request_buffering off`. Batas 10 MB hanya diterapkan aplikasi setelah unggahan diterima penuh. | Unggahan sangat besar dapat menghabiskan disk/memori VPS (DoS). | Set `client_max_body_size 12m` pada server nyata (perubahan manual di server, bukan lewat deploy otomatis). |

## Ide lanjutan

- Folder & tag media, thumbnail multi-ukuran, ganti berkas tanpa mengubah path.
- Formulir kontak fungsional, RSS/sitemap, SEO meta per artikel.
- Pratinjau artikel draf, penjadwalan terbit otomatis.
- Audit log perubahan admin.
