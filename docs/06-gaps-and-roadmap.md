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
| 10  | CSP baru berupa `Content-Security-Policy-Report-Only` (belum diberlakukan). Situs memakai data inline Inertia, Google Analytics, Turnstile, dan Iconify API sehingga kebijakan perlu disusun dan diuji dulu dengan `Content-Security-Policy-Report-Only`. | XSS yang lolos sanitizer tidak dibatasi lapisan kedua. | Pantau konsol browser di semua halaman, lengkapi daftar sumber, lalu ganti ke header `Content-Security-Policy`. |
| 11  | ~~Batas unggah di nginx~~ **Teratasi 2026-10-12**: server memakai aaPanel (nginx di `/www/server/nginx`); batas global 50 MB kini dipersempit ke `12m` untuk situs lewat `/www/server/panel/vhost/nginx/extension/www.cakadi.web.id/upload_limit.conf` (folder `extension` aman dari penimpaan aaPanel). Diuji: 13 MB → 413. Sisa: `proxy_request_buffering` masih default (aktif), sehingga unggahan ≤ 12 MB di-buffer nginx dulu. | — | Bila server direprovision, pasang ulang file tersebut. |
| 12  | Belum ada pencadangan terjadwal, pemantauan uptime, halaman kebijakan privasi/cookie, dan audit aksesibilitas/Core Web Vitals (lihat `07`). | Risiko kehilangan data & kepatuhan; kualitas belum terukur. | Cron backup SQLite + media off-site, health check, halaman privasi, audit Lighthouse. |
| 13  | Halaman Penyangkalan, kebijakan komentar, dan alasan kontak Koreksi sudah ada; seluruh usulan `08` bagian 6 sudah dikerjakan; sisa hanya kebijakan manual CLR-04/06/07. | Perlindungan hukum & kejelasan aturan lemah. | Patuhi aturan editorial manual. |

## Ide lanjutan

- Folder & tag media, thumbnail multi-ukuran, ganti berkas tanpa mengubah path.
- Newsletter/RSS, pencarian global situs, balasan email dari inbox admin, SEO meta per artikel.
- Pratinjau artikel draf, penjadwalan terbit otomatis.
- Audit log perubahan admin.
