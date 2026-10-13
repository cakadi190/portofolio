# 01 — Gambaran Produk

## Ringkasan

**Catatan Cakadi** (`cakadi.web.id`) adalah situs personal-branding milik Amir Zuhdi Wibowo (Cak Adi): gabungan **CV/portofolio online**, **blog**, **ruang klarifikasi (hak jawab)**, **profil pembicara & mentor**, dan **direktori tempat ngopi** untuk kerja remote, dengan **panel admin** (CMS) untuk mengelola seluruh kontennya. Pemilik situs adalah penulis/pengelola utama; pengunjung publik membaca, dan pengguna terdaftar dapat berkomentar di blog.

## Masalah yang diselesaikan

| Masalah                                                                       | Solusi produk                                                                     |
| ----------------------------------------------------------------------------- | --------------------------------------------------------------------------------- |
| CV statis (PDF) cepat usang dan tidak bisa dicari/dibagikan per bagian.       | Profil, karier, pendidikan, sertifikasi, penghargaan, dan portofolio sebagai halaman terpisah yang mudah diperbarui. |
| Rekruter/klien butuh bukti kerja dan reputasi, bukan sekadar klaim.           | Portofolio dengan teknologi, galeri, ulasan terverifikasi, sertifikasi berkas, dan rekam jejak pembicara. |
| Konten tersebar di banyak platform dan sulit dikelola.                        | Satu CMS dengan Pustaka Media terpusat; semua konten milik sendiri.               |
| Klaim/isu tentang pemilik situs beredar tanpa tanggapan yang bisa dirujuk.    | Halaman Klarifikasi dengan vonis, bukti/sumber, dan jejak waktu.                  |
| Sulit menemukan tempat kerja remote yang nyaman di sekitar pemilik.           | Direktori kedai kopi dengan Wi-Fi, harga, fasilitas, parkir, dan peta.            |

## Tujuan

1. Menampilkan profil profesional (pendidikan, karier, organisasi, penghargaan, sertifikasi, portofolio) yang mudah diperbarui tanpa menyentuh kode.
2. Menerbitkan artikel blog dengan editor WYSIWYG ala WordPress, lengkap dengan kategori, tag, jadwal terbit, dan komentar moderasi.
3. Menyediakan kanal **klarifikasi/hak jawab** yang transparan (klaim, vonis, bukti).
4. Menampilkan rekam jejak **pembicara & mentoring** (acara mendatang dan arsip).
5. Berbagi rekomendasi kedai kopi (Wi-Fi, harga, parkir, peta) sebagai sumber daya komunitas.
6. Menjadi kanal kontak yang terlindungi spam (formulir kontak + inbox admin).
7. Mengelola semua gambar/dokumen di satu **Pustaka Media** terpusat yang dapat dipakai ulang.
8. Mudah ditemukan mesin pencari dan dibagikan di media sosial (SEO, sitemap, Open Graph, JSON-LD).

## Persona

| Persona                                 | Kebutuhan                                                                                                       |
| --------------------------------------- | --------------------------------------------------------------------------------------------------------------- |
| **Pengunjung / calon klien / rekruter** | Cepat menilai kemampuan, melihat portofolio dan sertifikasi, membaca blog, menghubungi pemilik. Tanpa login.    |
| **Panitia / penyelenggara acara**       | Melihat rekam jejak dan jadwal pembicara/mentoring, lalu mengajak kolaborasi lewat formulir kontak.             |
| **Pembaca terdaftar (`user`)**          | Berkomentar dan membalas di artikel blog setelah verifikasi email; komentar menunggu moderasi.                 |
| **Pihak yang terkait klaim**            | Membaca klarifikasi resmi beserta bukti dan membagikan tautannya.                                              |
| **Redaktur (`redaktur`)**               | Mengelola blog saja: artikel, kategori, tag, moderasi komentar, dan unggah media (area `manage-blog`).         |
| **Admin / pemilik (`admin`)**           | Mengelola semua konten dan pengguna lewat `/admin`; aman (2FA, passkey); nyaman (tabel cari/urut, modal form, picker media). |

## Ruang lingkup (in scope)

**Situs publik** (Bahasa Indonesia, `id_ID`):

- Beranda, CV/Resume (`/resume`), Karier, Pendidikan, Penghargaan, Kontak, Layanan (+ detail), Blog (+ detail & komentar), Portofolio (+ detail & ulasan), Tentang (saya / situs / skill, memuat sertifikasi), Pembicara & Mentoring, Klarifikasi (+ detail), Tempat Ngopi.
- SEO on-page, sitemap (index + sub-sitemap), `robots.txt`, redirect 301 dari URL Indonesia lama, halaman error kustom, Google Analytics 4.
- Mode terang/gelap, lightbox gambar & video, viewer PDF khusus (CV dan sertifikasi), tampilan responsif.

**Panel admin** (`/admin`):

- CRUD seluruh entitas konten, Pustaka Media, Pengaturan Sistem, Pesan Masuk, moderasi komentar & ulasan, manajemen pengguna, dasbor, dan kalender kegiatan.
- Peran `admin` / `redaktur` / `user` dengan gate `manage-site` dan `manage-blog`.

**Autentikasi & keamanan:** login, registrasi, reset password, verifikasi email, 2FA (TOTP), passkey, Cloudflare Turnstile pada form publik, throttle, header keamanan, sanitasi HTML.

## Di luar lingkup (non-goals)

- E-commerce, pembayaran, multi-tenant, multi-penulis dengan alur persetujuan editorial berlapis.
- Peran/izin granular di luar tiga peran saat ini.
- API publik terdokumentasi (halaman dirender lewat Inertia).
- Komentar pada portofolio selain "ulasan" berperingkat; komentar tamu tanpa akun di blog.
- Pengelolaan konten multi-bahasa penuh (konten ditulis dalam Bahasa Indonesia).
- Newsletter/langganan email dan notifikasi push (lihat ide lanjutan di `06`).

## Asumsi & batasan

- Satu pemilik konten; trafik rendah–menengah, sehingga SQLite dan satu VPS (Docker, blue/green) dinilai cukup.
- Konten adalah milik pemilik; klarifikasi adalah **pernyataan sepihak** pemilik dan harus disertai bukti (lihat `06` #9).
- Berkas unggahan dibatasi 10 MB dan dikompresi ke WebP (lihat `05`).
- Antarmuka berbahasa Indonesia; kode, kolom, dan rute admin berbahasa Inggris.

## Metrik keberhasilan

| Metrik                                                   | Target                                                              |
| -------------------------------------------------------- | ------------------------------------------------------------------- |
| Waktu admin menambah artikel/portofolio lengkap bergambar | < 3 menit                                                           |
| Bobot gambar halaman publik                              | WebP, ≤ 300–400 KB per berkas                                       |
| Deploy dari nol                                          | `migrate:fresh --seed` menghasilkan situs yang langsung terisi & berjalan |
| Jangkauan organik                                        | Seluruh halaman terbit terindeks lewat sitemap; tidak ada 404 pada URL lama (redirect 301) |
| Konversi kontak                                          | Pesan kontak valid terkirim (event GA4 `generate_lead`) dan masuk inbox admin |
| Keterlibatan konten                                      | Event GA4 `scroll_depth`, `comment_submit`, `rating_submit` terpantau |
| Kualitas                                                 | Tes Pest & Vitest hijau di CI sebelum rilis                         |

## Rilis & status modul

| Modul                                              | Status |
| -------------------------------------------------- | ------ |
| Profil (karier, pendidikan, organisasi, sertifikasi, penghargaan) | ✅ |
| Portofolio + teknologi, layanan, galeri, ulasan     | ✅ |
| Blog + kategori, tag, komentar moderasi, penulis    | ✅ |
| Pembicara & Mentoring                              | ✅ |
| Klarifikasi (hak jawab)                            | ✅ |
| Tempat Ngopi + galeri + peta                       | ✅ |
| Pustaka Media, Pengaturan Sistem, Pesan Masuk       | ✅ |
| SEO, sitemap, GA4, Turnstile, header keamanan       | ✅ (CSP masih `Report-Only`, lihat `06` #10) |

Persyaratan non-fungsional, alur pengguna utama, kriteria penerimaan, risiko, dan glosarium ada di [07-nonfunctional-and-acceptance.md](07-nonfunctional-and-acceptance.md).
