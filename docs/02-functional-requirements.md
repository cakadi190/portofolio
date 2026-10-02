# 02 — Kebutuhan Fungsional

Status: ✅ ada di kode saat ini. ID dipakai untuk rujukan di PR/tes.

## A. Situs Publik (tanpa login)

| ID | Rute | Kebutuhan |
| --- | --- | --- |
| PUB-01 | `/` | Beranda: ringkasan portofolio terbaru dan artikel terbaru. ✅ |
| PUB-02 | `/karir` | Daftar pengalaman karier & organisasi. ✅ |
| PUB-03 | `/pendidikan` | Riwayat pendidikan per jenjang, termasuk nilai akademik (IPK/ujian sekolah). ✅ |
| PUB-04 | `/penghargaan` | Daftar penghargaan dengan ikon turunan otomatis (piala/medali/sertifikat) dan jenis penghargaan. ✅ |
| PUB-05 | `/blog`, `/blog/{slug}` | Daftar & detail artikel yang **sudah terbit** (`is_published`, `published_at`), dengan kategori dan tag. ✅ |
| PUB-06 | `/portofolio`, `/portofolio/{slug}` | Daftar & detail portofolio: teknologi, kategori, galeri, rating. Portofolio `is_private` tidak ditampilkan tautan kodenya. ✅ |
| PUB-07 | `/tentang/saya` | Profil, daftar sertifikasi (dengan berkas/kredensial). ✅ |
| PUB-08 | `/tentang/situs`, `/tentang/skill` | Informasi situs dan keahlian (halaman statis). ✅ |
| PUB-09 | `/layanan`, `/kontak` | Halaman layanan dan kontak (statis). ✅ |
| PUB-10 | `/sumber-daya/tempat-ngopi` | Direktori kedai kopi: filter region, pencarian, detail Wi-Fi/harga/parkir/jam buka/peta. ✅ |
| PUB-11 | Semua | Tema terang/gelap, pengalih bahasa, tombol kembali ke atas, lightbox gambar. ✅ |

## B. Autentikasi & Akun (Laravel Fortify)

| ID | Kebutuhan |
| --- | --- |
| AUTH-01 | Login, registrasi, lupa/reset password, verifikasi email. ✅ |
| AUTH-02 | 2FA TOTP dengan konfirmasi + kode pemulihan; konfirmasi password untuk aksi sensitif. ✅ |
| AUTH-03 | Passkey (WebAuthn) sebagai login/konfirmasi. ✅ |
| AUTH-04 | Halaman keamanan akun di `/settings/profile` dan `/settings/security` (profil, password, 2FA, passkey). ✅ |

Seluruh `/admin/*` memakai middleware `auth` + `verified`.

## C. Panel Admin (`/admin`)

Pola umum: halaman indeks berupa **DataTable** (cari, urut, ukuran halaman 10/25/50/100) dengan **modal** tambah/ubah, hapus dengan konfirmasi, dan toast sukses lewat `Inertia::flash('toast', …)`. Pengecualian: Artikel memakai halaman form penuh.

| ID | Modul | Rute dasar | Catatan |
| --- | --- | --- | --- |
| ADM-01 | Dasbor | `/admin` | Ringkasan. |
| ADM-02 | Pendidikan | `/admin/educations` | Jenjang (`EducationLevel`), nilai akademik (`AcademicScoreType`), logo dari media. |
| ADM-03 | Organisasi | `/admin/organizations` | |
| ADM-04 | Karier | `/admin/careers` | |
| ADM-05 | Sertifikasi | `/admin/certifications` | Berkas (gambar/PDF) **wajib**, dari Pustaka Media. |
| ADM-06 | Penghargaan | `/admin/awards` | **Jenis** wajib (`AwardType`, 10 nilai); lihat `03-data-model.md`. |
| ADM-07 | Portofolio | `/admin/portfolios` | Gambar wajib; teknologi, kategori, karier (many-to-many); slug otomatis. |
| ADM-08 | Kategori Portofolio, Teknologi | `/admin/portfolio-categories`, `/admin/technologies` | |
| ADM-09 | Galeri & Ulasan Portofolio | `/admin/portfolio-galleries`, `/admin/portfolio-ratings` | Gambar galeri wajib (`image_url`). |
| ADM-10 | Artikel | `/admin/posts` (+create/edit) | Editor Tiptap (visual/HTML), gambar sampul, kategori, tag, slug, jadwal terbit. |
| ADM-11 | Kategori Artikel, Tag | `/admin/post-categories`, `/admin/tags` | |
| ADM-12 | Kedai Kopi | `/admin/coffee-places` | Peta (Leaflet) untuk lat/long; enum Wi-Fi & tingkat harga. |
| ADM-13 | Pengguna | `/admin/users` | Peran `UserRole`, avatar dari media. |
| ADM-14 | **Pustaka Media** | `/admin/media` | Lihat `05-media-library.md`. |

## D. Aturan lintas modul

- Validasi lewat `FormRequest` di `app/Http/Requests/Admin`; pesan kesalahan Bahasa Indonesia.
- Semua field file/gambar memakai komponen `MediaField` dan aturan `MediaPath`.
- Konten artikel (HTML dari editor) dirender apa adanya di halaman blog — hanya admin tepercaya yang boleh menulis.
- Perubahan perilaku wajib disertai tes Pest di `tests/Feature`.
