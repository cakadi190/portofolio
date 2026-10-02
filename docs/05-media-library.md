# 05 — Pustaka Media (File Management Terpusat)

Terinspirasi pola "centralized file model" (satu model/tabel untuk semua berkas) dan pengalaman media WordPress: unggah sekali, pilih/pakai ulang di mana saja.

## Prinsip

1. **Satu pintu masuk.** Semua unggahan lewat `POST /admin/media` → `MediaService::store()`. Controller modul lain **tidak** menangani upload.
2. **Referensi berupa path.** Kolom di model lain (mis. `posts.cover_image`) tetap menyimpan string path yang sama dengan `media.path`. Tidak ada FK/pivot, sehingga data lama tetap valid dan halaman publik tidak berubah.
3. **Berkas bersama.** Satu media boleh dipakai banyak record, maka menghapus record **tidak** menghapus berkas; berkas hanya hilang saat dihapus dari Pustaka Media.
4. **Aman dihapus.** Media yang masih dipakai tidak dapat dihapus (lihat "Pelacakan pemakaian").

## Pemrosesan unggahan

| Jenis | Perlakuan |
| --- | --- |
| Gambar (JPG/PNG/WebP/GIF) | Skala turun maks 1920×1920, encode WebP kualitas 80, kompres hingga ≤ ~400 KB. Lebar/tinggi dicatat. |
| PDF | Disimpan apa adanya. |
| Lainnya | Ditolak (validasi `mimes:jpg,jpeg,png,webp,gif,pdf`, maks 10 MB). |

Penyimpanan: disk `public`, folder `media/YYYY/MM/`, nama berkas **kebab-case**: slug nama asli + akhiran acak 8 karakter huruf kecil/angka (contoh `Foto Profil 2024.JPG` → `media/2026/10/foto-profil-2024-a1b2c3d4.webp`; nama kosong/tak valid → `file-<acak>`). Ekstensi huruf kecil; gambar selalu `.webp`. Nama asli tetap disimpan di kolom `media.name`. URL publik `/storage/<path>` via `ImageService::url()`.

## Komponen

| Bagian | Berkas |
| --- | --- |
| Model/tabel | `app/Models/Media.php`, migrasi `create_media_table`, `MediaFactory` |
| Service | `app/Services/MediaService.php` (`store`, `kebabFilename`, `usageCount`, `delete`, peta `USAGES`) |
| Controller | `Admin/MediaController` — `index` (Inertia), `browse` (JSON picker), `store` (JSON), `update` (nama/alt), `destroy` |
| Validasi | `Admin/MediaRequest`, `App\Rules\MediaPath` |
| UI perpustakaan | `resources/js/pages/admin/media/index.svelte` (grid, cari, filter jenis, unggah multi/drag-drop, ubah detail, salin URL, hapus) |
| UI picker | `components/media/media-picker-modal.svelte` (cari, paginasi, unggah, pilih) |
| UI field | `components/media/media-field.svelte` (ganti `FileDropzone`, mendukung `accept="image" \| "all"`, `required`) |
| WYSIWYG | `components/ui/rich-text-editor.svelte`: tombol gambar membuka picker; tempel/seret gambar mengunggah ke pustaka; gambar disimpan sebagai `<figure class="wp-block-image is-align-*">` (posisi kiri/tengah/kanan, lebar %, alt, keterangan) yang diatur lewat block bar; ekstensi blok di `resources/js/lib/editor-blocks.ts` |
| Klien | `resources/js/lib/media.ts` (`uploadMedia`, `formatBytes`), tipe `resources/js/types/media.ts` |

## Rute

`GET admin/media` · `GET admin/media/browse` (JSON, `?search=&type=image|document&page=`) · `POST admin/media` (`file`) · `PUT admin/media/{media}` · `DELETE admin/media/{media}`

## Pelacakan pemakaian

`MediaService::USAGES` memetakan `tabel → kolom exact` (path sama persis) dan `kolom like` (path tertanam di HTML: `posts.content`, `portfolios.description`). **Saat menambah kolom berkas baru di model mana pun, daftarkan di `USAGES`** dan pakai `MediaField` + `new MediaPath($this->route('...')?->kolom)` di FormRequest.

## Menambah field berkas ke model baru (resep)

1. Kolom `string` (nullable bila opsional) di migrasi `create_*`.
2. FormRequest: `'kolom' => ['required|nullable', 'string', new MediaPath($this->route('model')?->kolom)]`.
3. Form Svelte: `<MediaField name="kolom" value={item.kolom} accept="image" />`.
4. Tambahkan ke `MediaService::USAGES`.
5. Controller: cukup `$request->validated()`; tidak ada logika upload.
6. Tes: buat `Media::factory()->create()` dan kirim `->path`.

## Batasan yang diketahui

- Belum ada folder/kategori media, versi/ganti berkas, atau varian ukuran (thumbnail).
- Pelacakan pemakaian berbasis pemindaian kolom, bukan relasi; kolom tak terdaftar tidak terlindungi.
- Belum ada pembersihan media yatim otomatis.

## Seeder

Seeder yang membawa gambar (`EducationSeeder`, `CoffeePlaceSeeder`, `PortfolioSeeder`, `PostSeeder` dengan sampel `public/images/posts/*.webp`) mendaftarkan berkas bawaan `public/images/...` lewat `MediaService::storeFromPublicPath()`, sehingga gambar tampil di Pustaka Media dan kolom modelnya menyimpan `media.path` hasilnya (diproses WebP seperti unggahan biasa).
