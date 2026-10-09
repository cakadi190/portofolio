<script lang="ts">
  import Icon from '@iconify/svelte';
  import { Link } from '@inertiajs/svelte';

  const year = new Date().getFullYear();

  const socials = [
    {
      label: "Cak Adi's Facebook",
      href: 'https://www.facebook.com/cakadi190',
      icon: 'fa6-brands:facebook',
    },
    {
      label: "Cak Adi's Twitter",
      href: 'https://www.twitter.com/cakadi190',
      icon: 'fa6-brands:twitter',
    },
    {
      label: "Cak Adi's Figma",
      href: 'https://www.figma.com/@cakadi190',
      icon: 'fa6-brands:figma',
    },
    {
      label: "Cak Adi's Instagram",
      href: 'https://www.instagram.com/cakadi190',
      icon: 'fa6-brands:instagram',
    },
    {
      label: "Cak Adi's Linkedin",
      href: 'https://www.linkedin.com/in/cakadi190',
      icon: 'fa6-brands:linkedin-in',
    },
  ];

  const workLinks = [
    { label: 'Pendidikan', href: '/pendidikan' },
    { label: 'Penghargaan', href: '/penghargaan' },
    { label: 'Pembicara', href: '/speaking' },
    { label: 'Skill & Perkakas', href: '/tentang#skills' },
    { label: 'Layanan', href: '/layanan' },
    { label: 'Portofolio Saya', href: '/portofolio' },
    { label: 'Karir', href: '/karir' },
  ];

  const resourceLinks = [
    { label: 'Blog Pribadi', href: '/blog' },
    { label: 'Referensi Ngoding', href: '#' },
    { label: 'Contekan Kode', href: '#' },
    { label: 'Tempat Ngopi', href: '/sumber-daya/tempat-ngopi' },
  ];

  const otherLinks = [
    { label: 'Tentang Situs', href: '/tentang/situs' },
    { label: 'Kredit', href: '#' },
    { label: 'Donasi di trakteer.id', href: '#' },
    { label: 'Donasi di ko-fi.com', href: '#' },
  ];
</script>

<footer class="site-footer border-top py-4 mt-auto">
  <div class="container py-4">
    <div class="row gy-5">
      <div class="col-md-6">
        <h5 class="h6">Tentang Saya</h5>
        <p class="opacity-75">
          Saya adalah seorang fullstack developer yang suka sekali dengan desain
          dan juga tertarik di dunia teknologi. Saya adalah orang kelahiran
          Klaten, Jawa Tengah dan berdomisili di Ngawi, Jawa Timur.
        </p>
        <div class="d-flex gap-3 mt-4">
          {#each socials as social (social.href)}
            <a
              aria-label={social.label}
              class="social-link"
              href={social.href}
              target="_blank"
              rel="noopener"
            >
              <Icon icon={social.icon} width={20} height={20} />
            </a>
          {/each}
        </div>
      </div>

      <div class="col-lg-2 col-6">
        <h5 class="h6">Ke(-rjaan/gabut)-an Saya</h5>
        <ul class="list-unstyled">
          {#each workLinks as link (link.label)}
            <li><Link href={link.href}>{link.label}</Link></li>
          {/each}
        </ul>
      </div>

      <div class="col-lg-2 col-6">
        <h5 class="h6">Sumber Daya</h5>
        <ul class="list-unstyled">
          {#each resourceLinks as link (link.label)}
            <li><Link href={link.href}>{link.label}</Link></li>
          {/each}
        </ul>
      </div>

      <div class="col-md-2">
        <h5 class="h6">Tautan Lain-lain</h5>
        <ul class="list-unstyled">
          {#each otherLinks as link (link.label)}
            <li><Link href={link.href}>{link.label}</Link></li>
          {/each}
        </ul>
      </div>
    </div>
  </div>

  <div class="container mt-3">
    <div class="row">
      <div class="col-md-6 text-center text-lg-start">
        <p class="mb-0">Hak cipta &copy; {year} Cak Adi.</p>
      </div>
      <div class="col-md-6 text-center text-lg-end">
        <p class="mb-0">Made with ❤ in Ngawi, Indonesia.</p>
      </div>
    </div>
  </div>
</footer>
