<script lang="ts">
  import Icon from '@iconify/svelte';
  import Link from '@/components/link.svelte';
  import { t } from '@/lib/i18n';

  import fotoku480 from '../../../images/hero/fotoku-480.webp?no-inline';
  import fotoku640 from '../../../images/hero/fotoku-640.webp?no-inline';
  import fotoku720 from '../../../images/hero/fotoku-720.webp?no-inline';
  import fotoku800 from '../../../images/hero/fotoku-800.webp?no-inline';

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
    let tooltips: import('@/lib/bootstrap').Tooltip[] = [];

    void import('@/lib/bootstrap').then(({ Tooltip }) => {
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
        <div class="breaking-news">
          {t('home.hero.award_prefix')}
          <a
            href="https://www.instagram.com/p/C6faMQ2yld-/?img_index=2"
            target="_blank"
            rel="noopener"
          >
            {t('home.hero.award_title')}<span class="d-none d-xl-none d-xxl-inline-block"
              >{@html t('home.hero.award_former')}</span
            >
          </a>
          {t('home.hero.award_suffix')}
        </div>

        <p class="h4">{t('home.hero.greeting')}</p>
        <h1 class="main-title justify-content-center justify-content-lg-start">
          <div>
            <span class="text-primary text-decoration-underline">A</span>
            <span>mir&nbsp;</span>
            <span>Zuh</span>
            <span class="text-primary text-decoration-underline">di</span>
          </div>
          <span>Wibowo, A.Md.Kom.</span>
        </h1>
        <p class="mb-4 main-desc">{@html t('home.hero.description')}</p>

        <div
          class="d-flex pb-2 gap-2 justify-content-center justify-content-lg-start align-items-center"
        >
          <Link href="/contact" class="btn btn-primary d-flex align-items-center gap-2">
            <Icon icon="fa6-solid:phone" />
            <span>{t('home.hero.contact')}</span>
          </Link>

          <Link href="/resume" class="btn btn-outline-primary d-flex align-items-center gap-2">
            <Icon icon="fa6-solid:file-pdf" />
            <span>{t('home.hero.resume')}</span>
          </Link>
        </div>

        <div
          class="d-flex pt-4 flex-column gap-2"
        >
          <h2 class="h5 text-center text-lg-start">{@html t('home.hero.stack')}</h2>

          <div class="d-flex flex-wrap justify-content-center justify-content-lg-start gap-3">
            {#each techStack as item, index (index)}
              {#if item.separator}
                <div class="vr h-100"></div>
              {:else if item.icon}
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
        <!-- LCP element. -->
        <img
          src={fotoku800}
          srcset="{fotoku480} 480w, {fotoku640} 640w, {fotoku720} 720w, {fotoku800} 800w"
          sizes="(min-width: 768px) 40vw, 90vw"
          width="800"
          height="800"
          fetchpriority="high"
          decoding="async"
          alt="Cak Adi"
          class="w-100 h-auto"
        />
      </div>
    </div>
  </div>
</header>
