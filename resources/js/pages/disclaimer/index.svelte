<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import HeaderPage from '@/components/header-page.svelte';
  import { index as clarifications } from '@/wayfinder/routes/clarifications';
  import { index as contact } from '@/wayfinder/routes/contact';

  const sections = [
    {
      title: 'Opini pribadi',
      body: 'Artikel, catatan, dan klarifikasi di situs ini adalah pendapat dan pengalaman pribadi pemilik situs, bukan nasihat profesional, hukum, maupun finansial.',
    },
    {
      title: 'Tanpa jaminan akurasi',
      body: 'Saya berusaha menulis dengan benar, tetapi informasi dapat usang atau keliru. Gunakan dengan pertimbangan sendiri dan verifikasi ke sumber resmi sebelum mengambil keputusan.',
    },
    {
      title: 'Tautan pihak ketiga',
      body: 'Situs ini memuat tautan ke situs lain. Isi, kebijakan, dan keamanan situs tersebut berada di luar kendali saya.',
    },
    {
      title: 'Hak cipta',
      body: 'Tulisan dan karya asli di situs ini dilindungi hak cipta. Anda boleh mengutip sebagian dengan menyebutkan sumber dan tautan; penyalinan penuh memerlukan izin tertulis.',
    },
    {
      title: 'Komentar dan ulasan',
      body: 'Komentar blog dan ulasan portofolio dimoderasi dan mencerminkan pendapat penulisnya. Saya berhak menolak atau menghapus konten yang berisi spam, ujaran kebencian, data pribadi, atau tuduhan tanpa dasar.',
    },
    {
      title: 'Klarifikasi',
      body: 'Halaman klarifikasi adalah tanggapan pemilik situs sebagai pihak yang bersangkutan dan bukan pemeriksaan fakta independen.',
    },
    {
      title: 'Batas tanggung jawab',
      body: 'Penggunaan situs ini sepenuhnya menjadi tanggung jawab Anda. Saya tidak bertanggung jawab atas kerugian yang timbul dari penggunaan informasi di dalamnya.',
    },
  ];
</script>

<AppHead title="Penyangkalan & Ketentuan" />

<div>
  <HeaderPage
    title="Penyangkalan & Ketentuan"
    subtitle="Aturan penggunaan situs, kebijakan komentar, dan cara mengajukan koreksi."
  />

  <section class="need-space pt-0">
    <div class="container">
      {#each sections as section (section.title)}
        <h2 class="h5 mt-4">{section.title}</h2>
        <p class="opacity-75 mb-0">{section.body}</p>
      {/each}

      <h2 class="h5 mt-4">Koreksi dan hak jawab</h2>
      <p class="opacity-75 mb-0">
        Bila Anda menemukan kekeliruan atau merasa dirugikan oleh konten di sini, pilih
        keperluan "Koreksi / Hak Jawab" pada <a href={contact().url}>formulir kontak</a>. Lihat
        juga <a href={clarifications().url}>daftar klarifikasi</a>.
      </p>
    </div>
  </section>
</div>
