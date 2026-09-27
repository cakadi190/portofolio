<?php

namespace Database\Seeders;

use App\Models\Portfolio;
use App\Models\PortfolioCategory;
use App\Models\Technology;
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
     * Seed the portfolio items, ported from the Nuxt app's seed_portofolio.ts.
     */
    public function run(): void
    {
        $stacks = $this->stacks();

        $websiteCategory = PortfolioCategory::query()->where('name', 'Website')->first();
        $mobileCategory = PortfolioCategory::query()->where('name', 'Mobile')->first();

        $items = [
            ['name' => 'SiTiket', 'slug' => 'ticket-hypenamic', 'image' => '/images/portofolio/ticket-front.png', 'stack' => 'laravel_fullstack', 'category' => $websiteCategory, 'short_desc' => 'Aplikasi ini adalah platform pemesanan tiket konser yang memudahkan pengguna untuk membeli dan memesan tiket ke konser favorit mereka. Dengan antarmuka yang intuitif dan fitur-fitur yang user-friendly, aplikasi ini memastikan proses pemesanan tiket menjadi cepat, mudah, dan aman.', 'description' => '<p>SiTiket adalah aplikasi pemesanan tiket yang dirancang untuk memberikan kemudahan dalam memesan tiket secara online. Aplikasi ini dilengkapi dengan berbagai fitur seperti pencarian tiket, pemilihan kursi, dan pembayaran online yang aman.</p><p>Dengan teknologi Laravel sebagai backend dan Bootstrap untuk tampilan frontend, SiTiket menawarkan pengalaman pengguna yang responsif dan mudah digunakan.</p>', 'demo_link' => null, 'source_code' => null],
            ['name' => 'Siperpus PHP Native', 'slug' => 'siperpus', 'image' => '/images/portofolio/siperpus-front.png', 'stack' => 'php_jquery_bootstrap', 'category' => $websiteCategory, 'short_desc' => 'Sistem ini adalah sistem informasi perpustakaan berbasis PHP native yang dirancang untuk mempermudah pengelolaan data buku, anggota perpustakaan, dan peminjaman buku.', 'description' => '<p>Siperpus adalah sistem informasi perpustakaan yang dikembangkan menggunakan PHP native, menyediakan fitur pencarian buku, manajemen anggota, peminjaman dan pengembalian buku, serta laporan statistik.</p>', 'demo_link' => null, 'source_code' => null],
            ['name' => 'Sistem Sewa Laptop', 'slug' => 'sisfo-sewa-laptop', 'image' => '/images/portofolio/sewa-laptop-front.png', 'stack' => 'php_jquery_bootstrap', 'category' => null, 'short_desc' => 'Aplikasi manajemen penyewaan laptop dirancang untuk mengelola proses penyewaan, pengembalian, inventaris, perawatan, pembayaran, dan pelaporan secara efisien.', 'description' => '<p>Sistem Sewa Laptop memudahkan manajemen penyewaan laptop, mulai dari pendaftaran penyewa, pengelolaan jadwal, hingga pelaporan kondisi laptop.</p>', 'demo_link' => null, 'source_code' => null],
            ['name' => 'PMB Eltibiz', 'slug' => 'ppdb-mahasiswa', 'image' => '/images/portofolio/ppdb-login.png', 'stack' => 'laravel_fullstack', 'category' => $websiteCategory, 'short_desc' => 'Sistem penerimaan mahasiswa baru untuk Eltibiz adalah platform digital yang memfasilitasi pendaftaran, seleksi, dan pengumuman hasil bagi calon mahasiswa.', 'description' => '<p>PMB Eltibiz mempermudah proses pendaftaran dan seleksi mahasiswa baru, dengan fitur pendaftaran online, upload dokumen, dan verifikasi data digital.</p>', 'demo_link' => null, 'source_code' => null],
            ['name' => 'Perkopian Duniawi', 'slug' => 'perkopian-duniawi', 'image' => '/images/portofolio/perkopian-duniawi-show.png', 'stack' => 'kotlin_java', 'category' => $websiteCategory, 'short_desc' => 'Aplikasi mobile untuk komunitas pecinta kopi dengan Kotlin adalah platform yang memungkinkan para pengguna untuk berinteraksi, berbagi informasi seputar kopi, dan mencari tempat kopi terdekat.', 'description' => '<p>Perkopian Duniawi adalah aplikasi mobile Android untuk komunitas pecinta kopi, dengan fitur pencarian kedai kopi, ulasan pengguna, dan forum diskusi.</p>', 'demo_link' => null, 'source_code' => null],
            ['name' => 'Sistem Data Organisasi', 'slug' => 'sistem-data-organisasi', 'image' => '/images/portofolio/sidasi-back.png', 'stack' => 'laravel_fullstack', 'category' => $mobileCategory, 'short_desc' => 'Sistem manajemen data organisasi adalah platform yang dirancang untuk mengelola berbagai aspek operasional dan administratif dalam sebuah organisasi.', 'description' => '<p>Sistem Data Organisasi membantu manajemen data anggota, jadwal kegiatan, dan dokumentasi acara dalam suatu organisasi.</p>', 'demo_link' => null, 'source_code' => null],
            ['name' => 'SUPEDES (Surat Pengantar Desa)', 'slug' => 'supedes', 'image' => '/images/portofolio/supedes-data.png', 'stack' => 'laravel_fullstack', 'category' => $websiteCategory, 'short_desc' => 'Aplikasi untuk pengelolaan surat pengantar desa adalah platform digital untuk mempermudah proses pembuatan, pengelolaan, dan pelacakan surat pengantar penduduk desa.', 'description' => '<p>SUPEDES memudahkan pengelolaan surat pengantar di tingkat desa, dengan fitur pembuatan surat, verifikasi, dan arsip digital.</p>', 'demo_link' => null, 'source_code' => null],
            ['name' => 'SIPDATA Pemuda Boyolali', 'slug' => 'sipdata-pemuda-boyolali', 'image' => '/images/portofolio/sipdata-front.png', 'stack' => 'laravel_fullstack', 'category' => $websiteCategory, 'short_desc' => 'Sistem informasi ini dirancang untuk mengelola data Pemuda MTA di Kabupaten Boyolali, mencakup data kepemudaan, guru daerah, dan anggota MTA Boyolali.', 'description' => '<p>SIPDATA Pemuda Boyolali mengelola pencatatan data pribadi, aktivitas, dan prestasi pemuda di wilayah Boyolali.</p>', 'demo_link' => null, 'source_code' => null],
            ['name' => 'Append Disperindag', 'slug' => 'append-disperindag', 'image' => '/images/portofolio/append-front.png', 'stack' => 'laravel_fullstack', 'category' => $websiteCategory, 'short_desc' => 'Aplikasi untuk pengelolaan data industri dan perdagangan yang dirancang untuk mengintegrasikan dan mengelola informasi terkait industri dan perdagangan secara efisien.', 'description' => '<p>Append Disperindag membantu Dinas Perindustrian dan Perdagangan mengelola data usaha, monitoring, dan pelaporan.</p>', 'demo_link' => null, 'source_code' => null],
            ['name' => 'Siternak BBIB Singosari', 'slug' => 'siternak', 'image' => '/images/portofolio/siternak-home.png', 'stack' => 'php_jquery_bootstrap', 'category' => $websiteCategory, 'short_desc' => 'Sistem Informasi Terintegrasi untuk BBIB Singosari, diinisiasi berkat tugas magang di PT Humma Teknologi Indonesia, dikerjakan dengan PHP Native dan template Sneat Bootstrap 5.', 'description' => '<p>Siternak BBIB Singosari mempermudah manajemen data inseminasi buatan, pendataan ternak, jadwal, dan pelaporan hasil.</p>', 'demo_link' => null, 'source_code' => null],
            ['name' => 'Personal Website Cak Adi', 'slug' => 'cakadi-web', 'image' => '/images/portofolio/cakadi-home.png', 'stack' => 'nuxt_supabase_vercel', 'category' => $websiteCategory, 'short_desc' => 'Juara 🏆 #3 Maroon Day UTDI 2024. Situs pribadi untuk Cak Adi yang menampilkan informasi, portofolio, blog, dan kontak.', 'description' => '<p>Personal Website Cak Adi adalah situs pribadi yang menampilkan portofolio, blog, dan kontak, dibangun dengan Nuxt, Supabase, dan Vercel.</p>', 'demo_link' => null, 'source_code' => null],
            ['name' => 'Sisfo PKL Hummatech', 'slug' => 'hummatech-pkl', 'image' => '/images/portofolio/pkl-hummatech-front.png', 'stack' => 'laravel_fullstack', 'category' => $websiteCategory, 'short_desc' => 'Sisfo PKL Hummatech mempermudah pengelolaan Praktik Kerja Lapangan (PKL) di Hummatech, dengan fitur pendaftaran peserta, monitoring, dan pelaporan hasil PKL.', 'description' => '<p>Sisfo PKL Hummatech menawarkan antarmuka yang user-friendly untuk peserta PKL dan pengelola mengakses informasi terkait PKL.</p>', 'demo_link' => 'https://pkl.hummatech.com', 'source_code' => null],
            ['name' => 'Sistem Pemilihan Ketua OSIS', 'slug' => 'pilketos-smp', 'image' => '/images/portofolio/pilketos-front.png', 'stack' => 'laravel_fullstack', 'category' => $websiteCategory, 'short_desc' => 'Platform pemilihan ketua OSIS secara online dibangun dengan Laravel 8, memungkinkan siswa memilih calon ketua OSIS dengan mudah dan transparan.', 'description' => '<p>Sistem Pemilihan Ketua OSIS menyediakan fitur pendaftaran calon, kampanye digital, dan pemungutan suara elektronik.</p>', 'demo_link' => null, 'source_code' => null],
            ['name' => 'Siperpus Laravel', 'slug' => 'siperpus-laravel', 'image' => '/images/portofolio/siperpus-home.png', 'stack' => 'php_jquery_bootstrap', 'category' => $websiteCategory, 'short_desc' => 'Sistem Informasi Perpustakaan Berbasis Laravel untuk mengelola operasional perpustakaan secara efisien: manajemen buku, peminjaman, pengembalian, dan anggota.', 'description' => '<p>Siperpus Laravel dilengkapi pencarian buku, manajemen anggota, dan dashboard admin untuk statistik dan laporan.</p>', 'demo_link' => 'https://siperpus.cakadi.id', 'source_code' => null],
            ['name' => 'Humma E-Sport', 'slug' => 'humma-e-sport', 'image' => '/images/portofolio/humma-esport-home.png', 'stack' => 'laravel_fullstack', 'category' => $websiteCategory, 'short_desc' => 'Platform e-sport yang dirancang khusus untuk menyediakan tempat dan menyelenggarakan berbagai ajang kompetisi e-sport.', 'description' => '<p>Humma E-Sport menawarkan streaming langsung, statistik permainan, forum komunitas, dan manajemen turnamen.</p>', 'demo_link' => null, 'source_code' => null],
            ['name' => 'SIDEKA (Sistem Informasi Dewan Kerja)', 'slug' => 'sideka', 'image' => '/images/portofolio/sideka-front.png', 'stack' => 'laravel_fullstack', 'category' => $websiteCategory, 'short_desc' => 'Sistem informasi untuk Dewan Kerja yang berfungsi sebagai alat monitor dan evaluasi Dewan Kerja se-Kwarda Jatim.', 'description' => '<p>SIDEKA menyediakan manajemen keanggotaan, pengelolaan jadwal kegiatan, dan dokumentasi acara.</p>', 'demo_link' => 'https://sideka.cakadi.id', 'source_code' => null],
            ['name' => 'Landing Page KawalCovid - Deptics 2021 Project', 'slug' => 'deptics-project', 'image' => '/images/portofolio/kawal-covid-main.png', 'stack' => 'html_jquery_bootstrap', 'category' => $websiteCategory, 'short_desc' => 'Juara #1 🏆 Deptics Unipma Competition. Landing page kampanye kawal Covid-19 dengan informasi pencegahan, statistik, dan panduan vaksinasi.', 'description' => '<p>Landing Page KawalCovid menyediakan informasi terbaru mengenai perkembangan kasus, tips kesehatan, dan panduan pencegahan Covid-19.</p>', 'demo_link' => 'https://deptics2021.portofolio.cakadi.id/', 'source_code' => null],
            ['name' => 'Landing Page Museum Trinil', 'slug' => 'museum-trinil', 'image' => '/images/portofolio/museum-trinil-front.png', 'stack' => 'nuxt_supabase_vercel', 'category' => $websiteCategory, 'short_desc' => 'Juara 🏆 #5 NIFC Universitas Muhammadiyah Riau 2024. Halaman utama berisi sejarah, koleksi, dan informasi kunjungan Museum Trinil.', 'description' => '<p>Landing Page Museum Trinil menampilkan sejarah museum, koleksi yang dimiliki, dan informasi kunjungan.</p>', 'demo_link' => 'https://nifc2024.portofolio.cakadi.id/', 'source_code' => null],
            ['name' => 'Landing Page Hummatech', 'slug' => 'hummatech-landing-page', 'image' => '/images/portofolio/hummatech-front.png', 'stack' => 'laravel_fullstack', 'category' => $websiteCategory, 'short_desc' => 'Halaman utama yang menampilkan informasi tentang perusahaan teknologi Hummatech, layanan, produk, dan informasi kontak.', 'description' => '<p>Landing Page Hummatech berfungsi sebagai wajah online perusahaan dengan tampilan profesional dan responsif.</p>', 'demo_link' => 'https://www.hummatech.com', 'source_code' => null],
            ['name' => 'Visit Ngawi 2024', 'slug' => 'visit-ngawi-2024', 'image' => '/images/portofolio/visit-ngawi-front.png', 'stack' => 'jquery_bootstrap_vercel_html', 'category' => $websiteCategory, 'short_desc' => 'Juara #3 🏆 Fostifest UMS Surakarta 2023. Landing page promosi destinasi wisata Ngawi dengan informasi tempat wisata, acara, dan aktivitas.', 'description' => '<p>Visit Ngawi 2024 menampilkan informasi lengkap tentang tempat wisata, acara, dan aktivitas di Ngawi.</p>', 'demo_link' => 'https://fostifest2023.portofolio.cakadi.id/', 'source_code' => null],
            ['name' => 'Landing Sirus', 'slug' => 'sirus', 'image' => '/images/portofolio/sirus-front.png', 'stack' => 'nuxt_supabase_vercel', 'category' => $websiteCategory, 'short_desc' => 'Juara 🏆 #1 Intechfest 2020. Landing page tema kesehatan berisi informasi fasilitas kesehatan dan bantuan medis terkait Covid-19.', 'description' => '<p>Landing Sirus menyediakan peta lokasi faskes, informasi kontak penting, dan panduan kesehatan terkait Covid-19.</p>', 'demo_link' => 'https://intechfest2020.portofolio.cakadi.id/', 'source_code' => null],
            ['name' => 'Landing Page Toko Online - BytesFest 2021 Project', 'slug' => 'bytesfest-project', 'image' => '/images/portofolio/bytesfest-front.png', 'stack' => 'html_jquery_bootstrap', 'category' => $websiteCategory, 'short_desc' => 'Juara #1 🏆 Bytesfest UNS 2020. Landing page toko online untuk mempromosikan produk unggulan dalam event BytesFest 2021.', 'description' => '<p>Landing Page Toko Online menampilkan produk-produk unggulan dengan desain menarik dan interaktif.</p>', 'demo_link' => 'https://bytesfest2020.portofolio.cakadi.id/', 'source_code' => null],
        ];

        foreach ($items as $item) {
            $portfolio = Portfolio::query()->create([
                'name' => $item['name'],
                'slug' => $item['slug'],
                'image' => $item['image'],
                'short_desc' => $item['short_desc'],
                'description' => $item['description'],
                'demo_link' => $item['demo_link'],
                'source_code' => $item['source_code'],
                'is_private' => false,
            ]);

            $technologyIds = Technology::query()->whereIn('name', $stacks[$item['stack']])->pluck('id');
            $portfolio->technologies()->sync($technologyIds);

            if ($item['category']) {
                $portfolio->categories()->sync([$item['category']->id]);
            }
        }
    }
}
