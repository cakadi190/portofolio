# 08 — Klarifikasi (Hak Jawab) & Penyangkalan (Disclaimer)

Spesifikasi produk modul Klarifikasi. Kebutuhan fungsional ringkas ada di `02` (PUB-17, ADM-19) dan skema di `03`; dokumen ini memuat **tujuan, aturan editorial, siklus hidup, dan celah**. Status: ✅ ada di kode, ⬜ usulan (belum ada).

## 1. Tujuan & prinsip

- Memberi pemilik situs satu tempat resmi untuk menanggapi klaim/isu yang beredar tentang dirinya, dengan bukti yang bisa ditelusuri.
- Transparan: klaim dikutip apa adanya, vonis eksplisit, bukti ditautkan, koreksi dicatat (tidak diam-diam diubah).
- Jujur soal posisi: ini **pernyataan pihak yang bersangkutan**, bukan pemeriksaan fakta independen (dinyatakan di tiap halaman detail ✅).
- Bukan forum debat: tidak ada komentar maupun formulir laporan publik khusus (non-goal); koreksi masuk lewat `/contact`.

## 2. Vonis (`ClarificationVerdict`)

| Nilai               | Label                      | Warna     | Arti                                                                    | Aturan terkait                           |
| ------------------- | -------------------------- | --------- | ----------------------------------------------------------------------- | ---------------------------------------- |
| `false`             | Tidak Benar                | danger    | Klaim keliru; bukti membantahnya.                                       | —                                        |
| `misleading`        | Menyesatkan                | warning   | Ada fakta, tetapi disajikan tanpa konteks sehingga menimbulkan salah paham. | —                                    |
| `partially_true`    | Benar Sebagian             | info      | Sebagian klaim benar, sebagian tidak; rinci di isi.                      | —                                        |
| `true_with_apology` | Benar, Saya Meminta Maaf   | primary   | Klaim benar; pemilik bertanggung jawab.                                  | Kolom `apology` **wajib** ✅             |
| `unverified`        | Belum Terbukti             | secondary | Belum cukup bukti untuk membenarkan/membantah (default).                 | —                                        |

## 3. Siklus hidup entri

1. **Draf** (`is_published=false`): hanya terlihat di admin; publik 404.
2. **Terjadwal**: `is_published=true` dengan `published_at` di masa depan; publik 404 sampai waktunya ✅.
3. **Terbit**: `is_published` dan `published_at` ≤ sekarang; tampil di `/clarifications`, sitemap, footer ✅. Menerbitkan tanpa tanggal mengisi `published_at` = sekarang ✅.
4. **Dikoreksi**: perubahan substansi dicatat di **Riwayat Pembaruan** (`updates` `{date,note}`), tampil terbaru dulu ✅. Jangan mengubah isi/vonis secara senyap.
5. **Ditarik** (`withdrawn_at`): keluar dari daftar dan sitemap; tautan lama menampilkan halaman "Klarifikasi Ditarik" (isi disembunyikan, noindex) dengan catatan penarikan ✅. Mengembalikan ke draf membuat halaman 404.

## 4. Aturan editorial (wajib diikuti pengelola)

| ID     | Aturan                                                                                                             | Ditegakkan sistem |
| ------ | ------------------------------------------------------------------------------------------------------------------ | ----------------- |
| CLR-01 | Klaim ditulis ≤ 1000 karakter dan dikutip netral, tanpa menambah tuduhan baru.                                      | Batas panjang ✅  |
| CLR-02 | Minimal satu bukti/sumber `{title,url}` saat terbit (maks. 20); tautan keluar `rel="noopener noreferrer nofollow"`. | ✅                |
| CLR-03 | Vonis wajib dipilih; `true_with_apology` mewajibkan permintaan maaf (≤ 5000 karakter).                              | ✅                |
| CLR-04 | Hanya membahas klaim tentang pemilik situs; jangan menyerang pribadi, menyebut data pribadi, atau membuka identitas pihak ketiga tanpa dasar. | ⬜ (kebijakan manual) |
| CLR-05 | Jangan mengklaim "fakta terverifikasi independen"; gunakan bahasa klarifikasi pihak bersangkutan.                   | Catatan otomatis di halaman ✅ |
| CLR-06 | Arsipkan tangkapan layar/dokumen bukti ke Pustaka Media (URL sumber bisa hilang).                                   | ⬜ (anjuran)      |
| CLR-07 | Naskah serius/berpotensi hukum ditinjau advokat sebelum terbit.                                                     | ⬜ (proses manual)|
| CLR-08 | Hanya `admin` yang boleh mengelola (`manage-site`); redaktur ditolak.                                               | ✅                |

## 5. Penyangkalan (disclaimer) — yang sudah ada

- Catatan di bawah setiap klarifikasi: *"Halaman ini adalah klarifikasi dari pemilik situs sebagai pihak yang bersangkutan, bukan pemeriksaan fakta independen. Bila Anda menemukan kekeliruan, silakan hubungi saya."* dengan tautan ke `/contact` ✅.
- Halaman `/disclaimer` (PUB-18) dengan kebijakan komentar/ulasan; disclaimer singkat di artikel blog, form komentar, dan form ulasan portofolio; alasan kontak "Koreksi / Hak Jawab" ✅.
- Tautan keluar bertanda `nofollow`; konten HTML disaring `HtmlSanitizer` ✅.

## 6. Penyangkalan & fitur yang belum ada (usulan)

| ID     | Usulan                                                                                                                                  | Alasan                                                    | Prioritas |
| ------ | --------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------- | --------- |
| CLR-D1 ✅ | Halaman **Penyangkalan & Ketentuan Penggunaan** (`/disclaimer`): opini pribadi, tanpa jaminan akurasi artikel, tautan pihak ketiga, hak cipta, batas tanggung jawab, kontak koreksi. Masuk footer & sitemap. | Belum ada halaman disclaimer di situs. | Tinggi |
| CLR-D2 ✅ | Disclaimer singkat di artikel blog ("pendapat pribadi penulis") dan portofolio (ulasan adalah pendapat pengulas).                         | Melindungi pemilik dari konten pengguna.                 | Sedang |
| CLR-D3 ✅ | Kebijakan komentar/ulasan: syarat moderasi, hak menolak/menghapus (muncul dekat form).                                                   | Komentar & ulasan dimoderasi tetapi aturannya tak tertulis.| Sedang |
| CLR-D4 ✅ | Alasan kontak baru **"Koreksi / Hak Jawab"** di `ContactReason` agar permintaan koreksi terpilah di inbox.                               | Saat ini hanya Connecting/Kolaborasi/Umum/Lainnya.        | Sedang |
| CLR-D5 ✅ | Tombol bagikan + tanggal "Terakhir diperbarui" menonjol di detail klarifikasi.                                                           | Mempermudah rujukan dan menunjukkan koreksi terbaru.      | Rendah |
| CLR-D6 ✅ | Pengalihan/halaman "ditarik" (410 atau catatan penarikan) untuk klarifikasi yang dicabut.                                               | Tautan yang sudah dibagikan tidak menjadi 404 tanpa konteks.| Rendah |
| CLR-D7 ✅ | Menyaring/membersihkan field `claim`, `apology`, `claim_origin` (teks polos) dan menambah tes XSS-nya; ganti JSON-LD ke `ClaimReview` hanya bila Google kembali mendukung. | Lihat `06` #9.                                           | Rendah |
| CLR-D8 ✅ | Pratinjau halaman draf sebelum terbit.                                                                                                   | Mengurangi salah terbit pada topik sensitif.              | Sedang |

## 7. Kriteria penerimaan modul

- Draf & terjadwal → 404 di publik; terbit → 200, ada di sitemap dan footer.
- Terbit tanpa sumber → ditolak validasi; vonis `true_with_apology` tanpa permintaan maaf → ditolak.
- Penyaringan `?verdict=` mengabaikan nilai tak dikenal.
- Redaktur/pengguna biasa → 403 di `/admin/clarifications`.
- Tes: `tests/Feature/Admin/ClarificationControllerTest.php`, `tests/Feature/PublicClarificationPageTest.php`.
