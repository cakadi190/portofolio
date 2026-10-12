# 09 — Log Aktivitas & Analitik Pengunjung

## Log aktivitas (audit trail)

- Paket: `spatie/laravel-activitylog` ^5.1; model `App\Models\Activity`; tabel `activity_log` (migrasi `create_activity_log_table`, indeks: `created_at`, `(log_name, created_at)`, `(event, created_at)`, `(outcome, created_at)`, `ip_address`, `request_id`, serta morph subject/causer).
- Kolom tambahan: `actor_type` (user/guest/system), `outcome` (success/failure), `request_id`, `http_method`, `route_name`, `http_status` (belum diisi), `ip_address`, `user_agent`. IP mengikuti konfigurasi trusted proxy (`TRUSTED_PROXIES`).
- Seluruh `properties` dan `attribute_changes` disanitasi rekursif oleh `App\Support\ActivitySanitizer` (kunci mengandung password/secret/token/key/credential dst. → `[REDACTED]`, string dipotong 500 karakter).
- Model terinstrumentasi lewat trait `App\Concerns\RecordsActivity` (hanya field yang berubah; password/token dikecualikan): semua model konten, `User`, `ContactMessage`, `Media`, dll. Event eksplisit lewat `App\Services\AuditLogger`: login, logout, login gagal (maks. 1 entri/menit per IP+identitas, identitas di-hash), lockout, registrasi, reset/ubah sandi, 2FA, `role_changed`, `settings_updated` (hanya nama kunci), `activity_exported`.
- Kebijakan kegagalan: kegagalan menulis log non-kritis tidak mematahkan request (dilaporkan ke log aplikasi); `critical: true` melempar exception. Perubahan model ikut transaksi bisnis (rollback ⇒ tidak ada log); event kegagalan dicatat setelah rollback.
- Tidak dicatat: setiap request HTTP (itu tugas analitik pengunjung), perubahan massal lewat query builder, dan job latar belakang (belum ada alur yang memerlukannya).
- Viewer: `/admin/activity-logs` (khusus admin) — filter teks, kategori, event, hasil, pengguna, tipe/ID objek, IP, rentang waktu; urut & paginasi di server (maks. 100/halaman); detail `/admin/activity-logs/{id}`; ekspor CSV maks. 5.000 baris (diaudit sendiri). Aplikasi ini satu-tenant, sehingga tidak ada filter tenant.
- Retensi: `ACTIVITYLOG_RETENTION_DAYS` (bawaan 365), dijalankan mingguan oleh `activitylog:clean`. Matikan total dengan `ACTIVITYLOG_ENABLED=false`.

## Analitik pengunjung

Middleware `TrackVisitor` (grup `web`, berjalan setelah respons dikirim) mencatat tayangan GET 200 publik. Dikecualikan: `admin`, `settings`, `up`, `storage`, `build`, `api`, sitemap/robots, path berekstensi (aset), kunjungan non-Inertia berbentuk JSON, dan prefetch.

| Metrik | Definisi |
| --- | --- |
| Tayangan halaman | Jumlah tayangan manusia (`visitor_daily_stats`, `is_bot=false`) |
| Pengunjung unik | Cookie pihak pertama `visitor_id` (UUID, 1 tahun, HttpOnly, SameSite=Lax) yang berbeda. Bukan orang; hapus cookie ⇒ pengunjung baru. IP tidak dipakai sebagai identitas |
| Kunjungan (sesi) | Rangkaian tayangan tanpa jeda > `visitor_session_minutes` (30) |
| Pengunjung berulang | Pengunjung dengan > 1 kunjungan |
| Harian / bulanan | Dari `visitor_daily_stats` dan `visitor_days` |
| Bot | UA cocok pola bot/crawler atau kosong; dicatat terpisah atau diabaikan (`visitor_bot_policy`) |

Desain tulis (SQLite): satu transaksi pendek per tayangan memperbarui baris pengunjung, sesi, `visitor_days`, dan penghitung harian (bukan satu baris per request); dedupe 2 detik; batas 20 pengunjung baru/jam per klien tanpa cookie; retry terbatas (3x) saat database terkunci; tanpa panggilan jaringan di dalam transaksi; ringkasan di-cache 60 detik. Pengayaan IPInfo berjalan lewat job antrean `EnrichIpAddress` (unik per IP, `afterResponse`). Antrean memakai tabel `jobs` pada SQLite yang sama sehingga tidak menghilangkan kontensi tulis; jalankan `php artisan queue:work`. Tanpa worker, tayangan tetap tercatat tetapi pengayaan tidak berjalan.

Mode WAL sudah dipakai produksi (lihat `04`); atur `DB_BUSY_TIMEOUT` (mis. 5000) agar kunci sementara menunggu. Angka throughput nyata belum diukur di produksi: tes lokal hanya menunjukkan ratusan tayangan/detik berurutan pada SQLite in-memory.

### IPInfo

Endpoint resmi `api.ipinfo.io/lite/{ip}` (plan Lite) atau `/lookup/{ip}` (Core/Plus), token via header `Authorization: Bearer` (tidak pernah di URL). Timeout 3/5 detik, retry 2x hanya untuk error koneksi, HTTP 429 menjeda panggilan 10 menit, kegagalan disimpan 15 menit tanpa metadata. IP privat/reserved ditandai `private` tanpa panggilan. Hasil disimpan di `ip_lookups` selama `ipinfo_cache_ttl_days`. Patuhi batas plan & ketentuan retensi IPInfo.

### Retensi & privasi

`visitors:prune` (harian 03:15, batch kecil) menghapus pengunjung/sesi/IP-cache yang lebih lama dari `visitor_retention_days`. Statistik harian agregat (tanpa data pribadi) tetap. Log aktivitas tidak tersentuh oleh retensi pengunjung. Matikan penyimpanan IP dengan `visitor_store_ip=0`.

## Pengaturan baru (Pengaturan Sistem)

| Kunci | Bawaan |
| --- | --- |
| `visitor_tracking_enabled` | aktif |
| `visitor_bot_policy` | `separate` (atau `exclude`) |
| `visitor_store_ip` | aktif |
| `visitor_excluded_paths` | kosong (ditambah ke bawaan) |
| `visitor_session_minutes` | 30 |
| `visitor_retention_days` | 180 |
| `ipinfo_enabled` | nonaktif |
| `ipinfo_plan` | `lite` |
| `ipinfo_token` (rahasia) | — |
| `ipinfo_cache_ttl_days` | 30 |

Rahasia (`*_token`, `turnstile_secret_key`) tersimpan terenkripsi, tidak pernah dikirim balik ke browser; kolom kosong mempertahankan nilai, kotak "Hapus nilai tersimpan" menghapusnya. Tombol uji koneksi IPInfo memanggil IP dokumentasi saja.

## Pemantauan error (Sentry)

- Paket: `sentry/sentry-laravel` (backend) dan `@sentry/svelte` (frontend). Seluruh pengaturan ada di Pengaturan Sistem → tab "Pemantauan Error (Sentry)" (grup `monitoring`); nilai `.env` (`SENTRY_LARAVEL_DSN`, `SENTRY_FRONTEND_DSN`, `SENTRY_RELEASE`, `SENTRY_ENVIRONMENT`) hanya cadangan saat setting kosong.

| Kunci | Bawaan |
| --- | --- |
| `sentry_enabled` | nonaktif (sakelar utama; tanpa DSN tidak ada yang dikirim) |
| `sentry_backend_dsn` (rahasia) | — |
| `sentry_frontend_dsn` (publik) | — |
| `sentry_environment` / `sentry_release` | environment aplikasi / kosong |
| `sentry_traces_sample_percent` | 10 |
| `sentry_profiles_sample_percent` | 0 (backend butuh ekstensi Excimer; frontend butuh header `Document-Policy: js-profiling`) |
| `sentry_enable_logs` | nonaktif (menambahkan channel `sentry_logs` ke stack log) |

- Backend: `SentryConfigurator` menyalin setting ke config pada callback `booting` (sebelum SDK boot; DSN harus ada saat boot). Perubahan setting berlaku pada request berikutnya setelah cache setting dibersihkan; worker antrean/Octane perlu di-restart. Exception tak tertangani dilaporkan lewat `Integration::handles()` di `bootstrap/app.php`.
- Frontend: DSN publik dan sampling dirender sebagai `<meta name="sentry-config">` (komponen `x-sentry`) bersama `sentry-trace`/`baggage` agar trace browser tersambung ke request server; `resources/js/lib/sentry.ts` menginisialisasi SDK. Trace hanya diteruskan ke origin sendiri.
- Pengayaan (tanpa data pribadi): tag `request_id` (sama dengan header `X-Request-Id` dan log aktivitas), `route`, `http_status`, `actor` (guest/user/redaktur/admin), `area`, `inertia`, `inertia_component`, `page_path`, `server_request_id`; pengguna hanya `id` semu (HMAC-SHA256 dengan `APP_KEY`, 16 karakter) + segmen peran.
- Privasi (UU PDP/GDPR): `send_default_pii=false`; `dataCollection` frontend mematikan info pengguna, cookie, body, dan query string. `SentryEventScrubber` (`before_send*`, breadcrumb, log) membuang cookie, body request, header selain allowlist, email/IP/nomor telepon/NIK/JWT/Bearer dari teks, serta nilai pada kunci sensitif. Frontend tidak melacak performa/profil bila browser mengirim Do Not Track atau Global Privacy Control. Binding SQL tidak dikirim.
- Yang harus dilakukan di dashboard Sentry: aktifkan "Prevent Storing of IP Addresses" pada kedua proyek, atur retensi data sesuai kebijakan, dan batasi akses anggota. Unggah source map belum dipasang (butuh `SENTRY_AUTH_TOKEN` dan plugin Vite Sentry).
- CSP report-only mengizinkan `connect-src` ke `*.ingest.sentry.io`, `*.ingest.us.sentry.io`, `*.ingest.de.sentry.io`.

## Deploy

Migrasi aditif baru (tabel baru, aman untuk `migrate --force`): `create_activity_log_table`, `create_visitor_analytics_tables`. Rollback: `migrate:rollback --step=2` (menghapus data log/analitik). Contoh env: `ACTIVITYLOG_RETENTION_DAYS=365`, `DB_BUSY_TIMEOUT=5000`. Perlu worker antrean dan scheduler (`schedule:run`).

## Aturan integrasi aaPanel

Integrasi aaPanel sengaja tidak ada (dihapus atas permintaan pemilik).
