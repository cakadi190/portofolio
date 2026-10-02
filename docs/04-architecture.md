# 04 — Arsitektur & Konvensi

## Stack

| Lapisan | Teknologi |
| --- | --- |
| Backend | PHP 8.4, Laravel 13, Laravel Fortify (auth, 2FA, passkey), Inertia Laravel 3 |
| Frontend | Svelte 5 + Inertia Svelte 3, Bootstrap 5.3, Tiptap (WYSIWYG: blok ala Gutenberg — gambar berposisi, `callout`, `buttonBlock` — diedit lewat block bar; klik kanan membuka menu konteks (salin/tempel, format, ubah blok, duplikat/pindah/hapus); mode `minimal` tanpa blok; toolbar sticky, toggler di mobile, penyisip blok via `/` atau tombol `+`; gaya konten dibagi lewat `.wysiwyg-content-wrapper` di editor dan halaman publik), Leaflet (peta), svelte-select, SweetAlert2 (toast), Iconify/Lucide |
| Tipe terhubung | Laravel Wayfinder → `resources/js/wayfinder/*` (**generated, jangan diedit**; jalankan `php artisan wayfinder:generate`) |
| Gambar | Intervention Image lewat `App\Services\ImageService` (WebP, kompresi ke target KB) |
| Build | Vite+ (`bun run build`, `bun run dev`) |
| Tes | Pest 5 (`php artisan test --compact`), Pint (`vendor/bin/pint --dirty --format agent`), Larastan |
| Deploy | Docker image, Jenkins pipeline, blue/green (`scripts/deploy-bluegreen.sh`, `docker-compose.prod.yml`, `deploy/nginx`); Cloudflare worker `workers/image-cache` |

## Struktur kode

```
app/Enums/                    Enum + trait HasEnumOptions/HasEnumValues/HasLabel
app/Http/Controllers/         Controller situs publik (+ Settings/)
app/Http/Controllers/Admin/   CRUD admin (Inertia), memakai trait PaginatesTables
app/Http/Requests/Admin/      FormRequest per modul
app/Models/                   Eloquent model (+ factory di database/factories)
app/Rules/MediaPath.php       Validasi referensi berkas ke Pustaka Media
app/Services/                 ImageService, MediaService
routes/web.php | admin.php | settings.php
resources/js/pages/           Halaman Inertia (admin/<modul>/index.svelte, dst.)
resources/js/components/      admin/ (DataTable, header, hapus), media/ (picker, field), ui/ (field, select, editor, peta…)
resources/js/lib/             utils.ts (storageUrl, formatDate), media.ts (uploadMedia)
tests/Feature/                Tes fitur per modul (Admin/, Auth/, Settings/, Support/)
```

## Konvensi penamaan berkas

- **PHP**: PascalCase untuk class (`MediaService.php`, `MediaController.php`), mengikuti konvensi Laravel.
- **Frontend** (`resources/js`: halaman, komponen, layout, lib, tipe): semua berkas dan folder **kebab-case** (`media-picker-modal.svelte`, `admin/settings/profile.svelte`). Nama halaman di `Inertia::render()` dan tes (`->component(...)`) harus sama persis dengan path berkas. Nama komponen yang diimpor di dalam kode tetap PascalCase.
- **Berkas hasil unggah** (Pustaka Media): kebab-case, lihat `05-media-library.md`.

## Pola backend

- Controller admin: `index` (Inertia + `paginateTable` + `tableFilters`), `store`, `update`, `destroy`; respons berupa redirect dengan `Inertia::flash('toast', ['type' => success|info|warning|error, 'message' => ...])`. Rute lewat `Route::resource(...)->only([...])` di `routes/admin.php`.
- Validasi di `FormRequest`; enum divalidasi dengan `Rule\Enum`; opsi enum dikirim ke halaman lewat `Enum::options()`.
- Query pencarian memakai `like` dengan escape; pengurutan dibatasi daftar kolom yang diizinkan.
- Tipe PHP eksplisit, constructor promotion, kurung kurawal selalu dipakai (lihat `CLAUDE.md`).

## Pola frontend

- Halaman indeks admin: `AdminPageHeader` + `DataTable` + `FormModal` berisi `<Form {...store.form()}>` Inertia dengan `Field.*`.
- Fungsi rute selalu dari Wayfinder (`@/wayfinder/routes/admin/<modul>`), bukan string URL manual.
- Field berkas: `MediaField` (hidden input berisi path). Editor: `RichTextEditor` + `MediaPickerModal`.
- Teks UI Bahasa Indonesia.

## Konfigurasi & lingkungan

- `.env.example`: SQLite, session/queue/cache berbasis `database`.
- Produksi: MySQL native di host, `.env` penuh disuplai sebagai Jenkins secret file; Nginx tidak disentuh pipeline.
- CI: `.github/workflows/tests.yml` (PHP 8.5) menjalankan tes pada `push` ke `main` dan PR.

## Aturan proses

1. Tanpa migrasi `alter`; ubah migrasi `create_*` lalu `migrate:fresh --seed`.
2. Jalankan Pint untuk file PHP yang berubah dan tes yang terdampak sebelum selesai.
3. Perubahan perilaku produk → perbarui `docs/*` yang relevan.
4. Jangan menambah dependensi atau folder dasar baru tanpa persetujuan.

## Minifikasi HTML

Middleware `MinifyHtmlResponse` (grup `web`) memadatkan spasi dan menghapus komentar pada respons HTML **hanya di production**. Blok `pre`, `textarea`, `script`, `style`, dan atribut Inertia `data-page` tidak diubah.

## Notifikasi (toast)

- `resources/js/lib/toast.ts` membungkus SweetAlert2 (`toast.success/error/warning/info/cancelled`); gunakan helper ini, jangan memanggil SweetAlert2 langsung.
- `resources/js/lib/flash-toast.ts` (dipasang di `app.ts`) menampilkan toast otomatis untuk: flash `toast` dari server, validasi gagal (`error`), error HTTP 419/403/429/lainnya (`httpException`), dan koneksi putus (`networkError`).
- Aksi non-Inertia (`fetch`, mis. upload media, salin tautan, muat pustaka media) memanggil `toast` secara eksplisit.
