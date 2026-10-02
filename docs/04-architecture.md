# 04 — Arsitektur & Konvensi

## Stack

| Lapisan | Teknologi |
| --- | --- |
| Backend | PHP 8.4, Laravel 13, Laravel Fortify (auth, 2FA, passkey), Inertia Laravel 3 |
| Frontend | Svelte 5 + Inertia Svelte 3, Bootstrap 5.3, Tiptap (WYSIWYG), Leaflet (peta), svelte-select, Iconify/Lucide |
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

## Pola backend

- Controller admin: `index` (Inertia + `paginateTable` + `tableFilters`), `store`, `update`, `destroy`; respons berupa redirect dengan `Inertia::flash('toast', [...])`. Rute lewat `Route::resource(...)->only([...])` di `routes/admin.php`.
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
