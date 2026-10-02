<script lang="ts">
  import Icon from '@iconify/svelte';
  import Medal from '@lucide/svelte/icons/medal';
  import Handshake from '@lucide/svelte/icons/handshake';
  import Briefcase from '@lucide/svelte/icons/briefcase';
  import { Link } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import FileText from '@lucide/svelte/icons/file-text';
  import HeaderPage from '@/components/header-page.svelte';
  import { formatDate } from '@/lib/utils';

  type Certification = {
    id: number;
    title: string;
    issuer: string;
    issuedAt: string;
    expiresAt: string | null;
    credentialId: string | null;
    credentialUrl: string | null;
    file: string | null;
    isPdf: boolean;
  };

  let {
    yearsExperience,
    yearsServing,
    totalProjects,
    certifications,
  }: {
    yearsExperience: number;
    yearsServing: number;
    totalProjects: number;
    certifications: Certification[];
  } = $props();

  const skillset = [
    { icon: 'devicon:laravel', name: 'Laravel' },
    { icon: 'devicon:nodejs', name: 'NodeJS' },
    { icon: 'devicon:python', name: 'Python' },
    { icon: 'devicon:java', name: 'Java' },
    { icon: 'devicon:dart', name: 'Dart' },
    { icon: 'devicon:kotlin', name: 'Kotlin' },
    { icon: 'devicon:express', name: 'ExpressJS' },
    { icon: 'devicon:go', name: 'GoLang' },
    { icon: 'devicon:vitejs', name: 'ViteJS' },
    { icon: 'devicon:mysql', name: 'MySQL' },
    { icon: 'devicon:mariadb', name: 'MariaDB' },
    { icon: 'devicon:mongodb', name: 'MongoDB' },
    { icon: 'devicon:sqlite', name: 'SQLite' },
    { icon: 'devicon:flutter', name: 'Flutter' },
    { icon: 'devicon:vercel', name: 'Vercel' },
    { icon: 'devicon:php', name: 'PHP' },
    { icon: 'devicon:html5', name: 'HTML' },
    { icon: 'devicon:css3', name: 'CSS' },
    { icon: 'devicon:javascript', name: 'Javascript' },
    { icon: 'devicon:vuejs', name: 'VueJS' },
    { icon: 'devicon:svelte', name: 'Svelte' },
    { icon: 'devicon:bootstrap', name: 'Bootstrap' },
    { icon: 'devicon:tailwindcss', name: 'TailwindCSS' },
    { icon: 'devicon:react', name: 'ReactJS' },
    { icon: 'devicon:nextjs', name: 'NextJS' },
    { icon: 'devicon:jquery', name: 'jQuery' },
    { icon: 'devicon:typescript', name: 'Typescript' },
    { icon: 'devicon:git', name: 'Git' },
    { icon: 'devicon:figma', name: 'Figma' },
    { icon: 'logos:adobe-illustrator', name: 'Adobe Illustrator' },
    { icon: 'logos:adobe-photoshop', name: 'Adobe Photoshop' },
    { icon: 'logos:visual-studio-code', name: 'Visual Studio Code' },
  ];
</script>

<AppHead title="Tentang Saya" />

<div id="about-me">
  <HeaderPage title="Tentang Saya" subtitle="Berikut sekilas tentang diri saya pribadi" />

  <section class="need-space pt-0">
    <div class="container">
      <div class="row flex-column-reverse flex-md-row gy-5">
        <div class="col-md-9">
          <div class="row gy-3 pb-5">
            <div class="col-md-4">
              <div
                class="card card-body rounded-4 p-4 text-center align-items-center reveal reveal-tween reveal-bottom"
                style="--reveal-y: 40px; --reveal-duration: .35s"
              >
                <Medal size={48} />
                <strong class="h3 mb-0 pt-3">{yearsExperience} Tahun</strong>
                <span>Berpengalaman</span>
              </div>
            </div>
            <div class="col-md-4">
              <div
                class="card card-body rounded-4 p-4 text-center align-items-center reveal reveal-tween reveal-bottom"
                style="--reveal-y: 40px; --reveal-duration: .35s"
              >
                <Handshake size={48} />
                <strong class="h3 mb-0 pt-3">{yearsServing} Tahun</strong>
                <span>Melayani</span>
              </div>
            </div>
            <div class="col-md-4">
              <Link
                href="/portofolio"
                class="card card-body rounded-4 p-4 text-center align-items-center text-decoration-none reveal reveal-tween reveal-bottom"
                style="--reveal-y: 40px; --reveal-duration: .35s"
              >
                <Briefcase size={48} />
                <strong class="h3 mb-0 pt-3">{totalProjects}</strong>
                <span>Proyek</span>
              </Link>
            </div>
          </div>

          <div class="mb-5 pb-5 border-bottom">
            <div class="row gy-4">
              <div class="col-md-5">
                <img
                  src="/images/programmer.svg"
                  class="w-100 reveal reveal-tween reveal-left"
                  style="--reveal-x: -40px; --reveal-duration: .35s"
                  alt="Ilustrasi programmer"
                />
              </div>
              <div class="col-md-7 reveal reveal-tween reveal-right" style="--reveal-x: 40px; --reveal-duration: .35s">
                <p>
                  Hai, saya <strong>Amir Zuhdi Wibowo, A.Md.Kom.</strong>, seorang
                  <strong>Software Engineer</strong> dan <strong>Wirausahawan</strong> yang hobi ngulik dari
                  front-end sampai back-end. Saya senang membuat aplikasi web yang nggak cuma berfungsi
                  dengan baik tapi juga enak dilihat. Dari HTML, CSS, JavaScript, sampai PHP, saya punya
                  pengalaman yang luas untuk memastikan proyek web berjalan lancar dan terlihat menarik.
                </p>
                <p>
                  Selain coding, saya juga jago di beberapa bidang IT lainnya. Saya bisa bikin desain
                  grafis yang kece dengan Photoshop dan Illustrator, merancang UI/UX yang user-friendly
                  pakai Figma, dan bahkan reparasi komputer ringan kalau ada masalah. Kombinasi
                  keterampilan ini bikin saya siap banget untuk memberikan yang terbaik di setiap proyek
                  yang saya kerjakan.
                </p>
              </div>
            </div>
          </div>

          <div id="history" class="pb-4 mb-4 border-bottom">
            <h4>Sejarah Singkat</h4>

            <p>
              Perjalanan saya sebagai programmer dimulai secara tidak sengaja pada pertengahan 2015 saat
              mengerjakan tugas PKn. Awalnya berkenalan dengan
              <a href="https://blogger.com" rel="noreferrer" target="_blank">Blogger</a>, lalu beralih ke
              WordPress, yang memperkenalkan saya pada konsep CMS yang lebih kompleks. Langkah berikutnya
              membawa saya ke dunia hosting dan manajemen server, di mana saya mempelajari panel kontrol
              seperti cPanel dan aspek teknis di balik sebuah website.
            </p>

            <p>
              Perjalanan ini berlanjut dengan mempelajari dasar-dasar HTML, CSS, dan JavaScript untuk
              frontend, serta PHP dan MySQL untuk backend. Saya menghadapi berbagai tantangan, dari error
              yang membingungkan hingga konsep-konsep sulit. Seiring waktu, saya mengeksplorasi teknologi
              modern seperti Node.js dan React, memperluas pemahaman saya tentang ekosistem pengembangan
              web fullstack.
            </p>

            <p>
              Menjadi fullstack developer adalah perjalanan penuh liku. Ada saat-saat frustrasi ketika
              menghadapi bug atau konfigurasi server yang kompleks, namun juga ada momen membahagiakan
              saat berhasil mengoptimalkan website atau mengimplementasikan fitur challenging. Kini,
              setelah bertahun-tahun, saya bersyukur atas perjalanan tidak disengaja ini yang telah
              membentuk karir dan passion saya dalam dunia pengembangan web.
            </p>
          </div>

          <div id="skills" class="pb-5 mb-5 border-bottom">
            <h4>Keahlian Saya</h4>
            <p class="opacity-75 mb-5">Keahlian profesional saya.</p>

            <div class="skill-main-wrapper">
              {#each skillset as item (item.name)}
                <div class="skill">
                  <Icon icon={item.icon} width={24} height={24} />
                  <p class="m-0">{item.name}</p>
                </div>
              {/each}
            </div>
          </div>

          <div id="certification">
            <h4>Sertifikasi Saya</h4>
            {#if certifications.length === 0}
              <p class="opacity-75 mb-0">Belum ada sertifikasi yang ditambahkan saat ini.</p>
            {:else}
              <p class="opacity-75 mb-4">Sertifikat dan kredensial yang pernah saya peroleh.</p>

              <div class="row g-4">
                {#each certifications as certification (certification.id)}
                  <div class="col-md-6">
                    <div class="card h-100 overflow-hidden rounded-4">
                      {#if certification.file}
                        <a
                          href={certification.file}
                          target="_blank"
                          rel="noopener"
                          class="d-block text-center bg-body-tertiary"
                        >
                          {#if certification.isPdf}
                            <div class="py-5 d-flex flex-column align-items-center gap-2">
                              <FileText size={40} />
                              <span class="small">Lihat sertifikat (PDF)</span>
                            </div>
                          {:else}
                            <img
                              src={certification.file}
                              alt={certification.title}
                              class="w-100 object-fit-cover"
                              style="max-height: 14rem"
                              loading="lazy"
                            />
                          {/if}
                        </a>
                      {/if}
                      <div class="card-body">
                        <h5 class="mb-1">{certification.title}</h5>
                        <p class="mb-2 opacity-75">{certification.issuer}</p>
                        <p class="small mb-0">
                          Terbit {formatDate(certification.issuedAt)}{certification.expiresAt
                            ? ` · Berlaku hingga ${formatDate(certification.expiresAt)}`
                            : ''}
                        </p>
                        {#if certification.credentialId}
                          <p class="small opacity-75 mb-0">ID: {certification.credentialId}</p>
                        {/if}
                        {#if certification.credentialUrl}
                          <a
                            href={certification.credentialUrl}
                            target="_blank"
                            rel="noopener"
                            class="small">Verifikasi kredensial</a
                          >
                        {/if}
                      </div>
                    </div>
                  </div>
                {/each}
              </div>
            {/if}
          </div>
        </div>
        <div class="col-md-3">
          <div
            class="card sticky-top overflow-hidden rounded-4 reveal reveal-tween reveal-bottom"
            style="--reveal-y: 40px; --reveal-duration: .35s"
          >
            <div class="card-header p-4">
              <h4 class="mb-0">Navigasi</h4>
            </div>
            <div class="card-body">
              <ul class="nav nav-pills flex-column align-items-stretch">
                <li class="nav-item">
                  <a href="#about-me" class="nav-link py-3 w-100 text-start">Tentang Saya</a>
                </li>
                <li class="nav-item">
                  <a href="#history" class="nav-link py-3 w-100 text-start">Sejarah Singkat Saya</a>
                </li>
                <li class="nav-item">
                  <a href="#skills" class="nav-link py-3 w-100 text-start">Keahlian</a>
                </li>
                <li class="nav-item">
                  <a href="#certification" class="nav-link py-3 w-100 text-start">Sertifikasi</a>
                </li>
                <li class="nav-item">
                  <Link href="/tentang/skill" class="nav-link py-3 w-100 text-start">Perkakas</Link>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
