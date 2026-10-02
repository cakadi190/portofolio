<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Site-wide SEO defaults
    |--------------------------------------------------------------------------
    |
    | Values used by App\Services\SeoService when a page does not provide its
    | own. `image` is relative to the public directory and must be 1200x630.
    |
    */

    'description' => 'Amir Zuhdi Wibowo (Cak Adi) — fullstack web developer asal Ngawi. Portofolio, artikel, dan catatan seputar pengembangan web dan teknologi.',

    'locale' => 'id_ID',

    'image' => '/images/og-default.png',

    'image_alt' => 'Catatan Cak Adi — portofolio dan artikel fullstack web developer',

    'author' => 'Amir Zuhdi Wibowo',

    'twitter' => '@cakadi190',

    'social_profiles' => [
        'https://www.twitter.com/cakadi190',
        'https://www.instagram.com/cakadi190',
        'https://www.linkedin.com/in/cakadi190',
    ],

    /*
    |--------------------------------------------------------------------------
    | Static public pages
    |--------------------------------------------------------------------------
    |
    | Keyed by route name. Drives the <title>/description of each page and the
    | static pages sitemap. Pages absent from this list (and from per-model
    | overrides) are served with `noindex`.
    |
    */

    'pages' => [
        'home' => [
            'title' => 'Beranda',
            'description' => 'Seorang Fullstack Web Developer yang berbasis di Kabupaten Ngawi yang suka sekali dengan desain dan juga hal yang berbau teknologi.',
            'changefreq' => 'weekly',
            'priority' => '1.0',
        ],
        'about.me' => [
            'title' => 'Tentang Saya',
            'description' => 'Kenali Amir Zuhdi Wibowo, fullstack web developer asal Ngawi, beserta pengalaman, perjalanan karir, dan teknologi yang dikuasainya.',
            'changefreq' => 'monthly',
            'priority' => '0.8',
        ],
        'about.skills' => [
            'title' => 'Keahlian',
            'description' => 'Jelajahi keahlian Cak Adi dalam pengembangan frontend, backend, basis data, desain, dan berbagai teknologi web modern.',
            'changefreq' => 'monthly',
            'priority' => '0.6',
        ],
        'about.site' => [
            'title' => 'Tentang Situs',
            'description' => 'Informasi tentang situs pribadi Cak Adi, tujuan pembuatannya, serta teknologi yang digunakan untuk membangunnya.',
            'changefreq' => 'yearly',
            'priority' => '0.3',
        ],
        'portfolios.index' => [
            'title' => 'Portofolio',
            'description' => 'Berikut daftar portofolio yang sudah saya kerjakan dan selesaikan akhir-akhir ini.',
            'changefreq' => 'weekly',
            'priority' => '0.9',
        ],
        'blog.index' => [
            'title' => 'Artikel',
            'description' => 'Kumpulan artikel Cak Adi tentang pengembangan web, teknologi, desain, dan pengalaman membangun produk digital.',
            'changefreq' => 'daily',
            'priority' => '0.9',
        ],
        'services.index' => [
            'title' => 'Layanan Saya',
            'description' => 'Berikut layanan yang bisa saya berikan dan layani untuk anda.',
            'changefreq' => 'monthly',
            'priority' => '0.7',
        ],
        'career.index' => [
            'title' => 'Karir Saya',
            'description' => 'Berikut daftar riwayat karir saya yang mana saya sudah berkarir di berbagai tempat.',
            'changefreq' => 'monthly',
            'priority' => '0.5',
        ],
        'education.index' => [
            'title' => 'Pendidikan dan Organisasi',
            'description' => 'Daftar riwayat pendidikan saya, yang mana saya tampilkan daftar tempat saya bersekolah dan menempuh pendidikan. Serta saya telah mengikuti kegiatan apa saja.',
            'changefreq' => 'yearly',
            'priority' => '0.4',
        ],
        'awards.index' => [
            'title' => 'Penghargaan',
            'description' => 'Berikut beberapa daftar penghargaan yang sudah saya raih dan capai.',
            'changefreq' => 'monthly',
            'priority' => '0.4',
        ],
        'resources.coffee-shops.index' => [
            'title' => 'Tempat Ngopi',
            'description' => 'Berikut daftar tempat ngopi yang saya rekomendasikan.',
            'changefreq' => 'monthly',
            'priority' => '0.5',
        ],
        'contact.index' => [
            'title' => 'Hubungi Saya',
            'description' => 'Berikut kontak yang dapat dihubungi apabila anda tertarik dengan skill saya maupun ingin bekerjasama dengan saya.',
            'changefreq' => 'yearly',
            'priority' => '0.6',
        ],
    ],

];
