<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
use App\Models\User;
use App\Services\ImageService;
use App\Services\MediaService;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Seed the blog posts with sample cover images from public/images/posts.
     * The `{{cover}}` token in a post's HTML is replaced by its cover URL so
     * the sample content can showcase image, callout and button blocks.
     */
    public function run(): void
    {
        $media = app(MediaService::class);
        $userId = User::query()->value('id');

        foreach ($this->posts() as $index => $item) {
            $cover = $media->storeFromPublicPath("/images/posts/{$item['image']}.webp", $userId)->path;

            $post = Post::query()->create([
                'user_id' => $userId,
                'title' => $item['title'],
                'slug' => $item['slug'],
                'excerpt' => $item['excerpt'],
                'content' => str_replace('{{cover}}', (string) ImageService::url($cover), $item['content']),
                'cover_image' => $cover,
                'is_published' => true,
                'published_at' => now()->subDays(count($this->posts()) - $index),
            ]);

            $category = PostCategory::query()->where('name', $item['category'])->first();

            if ($category) {
                $post->categories()->sync([$category->id]);
            }

            $tagIds = collect($item['tags'])
                ->map(fn (string $tag) => Tag::query()->firstOrCreate(['name' => $tag])->id);

            $post->tags()->sync($tagIds);
        }
    }

    /**
     * @return array<int, array{title: string, slug: string, excerpt: string, content: string, image: string, category: string, tags: array<int, string>}>
     */
    protected function posts(): array
    {
        return [
            [
                'title' => 'Membangun Fullstack App dengan Nuxt dan Prisma',
                'slug' => 'membangun-fullstack-app-dengan-nuxt-dan-prisma',
                'excerpt' => 'Cerita dan pengalaman membangun aplikasi fullstack menggunakan Nuxt di sisi frontend dan Prisma sebagai lapisan data.',
                'image' => 'nuxt-prisma',
                'category' => 'Teknologi',
                'tags' => ['Nuxt', 'Prisma ORM', 'Fullstack Development', 'JavaScript'],
                'content' => <<<'HTML'
<p>Nuxt dan Prisma adalah kombinasi yang sangat produktif untuk membangun aplikasi fullstack modern. Nuxt mengurus tampilan dan routing, sementara Prisma mengurus komunikasi dengan database lewat schema yang jelas dan tipe data yang aman.</p>
<figure class="wp-block-image is-align-center" style="width: 75%"><img src="{{cover}}" alt="Ilustrasi Nuxt dan Prisma"><figcaption>Nuxt di sisi tampilan, Prisma di sisi data.</figcaption></figure>
<h2>Kenapa Nuxt dan Prisma?</h2>
<p>Saat mengerjakan proyek kecil sampai menengah, saya ingin satu repositori saja yang berisi frontend sekaligus backend. Nuxt menyediakan server routes lewat Nitro, jadi endpoint API bisa hidup berdampingan dengan halaman. Prisma melengkapinya dengan query yang rapi dan autocomplete yang akurat.</p>
<h2>Langkah awal</h2>
<ol>
<li>Buat proyek Nuxt baru, lalu pasang Prisma sebagai dependensi.</li>
<li>Definisikan model di <code>schema.prisma</code>, misalnya <code>User</code> dan <code>Post</code>.</li>
<li>Jalankan migrasi supaya tabel terbentuk di database.</li>
<li>Buat server route di folder <code>server/api</code> yang memanggil Prisma Client.</li>
</ol>
<div class="wp-block-callout is-info" data-type="info"><p><strong>Tips:</strong> jalankan <code>npx prisma studio</code> untuk melihat dan mengubah data lewat antarmuka web selama pengembangan.</p></div>
<h2>Contoh endpoint sederhana</h2>
<pre><code>export default defineEventHandler(async () => {
  return await prisma.post.findMany({
    where: { published: true },
    orderBy: { createdAt: 'desc' },
  })
})</code></pre>
<p>Dengan kode sependek itu, daftar artikel sudah bisa dipakai oleh halaman Nuxt lewat <code>useFetch</code>. Tipe datanya ikut terbawa, jadi kesalahan penulisan field langsung ketahuan saat mengetik.</p>
<h2>Hal yang perlu diperhatikan</h2>
<ul>
<li>Buat satu instance Prisma Client saja agar koneksi database tidak membengkak saat mode pengembangan.</li>
<li>Validasi input di server sebelum menyentuh database.</li>
<li>Gunakan <code>select</code> atau <code>include</code> secukupnya supaya respons API tidak terlalu besar.</li>
</ul>
<div class="wp-block-callout is-warning" data-type="warning"><p>Jangan lupa menjalankan <code>prisma generate</code> setiap kali schema berubah, supaya tipe data di kode ikut diperbarui.</p></div>
<h2>Penutup</h2>
<p>Untuk proyek pribadi maupun aplikasi klien dengan skala wajar, kombinasi ini terasa ringan dan cepat dikerjakan. Kalau kamu baru mau mulai, coba buat aplikasi catatan sederhana dulu sebelum masuk ke fitur yang lebih rumit.</p>
HTML,
            ],
            [
                'title' => 'Tips Menulis Kode yang Mudah Dipelihara',
                'slug' => 'tips-menulis-kode-yang-mudah-dipelihara',
                'excerpt' => 'Beberapa prinsip sederhana yang saya pakai supaya kode tetap rapi dan mudah dirawat dalam jangka panjang.',
                'image' => 'kode-mudah-dipelihara',
                'category' => 'Tips & Trik',
                'tags' => ['Clean Code', 'Best Practice', 'SOLID', 'Tips & Trik'],
                'content' => <<<'HTML'
<p>Menulis kode yang mudah dipelihara bukan soal seberapa pintar solusinya, tapi seberapa mudah orang lain (atau diri sendiri di masa depan) memahami dan mengubahnya. Berikut beberapa kebiasaan yang saya terapkan.</p>
<figure class="wp-block-image is-align-right" style="width: 40%"><img src="{{cover}}" alt="Kode yang rapi" /><figcaption>Kode rapi lebih murah dirawat.</figcaption></figure>
<h2>1. Beri nama yang jelas</h2>
<p>Nama variabel dan fungsi adalah dokumentasi paling murah. <code>isRegisteredForDiscounts</code> jauh lebih jelas daripada <code>flag2</code>. Kalau butuh komentar untuk menjelaskan nama, biasanya namanya yang perlu diperbaiki.</p>
<h2>2. Satu fungsi, satu tugas</h2>
<p>Fungsi yang panjang dan mengerjakan banyak hal sulit diuji dan sulit diubah. Pecah menjadi fungsi kecil yang masing-masing punya satu tanggung jawab. Ini sejalan dengan prinsip <em>Single Responsibility</em> dari SOLID.</p>
<h2>3. Hindari duplikasi, tapi jangan berlebihan</h2>
<p>Kode yang sama di tiga tempat memang layak dirapikan. Namun jangan terburu-buru membuat abstraksi untuk dua potongan kode yang kebetulan mirip. Abstraksi yang salah lebih mahal daripada sedikit duplikasi.</p>
<h2>4. Tulis tes untuk perilaku penting</h2>
<p>Tes bukan untuk mengejar angka coverage, melainkan agar kamu berani mengubah kode tanpa takut merusak fitur lain. Fokuskan pada logika bisnis dan kasus gagal yang paling mungkin terjadi.</p>
<h2>5. Review kode sendiri sebelum dikirim</h2>
<p>Baca ulang diff sebelum membuat pull request. Hampir selalu ada nama yang kurang pas, sisa debug, atau potongan kode yang tidak terpakai.</p>
<div class="wp-block-callout is-success" data-type="success"><p>Pegangan singkat: tulis kode untuk dibaca manusia dulu, baru untuk dijalankan mesin.</p></div>
<h2>Ringkasan</h2>
<ul>
<li>Nama yang jelas mengalahkan komentar panjang.</li>
<li>Fungsi kecil lebih mudah diuji.</li>
<li>Abstraksi dibuat saat polanya sudah terbukti.</li>
<li>Tes melindungi perilaku yang penting.</li>
</ul>
HTML,
            ],
            [
                'title' => 'Pengalaman Ikut Kompetisi Pengembangan Web',
                'slug' => 'pengalaman-ikut-kompetisi-pengembangan-web',
                'excerpt' => 'Cerita di balik layar mengikuti beberapa kompetisi pengembangan web dan pelajaran yang saya dapat.',
                'image' => 'kompetisi-web',
                'category' => 'Pengalaman',
                'tags' => ['Kompetisi Web', 'Pengalaman Pribadi', 'Hackathon 2024', 'Cerita'],
                'content' => <<<'HTML'
<p>Mengikuti kompetisi pengembangan web mengajarkan saya banyak hal, mulai dari manajemen waktu hingga cara mempresentasikan produk dengan baik. Di tulisan ini saya bagikan beberapa pelajaran dari perjalanan tersebut.</p>
<figure class="wp-block-image is-align-left" style="width: 45%"><img src="{{cover}}" alt="Suasana kompetisi web" /><figcaption>Suasana saat kompetisi berlangsung.</figcaption></figure>
<h2>Persiapan sebelum hari H</h2>
<p>Sebelum lomba dimulai, saya dan tim membagi peran dengan jelas: siapa yang menggarap backend, siapa yang menggarap tampilan, dan siapa yang menyiapkan presentasi. Pembagian ini menghemat banyak waktu karena tidak ada yang saling menunggu.</p>
<h2>Waktu terbatas, prioritas harus tegas</h2>
<p>Pada hackathon, waktu pengerjaan hanya beberapa jam. Kami memilih satu masalah utama dan hanya membangun fitur yang langsung menjawabnya. Fitur tambahan kami catat sebagai rencana pengembangan, bukan dikerjakan saat itu juga.</p>
<h2>Presentasi sama pentingnya dengan kode</h2>
<p>Juri tidak membaca seluruh kode kami. Mereka menilai dari demo dan cerita yang kami sampaikan. Karena itu kami menyiapkan alur demo yang singkat: masalah, solusi, lalu hasil nyata yang bisa dicoba langsung.</p>
<div class="wp-block-callout is-danger" data-type="danger"><p>Jangan menambah fitur baru di menit-menit terakhir. Bekukan kode lebih awal dan gunakan sisa waktu untuk latihan demo.</p></div>
<h2>Pelajaran yang saya bawa pulang</h2>
<ul>
<li>Komunikasi tim lebih menentukan daripada kemampuan teknis perorangan.</li>
<li>Kerjakan versi sederhana yang jalan dulu, rapikan belakangan.</li>
<li>Kalah atau menang, kamu pulang dengan relasi dan pengalaman baru.</li>
</ul>
<p>Kalau kamu ragu untuk ikut kompetisi, coba saja. Pengalaman di bawah tekanan waktu itu sulit didapat dari belajar sendirian di rumah.</p>
HTML,
            ],
            [
                'title' => 'Kerja Remote dari Kafe: Panduan Memilih Tempat yang Tepat',
                'slug' => 'kerja-remote-dari-kafe-panduan-memilih-tempat-yang-tepat',
                'excerpt' => 'Wi-Fi kencang saja belum cukup. Ini daftar hal yang saya periksa sebelum memutuskan sebuah kafe layak dijadikan kantor dadakan.',
                'image' => 'kerja-remote-dari-kafe',
                'category' => 'Tips & Trik',
                'tags' => ['Kerja Remote', 'Tempat Ngopi', 'Produktivitas', 'Tips & Trik'],
                'content' => <<<'HTML'
<p>Sebagai orang yang sering bekerja dari kafe, saya punya daftar periksa sendiri sebelum betah berlama-lama di satu tempat. Daftar ini juga yang menjadi dasar penilaian di halaman Tempat Ngopi di situs ini.</p>
<figure class="wp-block-image is-align-center"><img src="{{cover}}" alt="Bekerja dari kafe" /><figcaption>Satu sudut kafe favorit untuk bekerja.</figcaption></figure>
<h2>1. Kecepatan dan kestabilan Wi-Fi</h2>
<p>Kecepatan unduh yang besar tidak berarti apa-apa kalau koneksinya sering putus. Saya biasanya mencoba panggilan video singkat atau mengunggah satu berkas berukuran sedang. Kalau lancar, tempat itu lolos.</p>
<h2>2. Colokan listrik</h2>
<p>Baterai laptop tidak akan bertahan seharian. Pastikan ada colokan di dekat meja, idealnya di banyak sudut, bukan hanya satu dekat kasir.</p>
<h2>3. Tingkat kebisingan</h2>
<p>Musik pelan masih bisa diterima, tapi obrolan keras dan mesin penggiling yang sering berbunyi akan merusak fokus. Datang di jam sepi lebih aman untuk pekerjaan yang butuh konsentrasi.</p>
<h2>4. Kebijakan tempat duduk</h2>
<p>Beberapa kafe tidak keberatan kamu duduk lama, beberapa lainnya membatasi. Pesan secara wajar dan sopan, lalu tambah pesanan kalau memang berencana tinggal berjam-jam.</p>
<h2>5. Biaya parkir dan harga menu</h2>
<p>Hitung total pengeluaran, termasuk parkir. Kafe dengan harga menu bersahabat dan parkir gratis sering kali lebih hemat untuk dipakai rutin.</p>
<div class="wp-block-button is-solid is-align-center"><a class="wp-block-button__link" href="/tempat-ngopi" rel="noopener">Lihat rekomendasi tempat ngopi</a></div>
<h2>Penutup</h2>
<p>Setiap orang punya prioritas berbeda. Untuk saya, urutannya Wi-Fi, colokan, lalu suasana. Silakan lihat daftar rekomendasi di halaman Tempat Ngopi untuk menemukan kafe yang cocok dengan gaya kerjamu.</p>
HTML,
            ],
            [
                'title' => 'Mengenal Stack Laravel, Inertia, dan Svelte',
                'slug' => 'mengenal-stack-laravel-inertia-dan-svelte',
                'excerpt' => 'Alasan saya memilih Laravel sebagai backend, Inertia sebagai jembatan, dan Svelte sebagai antarmuka untuk situs ini.',
                'image' => 'laravel-inertia-svelte',
                'category' => 'Teknologi',
                'tags' => ['Laravel', 'Inertia.js', 'Svelte', 'Fullstack Development'],
                'content' => <<<'HTML'
<p>Situs yang sedang kamu baca ini dibangun dengan Laravel, Inertia, dan Svelte. Kombinasi ini memberi rasa aplikasi single-page tanpa harus membangun dan merawat API terpisah.</p>
<figure class="wp-block-image is-align-center" style="width: 75%"><img src="{{cover}}" alt="Laravel, Inertia, dan Svelte" /><figcaption>Tiga lapisan, satu alur kerja.</figcaption></figure>
<h2>Peran masing-masing</h2>
<ul>
<li><strong>Laravel</strong> menangani routing, validasi, autentikasi, dan database.</li>
<li><strong>Inertia</strong> menghubungkan controller Laravel langsung ke komponen halaman di frontend.</li>
<li><strong>Svelte</strong> merender antarmuka dengan kode yang ringkas dan performa yang baik.</li>
</ul>
<h2>Kenapa tanpa API terpisah?</h2>
<p>Dengan Inertia, controller cukup mengembalikan data sebagai props halaman. Tidak perlu menulis endpoint JSON, mengatur CORS, atau menyinkronkan dua proyek. Validasi tetap memakai Form Request milik Laravel, dan pesan galatnya otomatis sampai ke form di sisi klien.</p>
<h2>Kenapa Svelte?</h2>
<p>Svelte menghasilkan kode yang lebih sedikit dibanding banyak framework lain. Dengan Svelte 5, reaktivitas ditulis lewat <em>runes</em> yang eksplisit dan mudah dibaca, sehingga komponen form dan tabel di panel admin tetap singkat.</p>
<div class="wp-block-callout is-info" data-type="info"><p>Inertia bukan pengganti API. Ia menggantikan lapisan <em>fetch</em> antara controller dan halaman, bukan kontrak layanan untuk klien lain.</p></div>
<h2>Kekurangan yang perlu diketahui</h2>
<p>Pendekatan ini kurang cocok bila kamu memang butuh API publik untuk aplikasi mobile atau pihak ketiga. Dalam kasus itu, tambahkan lapisan API terpisah di samping halaman Inertia.</p>
<h2>Kesimpulan</h2>
<p>Untuk situs personal dan aplikasi internal, stack ini terasa efisien. Satu bahasa di backend, satu framework ringan di frontend, dan alur kerja yang konsisten dari database sampai tampilan.</p>
HTML,
            ],
            [
                'title' => 'Belajar Ngoding Secara Otodidak: Yang Saya Lakukan dan Sesali',
                'slug' => 'belajar-ngoding-secara-otodidak-yang-saya-lakukan-dan-sesali',
                'excerpt' => 'Catatan jujur tentang cara saya belajar pemrograman dari nol, termasuk kesalahan yang sebaiknya tidak kamu ulangi.',
                'image' => 'belajar-ngoding-otodidak',
                'category' => 'Pengalaman',
                'tags' => ['Belajar Coding', 'Pengalaman Pribadi', 'Karier', 'Cerita'],
                'content' => <<<'HTML'
<p>Banyak yang bertanya bagaimana saya mulai belajar pemrograman. Jawabannya tidak glamor: sedikit tutorial, banyak mencoba, dan cukup sering salah jalan. Tulisan ini merangkum apa yang berhasil dan apa yang saya sesali.</p>
<figure class="wp-block-image is-align-center" style="width: 50%"><img src="{{cover}}" alt="Belajar ngoding otodidak" /></figure>
<h2>Yang berhasil</h2>
<ul>
<li><strong>Membangun proyek nyata.</strong> Belajar paling cepat terjadi saat ada masalah sungguhan yang ingin diselesaikan, misalnya membuat sistem perpustakaan atau situs profil.</li>
<li><strong>Membaca kode orang lain.</strong> Repositori open source mengajarkan struktur dan kebiasaan yang tidak ada di tutorial.</li>
<li><strong>Menulis catatan.</strong> Blog ini awalnya hanya tempat saya mencatat hal yang baru dipelajari.</li>
</ul>
<h2>Yang saya sesali</h2>
<ul>
<li>Terlalu lama terjebak menonton tutorial tanpa membuat apa pun sendiri.</li>
<li>Berpindah-pindah bahasa dan framework sebelum menguasai dasarnya.</li>
<li>Malu bertanya dan baru meminta bantuan setelah berhari-hari buntu.</li>
</ul>
<h2>Saran untuk pemula</h2>
<p>Pilih satu bahasa dan dalami dasarnya: variabel, kontrol alur, fungsi, dan struktur data. Setelah itu buat proyek kecil yang kamu pakai sendiri. Jangan menunggu merasa siap, karena rasa siap biasanya baru muncul setelah kamu mulai.</p>
<p>Terakhir, bergabunglah dengan komunitas. Belajar bersama orang lain membuat prosesnya lebih ringan dan jauh lebih menyenangkan.</p>
HTML,
            ],
        ];
    }
}
