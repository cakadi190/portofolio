<script lang="ts">
  import Icon from '@iconify/svelte';
  import { Link } from '@inertiajs/svelte';

  type TechStackItem = { icon?: string; name?: string; separator?: true };

  const techStack: TechStackItem[] = [
    { icon: 'devicon:laravel', name: 'Laravel' },
    { icon: 'devicon:vuejs', name: 'VueJS' },
    { icon: 'devicon:bootstrap', name: 'Bootstrap' },
    { icon: 'devicon:nuxtjs', name: 'NuxtJS' },
    { icon: 'devicon:nextjs', name: 'NextJS' },
    { icon: 'devicon:docker', name: 'Docker' },
    { separator: true },
    { icon: 'devicon:vscode', name: 'Visual Studio Code' },
    { icon: 'devicon:figma', name: 'Figma' },
    { icon: 'devicon:ubuntu', name: 'Linux Ubuntu' },
  ];

  $effect(() => {
    let tooltips: import('bootstrap').Tooltip[] = [];

    void import('bootstrap').then(({ Tooltip }) => {
      tooltips = Array.from(
        document.querySelectorAll<HTMLElement>('#masthead [data-bs-toggle="tooltip"]'),
      ).map((el) => new Tooltip(el));
    });

    return () => tooltips.forEach((tooltip) => tooltip.dispose());
  });
</script>

<header id="masthead">
  <div class="container">
    <div class="row gy-4">
      <div class="col-md-7 text-center text-lg-start">
        <div class="breaking-news reveal reveal-left">
          Website ini mendapatkan penghargaan 🏆
          <a
            href="https://www.instagram.com/p/C6faMQ2yld-/?img_index=2"
            target="_blank"
            rel="noopener"
          >
            #3 Kompetisi Desain Website Personal CV Maroon Day HIMAFORKA
            Universitas Teknologi Digital Indonesia<span
              class="d-none d-xl-none d-xxl-inline-block">&nbsp;(d/h STMIK AKAKOM Yogyakarta)</span
            >
          </a>
          tahun 2024.
        </div>

        <h2 class="h4 reveal reveal-left" style="animation-delay: .1s">
          Halo rek! 👋 Perkenalkan, Namaku...
        </h2>
        <h1
          class="main-title justify-content-center justify-content-lg-start reveal reveal-left"
          style="animation-delay: .2s"
        >
          <div>
            <span class="text-primary text-decoration-underline">A</span>
            <span>mir&nbsp;</span>
            <span>Zuh</span>
            <span class="text-primary text-decoration-underline">di</span>
          </div>
          <span>Wibowo, A.Md.Kom.</span>
        </h1>
        <p class="mb-4 main-desc reveal reveal-left" style="animation-delay: .3s">
          Seorang <strong>Software Engineer</strong> dan <strong>Wirausahawan</strong> yang berbasis
          di Kabupaten Ngawi yang suka sekali dengan desain dan juga hal yang berbau
          <strong>teknologi</strong>.
        </p>

        <div
          class="d-flex pb-2 gap-2 justify-content-center justify-content-lg-start align-items-center reveal reveal-left"
          style="animation-delay: .4s"
        >
          <Link href="/kontak" class="btn btn-primary d-flex align-items-center gap-2">
            <Icon icon="fa6-solid:phone" />
            <span>Hubungi Saya</span>
          </Link>

          <a
            href="https://s.id/cvnyaCakAdi"
            class="btn btn-outline-primary d-flex align-items-center gap-2"
            target="_blank"
            rel="noopener"
          >
            <Icon icon="fa6-solid:file-pdf" />
            <span>Resume</span>
          </a>
        </div>

        <div class="d-flex pt-4 flex-column gap-2 reveal reveal-left" style="animation-delay: .5s">
          <h5 class="text-center text-lg-start"><em>Tech Stack</em> Andalan Saya</h5>

          <div class="d-flex flex-wrap justify-content-center justify-content-lg-start gap-3">
            {#each techStack as item, index (index)}
              {#if item.separator}
                <div class="vr h-100"></div>
              {:else}
                <span
                  class="tech-stack"
                  data-bs-toggle="tooltip"
                  data-bs-placement="top"
                  data-bs-title={item.name}
                >
                  <Icon icon={item.icon} width={32} height={32} />
                </span>
              {/if}
            {/each}
          </div>
        </div>
      </div>
      <div class="col-md-5">
        <img
          src="/images/fotoku.webp"
          alt="Cak Adi"
          class="w-100 reveal reveal-right"
        />
      </div>
    </div>
  </div>
</header>
