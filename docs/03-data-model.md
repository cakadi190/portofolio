# 03 — Model Data

Database: SQLite (dev/test), MySQL (produksi). Semua tabel konten memakai `id` auto-increment + `timestamps`. Skema dibangun dari migrasi `create_*` saja (tanpa `alter`), sehingga `php artisan migrate:fresh --seed` adalah jalur resmi pembuatan ulang.

## Tabel

| Tabel | Kolom utama | Catatan |
| --- | --- | --- |
| `users` | name, email, password, account_type (`UserRole`), phone, gender (`Gender`), avatar | + tabel `sessions`, `passkeys`, kolom 2FA (Fortify) |
| `educations` | name, logo, website, level (`EducationLevel`), grade, department, study_program, start_date, end_date, place, academic_score_{type,label,value,scale} | |
| `organizations` | name, description, start_date, end_date | |
| `careers` | position, company, location, start_date, end_date | M2M `career_portfolio` |
| `certifications` | title, issuer, issued_at, expires_at, credential_id, credential_url, **file** | `is_pdf` (accessor) |
| `awards` | event_name, title, **type** (`AwardType`, default `competition`, index), year, rank, awarded_at | `icon` (accessor) |
| `portfolios` | name, slug (unique), **image**, short_desc, description, demo_link, source_code, is_private | M2M teknologi, kategori, karier |
| `portfolio_categories`, `technologies` | name (+ color / unique) | pivot `portfolio_category_portfolio`, `portfolio_technology` |
| `portfolio_galleries` | portfolio_id, **image_url**, description | cascade delete |
| `portfolio_ratings` | portfolio_id, rating, comment | cascade delete |
| `posts` | title, slug (unique), excerpt, content (HTML), **cover_image**, is_published, published_at | M2M tag & kategori |
| `post_categories`, `tags` | name (+ color / unique) | pivot `post_tag`, `post_category_post` |
| `coffee_places` | name, address, description, latitude, longitude, map_url, **image**, wifi_provider, wifi_speed, price_tier, park_fee, opens_at, closes_at, region, is_recommended | |
| `media` | user_id, disk, path (unique), name, alt, mime_type, size, width, height | Pustaka Media; lihat `05` |

Kolom **tebal** menyimpan *path* berkas yang menunjuk ke `media.path` (bukan foreign key; lihat `05`).

## Enum (`app/Enums`)

| Enum | Nilai |
| --- | --- |
| `UserRole` | admin, user |
| `Gender` | male, female |
| `EducationLevel` | kg, es, jhs, shs, university |
| `AcademicScoreType` | gpa, school_exam |
| `WifiSpeed` | weak, medium, strong |
| `CafePriceTier` | cheap, medium, expensive |
| `AwardType` | competition, certification, awardee, recognition, achievement, scholarship, appointment, honors, publication, contribution |

### `AwardType`

| Nilai | Label | Untuk | Contoh |
| --- | --- | --- | --- |
| `competition` | Kompetisi / Lomba | Kompetisi/lomba | Juara 1 Hackathon |
| `certification` | Sertifikasi | Sertifikasi resmi | AWS Certified Developer |
| `awardee` | Awardee | Penerima program/penghargaan | Awardee Beasiswa X |
| `recognition` | Pengakuan | Pengakuan non-kompetitif | Best Employee Recognition |
| `achievement` | Pencapaian | Pencapaian tertentu | Top 10 Finalist |
| `scholarship` | Beasiswa | Beasiswa | Penerima Beasiswa Unggulan |
| `appointment` | Penunjukan | Penunjukan resmi | Appointed as Student Ambassador |
| `honors` | Penghargaan Kehormatan | Kehormatan/akademik | Cum Laude, Dean's List |
| `publication` | Publikasi | Prestasi lewat publikasi | Best Paper Award |
| `contribution` | Kontribusi | Penghargaan atas kontribusi | Outstanding Contribution Award |

Aturan ikon `Award::icon`: jenis `certification`, atau tanpa peringkat, atau judul memuat "certif/sertif" → `mdi:certificate`; peringkat ≤ 3 → `fa6-solid:trophy`; selain itu `fa6-solid:medal`.

## Seeder

`DatabaseSeeder` memanggil seeder per entitas (mis. `AwardSeeder` memberi `type` eksplisit tiap baris). Data seed lama berisi path aset statis (mis. `/images/...`) yang bukan item `media`; `MediaPath` tetap menerima nilai yang sudah tersimpan pada record tersebut.
