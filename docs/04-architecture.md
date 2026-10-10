# 04 — Arsitektur & Konvensi

## Stack

| Lapisan        | Teknologi                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| -------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Backend        | PHP 8.4, Laravel 13, Laravel Fortify (auth, 2FA, passkey), Inertia Laravel 3                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| Frontend       | Svelte 5 + Inertia Svelte 3, Bootstrap 5.3, Tiptap (WYSIWYG: blok ala Gutenberg — gambar berposisi, `callout`, `buttonBlock` — diedit lewat block bar; klik kanan membuka menu konteks (salin/tempel, format, ubah blok, duplikat/pindah/hapus); mode `minimal` tanpa blok; toolbar sticky, toggler di mobile, penyisip blok via `/` atau tombol `+`; gaya konten dibagi lewat `.wysiwyg-content-wrapper` di editor dan halaman publik; blok kode diwarnai langsung di editor (plugin `CodeBlockHighlight` di `editor-blocks.ts`) dan block bar memiliki pemilih bahasa + pratinjau, dropdown bahasa melayang di pojok kanan atas blok kode saat kursor berada di dalamnya, dan menu klik kanan memuat daftar "Bahasa: …"; blok kode `pre code` di halaman publik di-highlight oleh port highlight.js ringan di `resources/js/lib/highlight/` lewat action `use:highlightCode`, tema warna adalah port langsung tema highlight.js (GitHub otomatis terang/gelap + 10 tema pilihan per blok lewat atribut `data-code-theme` pada `<pre>`) di `css/components/_hljs-theme.scss`; blok kode juga punya pemilih tema di block bar, dropdown melayang, dan menu klik kanan), Leaflet (peta), svelte-select, SweetAlert2 (toast), Iconify/Lucide |
| Tipe terhubung | Laravel Wayfinder → `resources/js/wayfinder/*` (**generated, jangan diedit**; jalankan `php artisan wayfinder:generate`)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| Gambar         | Intervention Image lewat `App\Services\ImageService` (WebP, kompresi ke target KB)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| Build          | Vite+ (`bun run build`, `bun run dev`)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| Tes            | Pest 5 (`php artisan test --compact`), Vitest via Vite+ (`bun run test`; frontend: jsdom + `@testing-library/svelte`), Pint (`vendor/bin/pint --dirty --format agent`), Larastan                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| Deploy         | Docker image, Jenkins pipeline, blue/green (`scripts/deploy-bluegreen.sh`, `docker-compose.prod.yml`, `deploy/nginx`); Cloudflare worker `workers/image-cache`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |

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

## Pengujian frontend (Vitest)

- Konfigurasi di blok `test` pada `vite.config.ts` (lewat Vite+; alias `@` → `resources/js`, environment `jsdom`, setup `resources/js/tests/setup.ts`).
- Berkas tes berdampingan dengan kode: `resources/js/**/*.{test,spec}.ts` (mis. `lib/utils.test.ts`, `components/empty-state.test.ts`).
- Perintah: `bun run test` (sekali jalan), `bun run test:watch`, `bun run test:coverage`.
- Cakupan: seluruh frontend — `lib/` (helper, OCR, analytics, highlight, editor blocks), `components/` (termasuk `ui/`: field, picker, lightbox, editor teks, PDF viewer), `layouts/`, `pages/` (publik, auth, admin) dan bootstrap `app.ts`; ditambah tes setup Bootstrap 5 (`tests/bootstrap.test.ts`). Kode generated (`wayfinder`) dikecualikan dari coverage.
- `resources/js/tests/setup.ts` menyediakan stub API browser yang tidak ada di jsdom (`matchMedia`, `ResizeObserver`, `IntersectionObserver`, `Element.animate`, `Range` rect); `tests/helpers.ts` (`hrefOf`) dan `tests/stubs/*` untuk komponen berat (picker media, lightbox, PDF viewer).
- Plugin Laravel/Wayfinder dinonaktifkan saat `VITEST` (hanya transform Svelte).
- CI: `bun run test` dijalankan sekali lewat `composer ci:check` (GitHub Actions); Jenkins membangun target Docker `frontend-testing` (bercabang dari stage `frontend-source`, tanpa `build:ssr`) paralel dengan target Pest pada tahap Test.
- Perubahan logika frontend menambah/memperbarui tes Vitest yang relevan; tes backend tetap Pest.

## Konfigurasi & lingkungan

- `.env.example`: SQLite, session/queue/cache berbasis `database`.
- Produksi: SQLite (`/www/dk_project/cakadi.web.id/storage/database/database.sqlite`, bind mount `./storage/database` → `/app/storage/database`, WAL, dibagi blue/green dan bisa diedit langsung dari folder host); seluruh path deploy di `/www/dk_project/cakadi.web.id`; `.env` penuh disuplai sebagai Jenkins secret file; Nginx tidak disentuh pipeline.
- CI: `.github/workflows/tests.yml` (PHP 8.5) menjalankan tes pada `push` ke `main` dan PR.

## Aturan proses

1. Tanpa migrasi `alter`; ubah migrasi `create_*` lalu `migrate:fresh --seed`.
2. Jalankan Pint untuk file PHP yang berubah dan tes yang terdampak sebelum selesai.
3. Perubahan perilaku produk → perbarui `docs/*` yang relevan.
4. Jangan menambah dependensi atau folder dasar baru tanpa persetujuan.

## Minifikasi HTML

Middleware `MinifyHtmlResponse` (grup `web`) memadatkan spasi dan menghapus komentar pada respons HTML **hanya di production**. Blok `pre`, `textarea`, `script`, `style`, dan atribut Inertia `data-page` tidak diubah.

## Halaman error

Respons error 400/401/403/404/419/429/500/503 pada request web (non-JSON) dirender sebagai halaman Inertia `resources/js/pages/error.svelte` lewat `$exceptions->respond()` di `bootstrap/app.php`. Judul, teks, dan ilustrasi (`public/images/errors/*.svg`, diambil dari proyek Nuxt) dipetakan di `App\Services\ErrorPageService`. Saat `APP_DEBUG=true`, error 500 tetap memakai halaman debug Laravel.

## Notifikasi (toast)

- `resources/js/lib/toast.ts` membungkus SweetAlert2 (`toast.success/error/warning/info/cancelled`); gunakan helper ini, jangan memanggil SweetAlert2 langsung.
- `resources/js/lib/flash-toast.ts` (dipasang di `app.ts`) menampilkan toast otomatis untuk: flash `toast` dari server, validasi gagal (`error`), error HTTP 419/403/429/lainnya (`httpException`), dan koneksi putus (`networkError`).
- Aksi non-Inertia (`fetch`, mis. upload media, salin tautan, muat pustaka media) memanggil `toast` secara eksplisit.

## Google Analytics (GA4)

- Tag `gtag.js` dirender oleh `resources/views/components/analytics.blade.php` hanya bila ID terisi: setting `google_analytics_id` (admin → Pengaturan Sistem → SEO & Analitik) lebih diutamakan, fallback `GOOGLE_ANALYTICS_ID`. `send_page_view` dimatikan karena situs adalah SPA Inertia.
- `resources/js/lib/analytics.ts` (dipasang di `app.ts`) mengirim `page_view` pada setiap `navigate` Inertia, serta event otomatis: `click` (tautan keluar), `file_download`, `contact_email`, `contact_phone`, `form_submit`, dan `scroll_depth` (25/50/75/100%).
- Atribusi UTM: `captureUtm()` membaca `utm_source/medium/campaign/term/content/id` dari URL pendaratan, menyimpannya di `sessionStorage` (navigasi SPA membuang query string), lalu mengirimnya via `gtag('set', …)` sebagai parameter resmi GA4 `campaign_source/medium/name/term/content/id`.
- Event kontekstual (rekomendasi GA4) pada setiap `navigate`: `search` (query `search`/`q`/`query`), `view_item` (halaman `*/show`), `page_error` (halaman error), `generate_lead` (POST sukses formulir kontak), `comment_submit` / `rating_submit` (POST sukses komentar blog / ulasan portofolio), `share` (klik tautan bagikan WhatsApp/Facebook/X/Telegram/LinkedIn), `exception` (error JS & promise yang tidak tertangani), `language_change` dan `theme_toggle` (atribut `data-track`).
- Halaman sitemap (XSL, `misc/sitemaps/style.blade.php`) memuat `gtag.js` sendiri (bila ID terisi) dan mengirim `page_view` (`content_group: sitemap`), `search` (kolom filter, debounce 800 ms) dan `select_content` (klik URL).
- Event kustom: atribut `data-track="nama_event"` (opsional `data-track-label`), action Svelte `use:track`, atau `trackEvent(nama, params)`.
- Path privat (admin, settings, auth) tidak pernah dilaporkan.
