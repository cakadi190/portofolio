# 01 — Gambaran Produk

## Ringkasan

**Catatan Cakadi** (`cakadi.web.id`) adalah situs personal-branding milik Amir Zuhdi Wibowo (Cakadi): gabungan **CV/portofolio online**, **blog**, dan **direktori tempat ngopi** untuk kerja remote, dengan **panel admin** (CMS) untuk mengelola seluruh kontennya. Satu pemilik konten; pengunjung publik hanya membaca.

## Tujuan

1. Menampilkan profil profesional (pendidikan, karier, organisasi, penghargaan, sertifikasi, portofolio) yang mudah diperbarui tanpa menyentuh kode.
2. Menerbitkan artikel blog dengan editor WYSIWYG ala WordPress.
3. Berbagi rekomendasi kedai kopi (Wi-Fi, harga, parkir, peta) sebagai sumber daya komunitas.
4. Mengelola semua gambar/dokumen di satu **Pustaka Media** terpusat yang dapat dipakai ulang.

## Persona

| Persona | Kebutuhan |
| --- | --- |
| **Pengunjung / calon klien / rekruter** | Cepat menilai kemampuan, melihat portofolio, membaca blog, menghubungi pemilik. Tanpa login. |
| **Admin (pemilik)** | Mengelola semua konten lewat `/admin`, aman (2FA, passkey), nyaman (tabel cari/urut, modal form, picker media). |

## Ruang lingkup (in scope)

- Situs publik berbahasa Indonesia: Beranda, Karier, Pendidikan, Penghargaan, Kontak, Layanan, Blog, Portofolio, Tentang (saya/situs/skill), Tempat Ngopi.
- Panel admin CRUD untuk seluruh entitas konten + Pustaka Media + manajemen pengguna.
- Autentikasi: login, registrasi, reset password, verifikasi email, 2FA (TOTP), passkey.
- Mode terang/gelap dan pengalih bahasa di antarmuka publik.

## Di luar lingkup (non-goals)

- Komentar pengunjung, e-commerce, multi-tenant, multi-penulis dengan alur persetujuan.
- Peran/izin granular (saat ini hanya `admin` dan `user`; lihat gap di `06`).
- API publik terdokumentasi (halaman dirender lewat Inertia).

## Metrik keberhasilan

- Admin dapat menambah artikel/portofolio lengkap dengan gambar dalam < 3 menit.
- Halaman publik cepat (gambar dikompresi ke WebP, target ≤ 300–400 KB per berkas).
- `migrate:fresh --seed` menghasilkan situs yang langsung terisi dan berjalan di produksi.
