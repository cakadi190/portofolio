# 07 — Persyaratan Non-Fungsional, Alur Pengguna & Penerimaan

Melengkapi `02` (fungsional). Status: ✅ sudah ada di kode, 🟡 sebagian, ⬜ belum (target).

## A. Alur pengguna utama (user stories)

| ID    | Sebagai…            | Saya ingin…                                                              | Sehingga…                      | Kriteria penerimaan                                                                                               |
| ----- | ------------------- | ------------------------------------------------------------------------ | ------------------------------ | ----------------------------------------------------------------------------------------------------------------- |
| US-01 | Rekruter            | melihat karier, pendidikan, sertifikasi, dan portofolio                  | menilai kemampuan dengan cepat | Halaman `/career`, `/education`, `/about`, `/portfolio` tampil tanpa login; sertifikasi bisa dibuka (gambar/PDF). |
| US-02 | Calon klien         | membaca detail portofolio dan layanan, lalu menghubungi pemilik          | memutuskan kerja sama          | `/portfolio/{slug}` memuat teknologi, galeri, ulasan disetujui; formulir `/contact` terkirim dan masuk inbox.     |
| US-03 | Pembaca             | membaca artikel dan menyaring per kategori/tag                           | menemukan topik relevan        | Hanya artikel `is_published` & `published_at` ≤ sekarang yang tampil.                                             |
| US-04 | Pembaca terdaftar   | berkomentar/membalas artikel                                             | berdiskusi                     | Wajib login + verifikasi email; komentar `pending` sampai disetujui; Turnstile & throttle aktif.                  |
| US-05 | Klien sebelumnya    | memberi ulasan dan rating portofolio                                     | berbagi pengalaman             | Ulasan baru `is_approved=false`; tampil publik setelah disetujui admin.                                           |
| US-06 | Publik              | membaca klarifikasi atas klaim tentang pemilik                           | memahami fakta                 | Klarifikasi terbit menampilkan klaim, vonis, ringkasan, bukti/sumber; dapat disaring `?verdict=`.                 |
| US-07 | Penyelenggara acara | melihat jadwal & arsip pembicara/mentoring                               | mengundang pemilik             | `/speaking` memisahkan "Akan Datang" dan "Arsip"; poster dapat dibuka.                                            |
| US-08 | Pekerja remote      | mencari kedai kopi berdasarkan wilayah, Wi-Fi, harga                     | memilih tempat kerja           | `/resources/coffee-shops` dapat difilter/dicari; modal detail memuat galeri & peta.                               |
| US-09 | Admin               | login aman dengan 2FA/passkey                                            | akun tidak mudah dibobol       | 2FA TOTP + kode pemulihan, passkey, konfirmasi password untuk aksi sensitif.                                      |
| US-10 | Admin               | menambah artikel/portofolio dengan gambar dari Pustaka Media             | konten cepat terbit            | Editor Tiptap, picker media, slug otomatis; selesai < 3 menit.                                                    |
| US-11 | Admin               | memoderasi komentar, ulasan, dan pesan masuk                             | menjaga kualitas & merespons   | Setujui/tolak/hapus; pesan otomatis ditandai dibaca saat dibuka.                                                  |
| US-12 | Redaktur            | mengelola blog tanpa akses ke data situs lain                            | pembagian tugas aman           | Rute non-blog menolak redaktur (403); tes `RedakturAccessTest`.                                                   |
| US-13 | Admin               | mengatur kontak, sosial media, SEO, dan ID analitik tanpa menyentuh kode | situs mudah dikonfigurasi      | `/admin/system-settings` menyimpan nilai terenkripsi dan langsung dipakai halaman publik.                         |

## B. Persyaratan non-fungsional

### Keamanan

| ID     | Kebutuhan                                                                                                                | Status        |
| ------ | ------------------------------------------------------------------------------------------------------------------------ | ------------- |
| NFR-S1 | Seluruh `/admin/*` memakai `auth` + `verified` dan gate peran (`manage-site`, `manage-blog`).                            | ✅            |
| NFR-S2 | HTML dari editor disaring `HtmlSanitizer` saat dirender; input publik (kontak, ulasan) disaring `SanitizesRichText`.     | ✅            |
| NFR-S3 | Form publik dilindungi Turnstile dan throttle; kredensial dijaga 2FA/passkey.                                            | ✅            |
| NFR-S4 | Header keamanan (nosniff, X-Frame-Options, Referrer-Policy, Permissions-Policy, HSTS); cookie sesi `Secure` di produksi. | ✅            |
| NFR-S5 | Administrator terakhir tidak dapat dihapus/diturunkan perannya.                                                          | ✅            |
| NFR-S6 | Pengaturan sensitif (`system_settings.value`) tersimpan terenkripsi; `APP_KEY` dapat dirotasi (`RotateAppKey`).          | ✅            |
| NFR-S7 | CSP diberlakukan (bukan sekadar `Report-Only`).                                                                          | 🟡 (`06` #10) |
| NFR-S8 | Batas ukuran unggah di lapisan nginx (`client_max_body_size 12m`) diterapkan di server nyata.                            | 🟡 (`06` #11) |
| NFR-S9 | Unggahan hanya lewat Pustaka Media (validasi MIME/ukuran), tidak lewat controller.                                       | ✅            |

### Performa

| ID     | Kebutuhan                                                                                      | Status           |
| ------ | ---------------------------------------------------------------------------------------------- | ---------------- |
| NFR-P1 | Gambar dikompresi ke WebP ≤ 300–400 KB; cache gambar via Cloudflare worker `image-cache`.      | ✅               |
| NFR-P2 | HTML dimampatkan di produksi (`MinifyHtmlResponse`); aset di-build Vite dengan code-splitting. | ✅               |
| NFR-P3 | Daftar publik dipaginasi; query memakai eager loading (tanpa N+1).                             | 🟡 audit berkala |
| NFR-P4 | Target Core Web Vitals "baik" (LCP < 2,5 dtk, CLS < 0,1) pada halaman publik utama.            | 🟡 diukur (PSI)   |
| NFR-P5 | Jalur kritis halaman publik ringan: font tanpa preload, hanya 2 bendera (`css/components/_flags.scss`), CSS admin/simplebar tidak ikut entry publik, ikon layar pertama dibundel offline (`lib/icon-collections.ts`, perlu dibuat ulang bila ikon di beranda berubah), chunk halaman beranda di-preload, thumbnail kartu 640 px, editor Tiptap dan widget Turnstile di halaman publik dimuat malas (saat formulir mendekati layar/difokus). | ✅               |

### SEO & aksesibilitas

| ID     | Kebutuhan                                                                                               | Status                          |
| ------ | ------------------------------------------------------------------------------------------------------- | ------------------------------- |
| NFR-A1 | Meta title/description, canonical, Open Graph, Twitter Card, JSON-LD dirender server-side.              | ✅                              |
| NFR-A2 | Sitemap index + sub-sitemap dan `robots.txt`; URL lama dialihkan 301.                                   | ✅                              |
| NFR-A3 | Kontras dan mode terang/gelap; tabel admin menjadi kartu berlabel di layar < 768 px.                    | ✅                              |
| NFR-A4 | Target WCAG 2.1 AA: label form, `alt` gambar dari Pustaka Media, navigasi keyboard pada modal/lightbox. | 🟡 audit manual belum dilakukan |

### Keandalan & operasional

| ID     | Kebutuhan                                                                                                 | Status |
| ------ | --------------------------------------------------------------------------------------------------------- | ------ |
| NFR-O1 | Deploy blue/green tanpa downtime (`scripts/deploy-bluegreen.sh`); `migrate:fresh --seed` aman dijalankan. | ✅     |
| NFR-O2 | CI menjalankan Pest, Vitest, Pint, dan Larastan pada push/PR.                                             | ✅     |
| NFR-O3 | Halaman error ramah untuk 4xx/5xx.                                                                        | ✅     |
| NFR-O4 | Pencadangan terjadwal basis data SQLite & `storage/app/public` (RPO ≤ 24 jam, uji pemulihan berkala).     | ⬜     |
| NFR-O5 | Pemantauan uptime & alert error (mis. health check, log terpusat).                                        | ⬜     |
| NFR-O6 | Kebijakan retensi pesan kontak & log.                                                                     | ⬜     |

### Privasi & kepatuhan

| ID     | Kebutuhan                                                                                                                         | Status           |
| ------ | --------------------------------------------------------------------------------------------------------------------------------- | ---------------- |
| NFR-R1 | Data pribadi pengunjung terbatas (nama, email pada kontak/ulasan/komentar); tidak dibagikan ke pihak ketiga selain GA4/Turnstile. | ✅               |
| NFR-R2 | Halaman kebijakan privasi & pemberitahuan cookie/analitik (selaras UU PDP).                                                       | ⬜               |
| NFR-R3 | Mekanisme hapus/anonimkan data atas permintaan (akun dihapus → `user_id` komentar menjadi null).                                  | 🟡               |
| NFR-R4 | Klarifikasi memuat bukti; naskah sensitif ditinjau sebelum terbit (lihat `06` #9).                                                | 🟡 proses manual |

## C. Definition of Done (per perubahan)

1. Perilaku baru dijelaskan di `docs/` yang relevan.
2. Tes Pest (backend) dan/atau Vitest (frontend) ditambahkan dan hijau.
3. `vendor/bin/pint --dirty --format agent` bersih; tanpa migrasi `alter`.
4. Upload lewat Pustaka Media; teks UI Bahasa Indonesia.
5. Tidak ada `{@html}` baru tanpa `HtmlSanitizer`.

## D. Risiko

| Risiko                                                         | Dampak                     | Mitigasi                                                   |
| -------------------------------------------------------------- | -------------------------- | ---------------------------------------------------------- |
| XSS lewat konten WYSIWYG                                       | Pengambilalihan sesi admin | Sanitizer saat render, CSP, 2FA (`06` #2, #10)             |
| Kehilangan data (SQLite satu berkas, tanpa cadangan terjadwal) | Konten hilang              | NFR-O4; WAL + bind mount sudah ada, tambah backup off-site |
| Klarifikasi dipersepsikan sepihak / berisiko hukum             | Reputasi, gugatan          | Bukti wajib, tinjau advokat, arsipkan tangkapan layar      |
| Spam formulir/komentar                                         | Inbox penuh                | Turnstile, throttle, moderasi                              |
| Unggahan besar                                                 | Disk/memori penuh          | Batas 12m di nginx (`06` #11)                              |
| Drift tipe Wayfinder / `types:check` gagal                     | Bug tipe lolos             | `06` #4, #5                                                |
| Satu orang sebagai satu-satunya admin (bus factor)             | Akses hilang               | Kode pemulihan 2FA tersimpan aman; lebih dari satu admin   |

## E. Glosarium

| Istilah                     | Arti                                                                                 |
| --------------------------- | ------------------------------------------------------------------------------------ |
| Pustaka Media               | Penyimpanan berkas terpusat (`media`), dipakai lewat `MediaField`/`MediaPath`.       |
| Klarifikasi                 | Halaman hak jawab atas klaim tentang pemilik, dengan vonis (`ClarificationVerdict`). |
| Redaktur                    | Peran `redaktur`: hanya area blog (`manage-blog`).                                   |
| `manage-site`/`manage-blog` | Gate otorisasi admin penuh / area blog.                                              |
| Tiptap                      | Editor WYSIWYG berbasis blok untuk artikel, klarifikasi, deskripsi.                  |
| Turnstile                   | CAPTCHA Cloudflare pada form publik.                                                 |
| Wayfinder                   | Generator TypeScript untuk rute/tipe dari Laravel.                                   |
