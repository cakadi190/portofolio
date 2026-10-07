<?php

namespace Database\Seeders;

use App\Models\Portfolio;
use App\Models\Service;
use App\Models\Technology;
use App\Models\User;
use App\Services\ImageService;
use App\Services\MediaService;
use Illuminate\Database\Seeder;

class PortfolioSeeder extends Seeder
{
    /**
     * Preset technology stacks, mirroring the Nuxt source's techstack.ts combinators.
     *
     * @return array<string, array<int, string>>
     */
    protected function stacks(): array
    {
        $php = ['PHP'];
        $laravel = ['Laravel', ...$php];
        $jqueryBootstrap = ['jQuery', 'Bootstrap'];
        $htmlCssJs = ['HTML5', 'CSS3', 'JavaScript'];
        $vue = ['VueJS', 'JavaScript'];
        $nuxt = ['NuxtJS', ...$vue];
        $laravelFullstack = [...$laravel, ...$htmlCssJs, ...$jqueryBootstrap];

        return [
            'laravel_fullstack' => $laravelFullstack,
            'php_jquery_bootstrap' => [...$php, ...$jqueryBootstrap],
            'kotlin_java' => ['Kotlin', 'Java'],
            'nuxt_supabase_vercel' => [...$nuxt, ...$jqueryBootstrap, 'Supabase', 'Vercel'],
            'html_jquery_bootstrap' => [...$htmlCssJs, ...$jqueryBootstrap],
            'jquery_bootstrap_vercel_html' => [...$jqueryBootstrap, 'Vercel', ...$htmlCssJs],
        ];
    }

    /**
     * Detailed rich-text descriptions keyed by portfolio slug.
     *
     * @return array<string, string>
     */
    protected function descriptions(): array
    {
        return [
            'ticket-hypenamic' => '<p>SiTiket adalah platform pemesanan tiket konser berbasis web yang dibangun untuk menyederhanakan proses pembelian tiket, mulai dari menemukan acara hingga tiket diterima pembeli. Pengguna tidak perlu lagi antre atau memesan secara manual karena seluruh alur dilakukan secara daring.</p><h3>Latar Belakang</h3><p>Pemesanan tiket konser secara konvensional sering menimbulkan antrean panjang, data penjualan yang tidak tercatat rapi, dan risiko tiket ganda. SiTiket hadir untuk mengatasi masalah tersebut dengan sistem terpusat yang transparan bagi pembeli maupun penyelenggara.</p><h3>Fitur Utama</h3><ul><li>Katalog konser dengan pencarian dan penyaringan berdasarkan nama, lokasi, dan tanggal.</li><li>Pemilihan kategori tiket dan jumlah pembelian dengan ketersediaan stok yang diperbarui secara langsung.</li><li>Pembayaran online beserta konfirmasi dan riwayat transaksi untuk tiap pengguna.</li><li>Dashboard admin untuk mengelola konser, kategori tiket, pesanan, dan laporan penjualan.</li><li>Autentikasi pengguna dengan pembagian hak akses antara admin dan pembeli.</li></ul><h3>Teknologi</h3><p>Backend menggunakan Laravel dengan database relasional, sedangkan antarmuka dibuat dengan Bootstrap dan jQuery agar responsif di perangkat desktop maupun mobile.</p>',

            'siperpus' => '<p>Siperpus PHP Native adalah sistem informasi perpustakaan yang dikembangkan dengan PHP native tanpa framework. Sistem ini membantu petugas perpustakaan mengelola koleksi buku, data anggota, serta transaksi peminjaman dan pengembalian dalam satu aplikasi.</p><h3>Tujuan</h3><p>Menggantikan pencatatan manual di buku besar yang rawan salah dan sulit ditelusuri, sekaligus mempercepat layanan sirkulasi di perpustakaan.</p><h3>Fitur Utama</h3><ul><li>Manajemen data buku: judul, pengarang, penerbit, kategori, dan jumlah eksemplar.</li><li>Manajemen anggota perpustakaan beserta riwayat peminjamannya.</li><li>Transaksi peminjaman dan pengembalian dengan perhitungan tenggat serta denda keterlambatan.</li><li>Pencarian buku cepat dan laporan statistik peminjaman.</li></ul><h3>Teknologi</h3><p>PHP native dan MySQL untuk logika dan penyimpanan data, dengan jQuery dan Bootstrap pada antarmuka. Proyek ini menjadi latihan penting dalam memahami alur aplikasi web dari dasar, termasuk sesi, validasi input, dan struktur kode tanpa framework.</p>',

            'sisfo-sewa-laptop' => '<p>Sistem Sewa Laptop adalah aplikasi manajemen usaha penyewaan laptop yang mencakup seluruh siklus bisnis: inventaris unit, pendaftaran penyewa, transaksi sewa, pengembalian, hingga pelaporan. Aplikasi ini dirancang agar pemilik usaha dapat memantau kondisi dan ketersediaan setiap unit dengan mudah.</p><h3>Fitur Utama</h3><ul><li>Inventaris laptop lengkap dengan spesifikasi, status ketersediaan, dan kondisi unit.</li><li>Pendaftaran penyewa dan pencatatan identitas untuk keamanan transaksi.</li><li>Pengelolaan jadwal sewa, durasi, tarif, serta pembayaran dan denda keterlambatan.</li><li>Pencatatan perawatan dan perbaikan unit agar laptop selalu siap disewakan.</li><li>Laporan pendapatan, riwayat sewa, dan unit yang paling sering disewa.</li></ul><h3>Teknologi</h3><p>Dibangun dengan PHP, jQuery, dan Bootstrap sehingga ringan dijalankan di hosting biasa dan nyaman dipakai pada layar komputer maupun ponsel.</p>',

            'ppdb-mahasiswa' => '<p>PMB Eltibiz adalah sistem penerimaan mahasiswa baru yang mendigitalkan seluruh proses pendaftaran, mulai dari pengisian formulir, unggah berkas, seleksi, sampai pengumuman hasil. Calon mahasiswa dapat mendaftar dari mana saja tanpa datang langsung ke kampus.</p><h3>Alur Sistem</h3><ol><li>Calon mahasiswa membuat akun dan mengisi formulir pendaftaran.</li><li>Dokumen persyaratan diunggah dan diverifikasi oleh panitia.</li><li>Panitia melakukan seleksi dan menetapkan hasil kelulusan.</li><li>Hasil diumumkan melalui akun masing-masing pendaftar.</li></ol><h3>Fitur Utama</h3><ul><li>Pendaftaran online dengan validasi data dan pengunggahan dokumen digital.</li><li>Panel panitia untuk verifikasi berkas, penentuan status, dan rekap pendaftar per program studi.</li><li>Pengumuman hasil seleksi yang aman karena hanya dapat dilihat oleh pendaftar yang bersangkutan.</li><li>Ekspor data pendaftar untuk keperluan administrasi akademik.</li></ul><h3>Teknologi</h3><p>Laravel sebagai backend, dengan HTML, CSS, JavaScript, jQuery, dan Bootstrap pada tampilan.</p>',

            'perkopian-duniawi' => '<p>Perkopian Duniawi adalah aplikasi mobile Android untuk komunitas pecinta kopi. Aplikasi ini mempertemukan para penikmat kopi untuk saling berbagi pengalaman, menemukan kedai kopi terdekat, dan berdiskusi seputar dunia perkopian.</p><h3>Fitur Utama</h3><ul><li>Pencarian kedai kopi terdekat berdasarkan lokasi pengguna, lengkap dengan alamat dan informasi kedai.</li><li>Ulasan dan penilaian pengguna untuk membantu pengunjung lain memilih tempat.</li><li>Forum diskusi komunitas untuk berbagi tips seduh, rekomendasi biji kopi, dan cerita seputar kopi.</li><li>Profil pengguna dan riwayat aktivitas.</li></ul><h3>Teknologi</h3><p>Dikembangkan secara native untuk Android menggunakan Kotlin dan Java, dengan fokus pada antarmuka yang ringan dan navigasi yang mudah dipahami.</p>',

            'sistem-data-organisasi' => '<p>Sistem Data Organisasi adalah platform manajemen administrasi yang membantu organisasi merapikan data anggota, jadwal kegiatan, dan dokumentasi acara dalam satu tempat. Data yang sebelumnya tersebar di berbagai berkas kini dapat dicari dan diperbarui dengan cepat.</p><h3>Fitur Utama</h3><ul><li>Database anggota lengkap dengan jabatan, kontak, dan status keanggotaan.</li><li>Penjadwalan dan pencatatan kegiatan beserta daftar hadir peserta.</li><li>Arsip dokumentasi acara seperti foto, notulen, dan laporan kegiatan.</li><li>Hak akses bertingkat sehingga setiap pengurus hanya melihat data sesuai perannya.</li></ul><h3>Teknologi</h3><p>Dibangun menggunakan Laravel dengan Bootstrap dan jQuery sehingga mudah dirawat dan dikembangkan oleh pengurus berikutnya.</p>',

            'supedes' => '<p>SUPEDES (Surat Pengantar Desa) adalah aplikasi untuk mempermudah pelayanan administrasi surat menyurat di tingkat desa. Warga dapat mengajukan surat pengantar secara daring, sementara perangkat desa memprosesnya tanpa harus menulis dan mencatat ulang secara manual.</p><h3>Fitur Utama</h3><ul><li>Pengajuan surat pengantar oleh warga atau input langsung oleh petugas desa.</li><li>Template surat otomatis yang terisi data penduduk sehingga mengurangi kesalahan pengetikan.</li><li>Alur verifikasi dan persetujuan oleh perangkat desa, termasuk pelacakan status surat.</li><li>Arsip digital surat yang dapat dicari berdasarkan nama, nomor, atau tanggal.</li><li>Cetak surat siap tanda tangan dalam format yang rapi.</li></ul><h3>Manfaat</h3><p>Pelayanan menjadi lebih cepat, nomor surat terkelola otomatis, dan arsip desa tersimpan aman dalam bentuk digital.</p>',

            'sipdata-pemuda-boyolali' => '<p>SIPDATA Pemuda Boyolali adalah sistem informasi yang dirancang untuk mengelola data Pemuda MTA di Kabupaten Boyolali. Sistem ini menghimpun data kepemudaan, guru daerah, dan anggota MTA Boyolali sehingga pengurus memiliki gambaran yang akurat tentang jumlah dan persebaran anggota.</p><h3>Fitur Utama</h3><ul><li>Pencatatan data pribadi anggota, riwayat pendidikan, dan jabatan dalam organisasi.</li><li>Pengelolaan data guru daerah beserta wilayah binaannya.</li><li>Pencatatan aktivitas dan prestasi pemuda.</li><li>Rekapitulasi dan statistik per cabang atau wilayah untuk bahan pengambilan keputusan pengurus.</li><li>Ekspor laporan untuk kebutuhan administrasi dan evaluasi.</li></ul><h3>Teknologi</h3><p>Laravel dengan Bootstrap dan jQuery, menggunakan hak akses berjenjang untuk pengurus daerah dan cabang.</p>',

            'append-disperindag' => '<p>Append Disperindag adalah aplikasi pengelolaan data industri dan perdagangan untuk Dinas Perindustrian dan Perdagangan. Aplikasi ini mengintegrasikan informasi pelaku usaha dalam satu basis data agar pemantauan dan pelaporan dapat dilakukan lebih efisien.</p><h3>Fitur Utama</h3><ul><li>Pendataan pelaku usaha: identitas, jenis usaha, lokasi, dan skala usaha.</li><li>Pemantauan perkembangan industri dan perdagangan berdasarkan periode tertentu.</li><li>Penyusunan laporan berkala yang dapat diekspor untuk kebutuhan instansi.</li><li>Pengelolaan pengguna dan hak akses antar bidang di dinas.</li></ul><h3>Manfaat</h3><p>Data yang sebelumnya tersebar kini terpusat, sehingga pencarian informasi lebih cepat dan laporan dapat disusun tanpa merekap manual.</p>',

            'siternak' => '<p>Siternak adalah Sistem Informasi Terintegrasi untuk BBIB Singosari yang lahir dari tugas magang di PT Humma Teknologi Indonesia. Sistem ini membantu pengelolaan data ternak dan kegiatan inseminasi buatan secara terstruktur.</p><h3>Fitur Utama</h3><ul><li>Pendataan ternak: identitas, jenis, kondisi, dan riwayat kesehatan.</li><li>Pencatatan inseminasi buatan beserta jadwal dan hasilnya.</li><li>Pengelolaan jadwal kegiatan dan penugasan petugas lapangan.</li><li>Laporan hasil dan rekapitulasi data untuk kebutuhan evaluasi.</li></ul><h3>Teknologi</h3><p>Dikerjakan dengan PHP Native dan template Sneat berbasis Bootstrap 5 sehingga tampilan dashboard rapi dan konsisten. Proyek ini memberi pengalaman bekerja pada kebutuhan instansi nyata, mulai dari analisis kebutuhan hingga implementasi.</p>',

            'cakadi-web' => '<p>Personal Website Cak Adi adalah situs pribadi yang menampilkan profil, portofolio, blog, dan kontak. Situs ini meraih Juara 3 pada Maroon Day UTDI 2024.</p><h3>Fitur Utama</h3><ul><li>Halaman profil yang merangkum latar belakang, keahlian, dan pengalaman.</li><li>Etalase portofolio proyek lengkap dengan teknologi yang digunakan.</li><li>Blog untuk berbagi catatan dan pengetahuan seputar pemrograman.</li><li>Formulir kontak agar pengunjung dapat menghubungi secara langsung.</li></ul><h3>Teknologi</h3><p>Dibangun dengan Nuxt (Vue) untuk render yang cepat dan ramah SEO, Supabase sebagai backend dan basis data, serta di-deploy di Vercel untuk distribusi yang cepat dan mudah dirilis ulang.</p>',

            'hummatech-pkl' => '<p>Sisfo PKL Hummatech adalah sistem informasi yang mengelola Praktik Kerja Lapangan (PKL) di Hummatech. Sistem ini menghubungkan peserta, pembimbing, dan pengelola dalam satu platform agar seluruh proses PKL tercatat dengan baik.</p><h3>Fitur Utama</h3><ul><li>Pendaftaran peserta PKL beserta data sekolah atau kampus asal.</li><li>Pemantauan kegiatan harian dan presensi peserta.</li><li>Penilaian dan pelaporan hasil PKL oleh pembimbing.</li><li>Dashboard pengelola untuk melihat rekap peserta dan perkembangan PKL.</li></ul><h3>Teknologi</h3><p>Laravel dengan Bootstrap dan jQuery, menyajikan antarmuka yang ramah bagi peserta maupun pengelola.</p>',

            'pilketos-smp' => '<p>Sistem Pemilihan Ketua OSIS adalah platform pemilihan ketua OSIS secara daring yang dibangun dengan Laravel 8. Sistem ini membuat pemilihan lebih praktis, transparan, dan hasilnya dapat dihitung secara otomatis.</p><h3>Fitur Utama</h3><ul><li>Pendaftaran dan profil calon ketua beserta visi dan misi.</li><li>Pemungutan suara elektronik dengan aturan satu pemilih satu suara.</li><li>Pengelolaan data pemilih dan pembuatan akun siswa oleh panitia.</li><li>Rekapitulasi hasil secara langsung dan akurat tanpa hitung manual.</li></ul><h3>Manfaat</h3><p>Proses pemilihan lebih cepat, menghemat kertas, mengurangi potensi kecurangan, dan hasilnya dapat dipertanggungjawabkan.</p>',

            'siperpus-laravel' => '<p>Siperpus Laravel adalah sistem informasi perpustakaan berbasis Laravel untuk mengelola operasional perpustakaan secara efisien, mulai dari katalog buku hingga laporan sirkulasi.</p><h3>Fitur Utama</h3><ul><li>Manajemen buku dengan kategori, penulis, dan jumlah stok.</li><li>Manajemen anggota dan kartu keanggotaan.</li><li>Peminjaman dan pengembalian dengan pencatatan tenggat serta denda.</li><li>Pencarian buku cepat bagi pengunjung dan petugas.</li><li>Dashboard admin berisi statistik dan laporan yang dapat dilihat sekilas.</li></ul><h3>Teknologi</h3><p>Antarmuka dibangun dengan Bootstrap dan jQuery serta mengikuti arsitektur MVC agar kode mudah dirawat. Demo dapat dicoba melalui tautan yang tersedia.</p>',

            'humma-e-sport' => '<p>Humma E-Sport adalah platform yang dirancang untuk menyediakan wadah dan menyelenggarakan berbagai ajang kompetisi e-sport. Platform ini membantu penyelenggara mengelola turnamen sekaligus memberi ruang bagi komunitas gamer untuk berkumpul.</p><h3>Fitur Utama</h3><ul><li>Manajemen turnamen: pendaftaran tim, jadwal pertandingan, dan bagan kompetisi.</li><li>Statistik permainan dan papan peringkat tim maupun pemain.</li><li>Siaran langsung pertandingan.</li><li>Forum komunitas untuk diskusi dan pencarian rekan satu tim.</li></ul><h3>Teknologi</h3><p>Laravel sebagai backend dengan tampilan Bootstrap dan jQuery yang responsif untuk diakses dari berbagai perangkat.</p>',

            'sideka' => '<p>SIDEKA (Sistem Informasi Dewan Kerja) adalah sistem informasi yang berfungsi sebagai alat monitoring dan evaluasi Dewan Kerja se-Kwarda Jatim. Sistem ini membantu pengurus memantau perkembangan tiap dewan kerja secara terstruktur.</p><h3>Fitur Utama</h3><ul><li>Manajemen keanggotaan Dewan Kerja pada setiap tingkatan.</li><li>Pengelolaan jadwal kegiatan dan program kerja.</li><li>Dokumentasi acara beserta laporan pelaksanaannya.</li><li>Rekap evaluasi per wilayah untuk bahan pembinaan.</li></ul><h3>Teknologi</h3><p>Laravel dengan Bootstrap dan jQuery, dilengkapi pengaturan hak akses sesuai jenjang kepengurusan. Demo tersedia melalui tautan yang disediakan.</p>',

            'deptics-project' => '<p>Landing Page KawalCovid adalah karya yang meraih Juara 1 pada Deptics Unipma Competition 2021. Halaman ini dibuat sebagai kampanye kesadaran untuk mengawal penanganan Covid-19 dengan informasi yang mudah dipahami masyarakat.</p><h3>Konten Utama</h3><ul><li>Informasi pencegahan dan protokol kesehatan.</li><li>Statistik perkembangan kasus yang disajikan secara visual.</li><li>Panduan vaksinasi dan jawaban atas pertanyaan umum.</li><li>Ajakan bertindak agar pengunjung ikut menyebarkan kampanye.</li></ul><h3>Teknologi</h3><p>HTML5, CSS3, JavaScript, jQuery, dan Bootstrap dengan desain responsif dan animasi ringan agar nyaman dibaca di ponsel.</p>',

            'museum-trinil' => '<p>Landing Page Museum Trinil adalah karya yang meraih Juara 5 pada NIFC Universitas Muhammadiyah Riau 2024. Halaman ini memperkenalkan Museum Trinil kepada calon pengunjung secara menarik dan informatif.</p><h3>Konten Utama</h3><ul><li>Sejarah museum dan temuan purbakala yang menjadikannya terkenal.</li><li>Koleksi unggulan dilengkapi gambar dan keterangan.</li><li>Informasi kunjungan: lokasi, jam buka, dan harga tiket.</li></ul><h3>Teknologi</h3><p>Dibangun dengan Nuxt (Vue) untuk performa dan SEO yang baik, Supabase untuk pengelolaan data, dan Vercel untuk deployment. Tampilan dirancang responsif agar nyaman diakses dari ponsel.</p>',

            'hummatech-landing-page' => '<p>Landing Page Hummatech adalah wajah online perusahaan teknologi Hummatech. Halaman ini memperkenalkan profil perusahaan, layanan, produk, serta cara menghubungi tim kepada calon klien dan mitra.</p><h3>Konten Utama</h3><ul><li>Profil dan nilai perusahaan.</li><li>Daftar layanan dan produk yang ditawarkan.</li><li>Portofolio proyek dan testimoni klien.</li><li>Informasi kontak dan formulir untuk calon klien.</li></ul><h3>Teknologi</h3><p>Dibangun dengan Laravel dan Bootstrap dengan tampilan profesional serta responsif. Situs dapat dilihat langsung melalui tautan demo.</p>',

            'visit-ngawi-2024' => '<p>Visit Ngawi 2024 adalah landing page promosi pariwisata yang meraih Juara 3 pada Fostifest UMS Surakarta 2023. Halaman ini mengajak wisatawan menjelajahi destinasi unggulan di Kabupaten Ngawi.</p><h3>Konten Utama</h3><ul><li>Daftar destinasi wisata lengkap dengan foto dan deskripsi.</li><li>Kalender acara dan agenda budaya.</li><li>Aktivitas dan pengalaman yang dapat dilakukan wisatawan.</li><li>Informasi akses dan lokasi destinasi.</li></ul><h3>Teknologi</h3><p>HTML5, CSS3, JavaScript, jQuery, dan Bootstrap, di-deploy di Vercel dengan desain visual yang menonjolkan keindahan alam dan budaya Ngawi.</p>',

            'sirus' => '<p>Landing Sirus adalah karya bertema kesehatan yang meraih Juara 1 pada Intechfest 2020. Halaman ini membantu masyarakat menemukan fasilitas kesehatan dan bantuan medis terkait Covid-19.</p><h3>Konten Utama</h3><ul><li>Peta lokasi fasilitas kesehatan terdekat.</li><li>Kontak penting dan layanan darurat.</li><li>Panduan kesehatan dan langkah penanganan awal.</li></ul><h3>Teknologi</h3><p>Dibangun dengan Nuxt (Vue), Supabase, dan Vercel dengan desain yang bersih dan mudah dipahami, agar informasi penting dapat diakses cepat saat dibutuhkan.</p>',

            'bytesfest-project' => '<p>Landing Page Toko Online adalah karya yang meraih Juara 1 pada BytesFest UNS 2020. Halaman ini dirancang untuk mempromosikan produk unggulan toko online dengan tampilan yang menarik dan mendorong pengunjung untuk membeli.</p><h3>Konten Utama</h3><ul><li>Banner dan produk unggulan dengan desain yang menonjol.</li><li>Katalog produk beserta harga dan deskripsi singkat.</li><li>Testimoni pelanggan dan ajakan bertindak yang jelas.</li></ul><h3>Teknologi</h3><p>HTML5, CSS3, JavaScript, jQuery, dan Bootstrap dengan elemen interaktif dan tata letak responsif untuk semua ukuran layar.</p>',
        ];
    }

    /**
     * Enrich a plain HTML description with editor blocks: a captioned
     * screenshot after the intro, a callout, and a demo button when available.
     */
    protected function withBlocks(string $html, string $name, string $image, ?string $demoLink): string
    {
        $alt = e($name);
        $figure = '<figure class="wp-block-image is-align-center" style="width: 75%"><img src="'.ImageService::url($image).'" alt="Tampilan '.$alt.'"><figcaption>Tampilan antarmuka '.$alt.'.</figcaption></figure>';

        $html = preg_replace('#</p>#', '</p>'.$figure, $html, 1) ?? $html;

        $html .= '<div class="wp-block-callout is-info" data-type="info"><p>Deskripsi ini disusun dari catatan pengembangan proyek. Hubungi saya lewat halaman kontak untuk detail teknis lebih lanjut.</p></div>';

        if ($demoLink) {
            $html .= '<div class="wp-block-button is-solid is-align-left"><a class="wp-block-button__link" href="'.e($demoLink).'" rel="noopener">Lihat demo</a></div>';
        }

        return $html;
    }

    /**
     * Seed the portfolio items, ported from the Nuxt app's seed_portofolio.ts.
     */
    public function run(): void
    {
        $stacks = $this->stacks();
        $descriptions = $this->descriptions();

        $websiteService = Service::query()->where('name', 'Website')->first();
        $mobileService = Service::query()->where('name', 'Mobile')->first();

        $items = [
            ['name' => 'SiTiket', 'slug' => 'ticket-hypenamic', 'image' => '/images/portofolio/ticket-front.png', 'stack' => 'laravel_fullstack', 'service' => $websiteService, 'short_desc' => 'Aplikasi ini adalah platform pemesanan tiket konser yang memudahkan pengguna untuk membeli dan memesan tiket ke konser favorit mereka. Dengan antarmuka yang intuitif dan fitur-fitur yang user-friendly, aplikasi ini memastikan proses pemesanan tiket menjadi cepat, mudah, dan aman.', 'demo_link' => null, 'source_code' => null],
            ['name' => 'Siperpus PHP Native', 'slug' => 'siperpus', 'image' => '/images/portofolio/siperpus-front.png', 'stack' => 'php_jquery_bootstrap', 'service' => $websiteService, 'short_desc' => 'Sistem ini adalah sistem informasi perpustakaan berbasis PHP native yang dirancang untuk mempermudah pengelolaan data buku, anggota perpustakaan, dan peminjaman buku.', 'demo_link' => null, 'source_code' => null],
            ['name' => 'Sistem Sewa Laptop', 'slug' => 'sisfo-sewa-laptop', 'image' => '/images/portofolio/sewa-laptop-front.png', 'stack' => 'php_jquery_bootstrap', 'service' => null, 'short_desc' => 'Aplikasi manajemen penyewaan laptop dirancang untuk mengelola proses penyewaan, pengembalian, inventaris, perawatan, pembayaran, dan pelaporan secara efisien.', 'demo_link' => null, 'source_code' => null],
            ['name' => 'PMB Eltibiz', 'slug' => 'ppdb-mahasiswa', 'image' => '/images/portofolio/ppdb-login.png', 'stack' => 'laravel_fullstack', 'service' => $websiteService, 'short_desc' => 'Sistem penerimaan mahasiswa baru untuk Eltibiz adalah platform digital yang memfasilitasi pendaftaran, seleksi, dan pengumuman hasil bagi calon mahasiswa.', 'demo_link' => null, 'source_code' => null],
            ['name' => 'Perkopian Duniawi', 'slug' => 'perkopian-duniawi', 'image' => '/images/portofolio/perkopian-duniawi-show.png', 'stack' => 'kotlin_java', 'service' => $websiteService, 'short_desc' => 'Aplikasi mobile untuk komunitas pecinta kopi dengan Kotlin adalah platform yang memungkinkan para pengguna untuk berinteraksi, berbagi informasi seputar kopi, dan mencari tempat kopi terdekat.', 'demo_link' => null, 'source_code' => null],
            ['name' => 'Sistem Data Organisasi', 'slug' => 'sistem-data-organisasi', 'image' => '/images/portofolio/sidasi-back.png', 'stack' => 'laravel_fullstack', 'service' => $mobileService, 'short_desc' => 'Sistem manajemen data organisasi adalah platform yang dirancang untuk mengelola berbagai aspek operasional dan administratif dalam sebuah organisasi.', 'demo_link' => null, 'source_code' => null],
            ['name' => 'SUPEDES (Surat Pengantar Desa)', 'slug' => 'supedes', 'image' => '/images/portofolio/supedes-data.png', 'stack' => 'laravel_fullstack', 'service' => $websiteService, 'short_desc' => 'Aplikasi untuk pengelolaan surat pengantar desa adalah platform digital untuk mempermudah proses pembuatan, pengelolaan, dan pelacakan surat pengantar penduduk desa.', 'demo_link' => null, 'source_code' => null],
            ['name' => 'SIPDATA Pemuda Boyolali', 'slug' => 'sipdata-pemuda-boyolali', 'image' => '/images/portofolio/sipdata-front.png', 'stack' => 'laravel_fullstack', 'service' => $websiteService, 'short_desc' => 'Sistem informasi ini dirancang untuk mengelola data Pemuda MTA di Kabupaten Boyolali, mencakup data kepemudaan, guru daerah, dan anggota MTA Boyolali.', 'demo_link' => null, 'source_code' => null],
            ['name' => 'Append Disperindag', 'slug' => 'append-disperindag', 'image' => '/images/portofolio/append-front.png', 'stack' => 'laravel_fullstack', 'service' => $websiteService, 'short_desc' => 'Aplikasi untuk pengelolaan data industri dan perdagangan yang dirancang untuk mengintegrasikan dan mengelola informasi terkait industri dan perdagangan secara efisien.', 'demo_link' => null, 'source_code' => null],
            ['name' => 'Siternak BBIB Singosari', 'slug' => 'siternak', 'image' => '/images/portofolio/siternak-home.png', 'stack' => 'php_jquery_bootstrap', 'service' => $websiteService, 'short_desc' => 'Sistem Informasi Terintegrasi untuk BBIB Singosari, diinisiasi berkat tugas magang di PT Humma Teknologi Indonesia, dikerjakan dengan PHP Native dan template Sneat Bootstrap 5.', 'demo_link' => null, 'source_code' => null],
            ['name' => 'Personal Website Cak Adi', 'slug' => 'cakadi-web', 'image' => '/images/portofolio/cakadi-home.png', 'stack' => 'nuxt_supabase_vercel', 'service' => $websiteService, 'short_desc' => 'Juara 🏆 #3 Maroon Day UTDI 2024. Situs pribadi untuk Cak Adi yang menampilkan informasi, portofolio, blog, dan kontak.', 'demo_link' => null, 'source_code' => null],
            ['name' => 'Sisfo PKL Hummatech', 'slug' => 'hummatech-pkl', 'image' => '/images/portofolio/pkl-hummatech-front.png', 'stack' => 'laravel_fullstack', 'service' => $websiteService, 'short_desc' => 'Sisfo PKL Hummatech mempermudah pengelolaan Praktik Kerja Lapangan (PKL) di Hummatech, dengan fitur pendaftaran peserta, monitoring, dan pelaporan hasil PKL.', 'demo_link' => 'https://pkl.hummatech.com', 'source_code' => null],
            ['name' => 'Sistem Pemilihan Ketua OSIS', 'slug' => 'pilketos-smp', 'image' => '/images/portofolio/pilketos-front.png', 'stack' => 'laravel_fullstack', 'service' => $websiteService, 'short_desc' => 'Platform pemilihan ketua OSIS secara online dibangun dengan Laravel 8, memungkinkan siswa memilih calon ketua OSIS dengan mudah dan transparan.', 'demo_link' => null, 'source_code' => null],
            ['name' => 'Siperpus Laravel', 'slug' => 'siperpus-laravel', 'image' => '/images/portofolio/siperpus-home.png', 'stack' => 'php_jquery_bootstrap', 'service' => $websiteService, 'short_desc' => 'Sistem Informasi Perpustakaan Berbasis Laravel untuk mengelola operasional perpustakaan secara efisien: manajemen buku, peminjaman, pengembalian, dan anggota.', 'demo_link' => 'https://siperpus.cakadi.id', 'source_code' => null],
            ['name' => 'Humma E-Sport', 'slug' => 'humma-e-sport', 'image' => '/images/portofolio/humma-esport-home.png', 'stack' => 'laravel_fullstack', 'service' => $websiteService, 'short_desc' => 'Platform e-sport yang dirancang khusus untuk menyediakan tempat dan menyelenggarakan berbagai ajang kompetisi e-sport.', 'demo_link' => null, 'source_code' => null],
            ['name' => 'SIDEKA (Sistem Informasi Dewan Kerja)', 'slug' => 'sideka', 'image' => '/images/portofolio/sideka-front.png', 'stack' => 'laravel_fullstack', 'service' => $websiteService, 'short_desc' => 'Sistem informasi untuk Dewan Kerja yang berfungsi sebagai alat monitor dan evaluasi Dewan Kerja se-Kwarda Jatim.', 'demo_link' => 'https://sideka.cakadi.id', 'source_code' => null],
            ['name' => 'Landing Page KawalCovid - Deptics 2021 Project', 'slug' => 'deptics-project', 'image' => '/images/portofolio/kawal-covid-main.png', 'stack' => 'html_jquery_bootstrap', 'service' => $websiteService, 'short_desc' => 'Juara #1 🏆 Deptics Unipma Competition. Landing page kampanye kawal Covid-19 dengan informasi pencegahan, statistik, dan panduan vaksinasi.', 'demo_link' => 'https://deptics2021.portofolio.cakadi.id/', 'source_code' => null],
            ['name' => 'Landing Page Museum Trinil', 'slug' => 'museum-trinil', 'image' => '/images/portofolio/museum-trinil-front.png', 'stack' => 'nuxt_supabase_vercel', 'service' => $websiteService, 'short_desc' => 'Juara 🏆 #5 NIFC Universitas Muhammadiyah Riau 2024. Halaman utama berisi sejarah, koleksi, dan informasi kunjungan Museum Trinil.', 'demo_link' => 'https://nifc2024.portofolio.cakadi.id/', 'source_code' => null],
            ['name' => 'Landing Page Hummatech', 'slug' => 'hummatech-landing-page', 'image' => '/images/portofolio/hummatech-front.png', 'stack' => 'laravel_fullstack', 'service' => $websiteService, 'short_desc' => 'Halaman utama yang menampilkan informasi tentang perusahaan teknologi Hummatech, layanan, produk, dan informasi kontak.', 'demo_link' => 'https://www.hummatech.com', 'source_code' => null],
            ['name' => 'Visit Ngawi 2024', 'slug' => 'visit-ngawi-2024', 'image' => '/images/portofolio/visit-ngawi-front.png', 'stack' => 'jquery_bootstrap_vercel_html', 'service' => $websiteService, 'short_desc' => 'Juara #3 🏆 Fostifest UMS Surakarta 2023. Landing page promosi destinasi wisata Ngawi dengan informasi tempat wisata, acara, dan aktivitas.', 'demo_link' => 'https://fostifest2023.portofolio.cakadi.id/', 'source_code' => null],
            ['name' => 'Landing Sirus', 'slug' => 'sirus', 'image' => '/images/portofolio/sirus-front.png', 'stack' => 'nuxt_supabase_vercel', 'service' => $websiteService, 'short_desc' => 'Juara 🏆 #1 Intechfest 2020. Landing page tema kesehatan berisi informasi fasilitas kesehatan dan bantuan medis terkait Covid-19.', 'demo_link' => 'https://intechfest2020.portofolio.cakadi.id/', 'source_code' => null],
            ['name' => 'Landing Page Toko Online - BytesFest 2021 Project', 'slug' => 'bytesfest-project', 'image' => '/images/portofolio/bytesfest-front.png', 'stack' => 'html_jquery_bootstrap', 'service' => $websiteService, 'short_desc' => 'Juara #1 🏆 Bytesfest UNS 2020. Landing page toko online untuk mempromosikan produk unggulan dalam event BytesFest 2021.', 'demo_link' => 'https://bytesfest2020.portofolio.cakadi.id/', 'source_code' => null],
        ];

        $media = app(MediaService::class);
        $userId = User::query()->value('id');

        foreach ($items as $item) {
            $image = $media->storeFromPublicPath($item['image'], $userId)->path;

            $portfolio = Portfolio::query()->create([
                'name' => $item['name'],
                'slug' => $item['slug'],
                'image' => $image,
                'short_desc' => $item['short_desc'],
                'description' => $this->withBlocks($descriptions[$item['slug']], $item['name'], $image, $item['demo_link']),
                'demo_link' => $item['demo_link'],
                'source_code' => $item['source_code'],
                'is_private' => false,
            ]);

            $technologyIds = Technology::query()->whereIn('name', $stacks[$item['stack']])->pluck('id');
            $portfolio->technologies()->sync($technologyIds);

            if ($item['service']) {
                $portfolio->services()->sync([$item['service']->id]);
            }
        }
    }
}
