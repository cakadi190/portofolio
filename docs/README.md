# Dokumentasi Produk — Catatan Cakadi

Dokumen ini adalah **PRD (Product Requirements Document)** hasil reverse engineering dari kode proyek per 2026-10-02. Kode adalah sumber kebenaran untuk perilaku; dokumen ini adalah sumber kebenaran untuk **maksud** dan **batasan** produk.

> **Untuk semua AI Agent:** baca dokumen di folder ini sebelum merencanakan atau mengubah kode. Minimal baca `01-product-overview.md` dan dokumen yang relevan dengan area kerja Anda (lihat tabel di bawah). Jika perubahan Anda mengubah perilaku produk, skema data, atau aturan bisnis, **perbarui dokumen terkait dalam perubahan yang sama**.

| Dokumen                                                        | Isi                                                   | Baca saat                       |
| -------------------------------------------------------------- | ----------------------------------------------------- | ------------------------------- |
| [01-product-overview.md](01-product-overview.md)               | Visi, persona, tujuan, ruang lingkup, non-goals       | Selalu                          |
| [02-functional-requirements.md](02-functional-requirements.md) | Kebutuhan fungsional situs publik, admin, autentikasi | Menambah/mengubah fitur         |
| [03-data-model.md](03-data-model.md)                           | Tabel, relasi, enum, aturan data                      | Menyentuh model/migrasi/seeder  |
| [04-architecture.md](04-architecture.md)                       | Stack, struktur kode, konvensi, deployment            | Menulis kode apa pun            |
| [05-media-library.md](05-media-library.md)                     | Spesifikasi Pustaka Media (file management terpusat)  | Menyentuh upload/gambar/WYSIWYG |
| [06-gaps-and-roadmap.md](06-gaps-and-roadmap.md)               | Celah yang diketahui, utang teknis, ide lanjutan      | Merencanakan pekerjaan          |

## Aturan kerja penting (ringkasan)

- Migrasi: **jangan buat migrasi `alter table`**. Ubah migrasi `create_*` yang ada agar `migrate:fresh` di produksi tetap berhasil.
- Upload file: selalu lewat Pustaka Media (`MediaService`, `MediaField`), jangan menyimpan file langsung dari controller.
- Enum baru: gunakan trait `HasEnumOptions`/`HasEnumValues` di `app/Enums`, label Bahasa Indonesia.
- Teks antarmuka berbahasa Indonesia; kode, nama kolom, dan rute admin berbahasa Inggris.
- Ikuti `CLAUDE.md` / `AGENTS.md` (Laravel Boost guidelines) untuk konvensi kode dan pengujian.
