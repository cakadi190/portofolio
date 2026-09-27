<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Seed the blog posts, ported from the Nuxt app's seed_posts.ts.
     */
    public function run(): void
    {
        $items = [
            [
                'title' => 'Membangun Fullstack App dengan Nuxt dan Prisma',
                'slug' => 'membangun-fullstack-app-dengan-nuxt-dan-prisma',
                'excerpt' => 'Cerita dan pengalaman membangun aplikasi fullstack menggunakan Nuxt di sisi frontend dan Prisma sebagai lapisan data.',
                'content' => '<p>Nuxt dan Prisma adalah kombinasi yang sangat produktif untuk membangun aplikasi fullstack modern. Dalam artikel ini saya membahas bagaimana kedua tools ini saling melengkapi.</p>',
                'category' => 'Teknologi',
                'tags' => ['Nuxt', 'Prisma ORM', 'Fullstack Development', 'JavaScript'],
            ],
            [
                'title' => 'Tips Menulis Kode yang Mudah Dipelihara',
                'slug' => 'tips-menulis-kode-yang-mudah-dipelihara',
                'excerpt' => 'Beberapa prinsip sederhana yang saya pakai supaya kode tetap rapi dan mudah dirawat dalam jangka panjang.',
                'content' => '<p>Menulis kode yang mudah dipelihara bukan soal seberapa pintar solusinya, tapi seberapa mudah orang lain (atau diri sendiri di masa depan) memahami dan mengubahnya.</p>',
                'category' => 'Tips & Trik',
                'tags' => ['Clean Code', 'Best Practice', 'SOLID', 'tips & trik'],
            ],
            [
                'title' => 'Pengalaman Ikut Kompetisi Pengembangan Web',
                'slug' => 'pengalaman-ikut-kompetisi-pengembangan-web',
                'excerpt' => 'Cerita di balik layar mengikuti beberapa kompetisi pengembangan web dan pelajaran yang saya dapat.',
                'content' => '<p>Mengikuti kompetisi pengembangan web mengajarkan saya banyak hal, mulai dari manajemen waktu hingga cara mempresentasikan produk dengan baik.</p>',
                'category' => 'Pengalaman',
                'tags' => ['Kompetisi Web', 'Pengalaman Pribadi', 'Hackathon 2024', 'cerita'],
            ],
        ];

        foreach ($items as $item) {
            $now = now();

            $post = Post::query()->create([
                'title' => $item['title'],
                'slug' => $item['slug'],
                'excerpt' => $item['excerpt'],
                'content' => $item['content'],
                'is_published' => true,
                'published_at' => $now,
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
}
