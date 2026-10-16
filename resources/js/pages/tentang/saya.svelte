<script lang="ts">
  import { t } from '@/lib/i18n';
  import Icon from '@iconify/svelte';
  import Eye from '@lucide/svelte/icons/eye';
  import Medal from '@lucide/svelte/icons/medal';
  import Handshake from '@lucide/svelte/icons/handshake';
  import Briefcase from '@lucide/svelte/icons/briefcase';
  import Link from '@/components/link.svelte';
  import AppHead from '@/components/app-head.svelte';
  import FileText from '@lucide/svelte/icons/file-text';
  import HeaderPage from '@/components/header-page.svelte';

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
    pageUrl: string;
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

<AppHead title={t('about.title')} />

<div id="about-me">
  <HeaderPage title={t('about.title')} subtitle={t('about.subtitle')} />

  <section class="need-space pt-0">
    <div class="container">
      <div class="row flex-column-reverse flex-md-row gy-5">
        <div class="col-md-9">
          <div class="row gy-3 pb-5">
            <div class="col-md-4">
              <div
                class="card card-body rounded-4 p-4 text-center align-items-center"
              >
                <Medal size={48} />
                <strong class="h3 mb-0 pt-3">{t('about.years', { count: yearsExperience })}</strong>
                <span>{t('about.experienced')}</span>
              </div>
            </div>
            <div class="col-md-4">
              <div
                class="card card-body rounded-4 p-4 text-center align-items-center"
              >
                <Handshake size={48} />
                <strong class="h3 mb-0 pt-3">{t('about.years', { count: yearsServing })}</strong>
                <span>{t('about.serving')}</span>
              </div>
            </div>
            <div class="col-md-4">
              <Link
                href="/portfolio"
                class="card card-body rounded-4 p-4 text-center align-items-center text-decoration-none"
              >
                <Briefcase size={48} />
                <strong class="h3 mb-0 pt-3">{totalProjects}</strong>
                <span>{t('about.projects')}</span>
              </Link>
            </div>
          </div>

          <div class="mb-5 pb-5 border-bottom">
            <div class="row gy-4">
              <div class="col-md-5">
                <img
                  src="/images/programmer.svg"
                  width="787"
                  height="572"
                  class="w-100 h-auto"
                  alt={t('about.illustration')}
                />
              </div>
              <div class="col-md-7">
                <p>{@html t('about.intro1')}</p>
                <p>{t('about.intro2')}</p>
              </div>
            </div>
          </div>

          <div id="history" class="pb-4 mb-4 border-bottom">
            <h2 class="h4">{t('about.history.title')}</h2>

            <p>
              {t('about.history.p1_before')}
              <a href="https://blogger.com" rel="noreferrer" target="_blank">Blogger</a>{t('about.history.p1_after')}
            </p>

            <p>{t('about.history.p2')}</p>

            <p>{t('about.history.p3')}</p>
          </div>

          <div id="skills" class="pb-5 mb-5 border-bottom">
            <h2 class="h4">{t('about.skills.title')}</h2>
            <p class="opacity-75 mb-5">{t('about.skills.subtitle')}</p>

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
            <h2 class="h4">{t('about.certs.title')}</h2>
            {#if certifications.length === 0}
              <p class="opacity-75 mb-0">{t('about.certs.empty')}</p>
            {:else}
              <p class="opacity-75 mb-4">{t('about.certs.subtitle')}</p>

              <div class="row g-4">
                {#each certifications as certification (certification.id)}
                  <div class="col-md-6">
                    <div class="card h-100 overflow-hidden rounded-4">
                      <div
                        class="card-body d-flex align-items-center justify-content-between gap-3"
                      >
                        <h3 class="h5 mb-0">{certification.title}</h3>
                        <Link
                          href={certification.pageUrl}
                          class="btn btn-sm btn-primary flex-shrink-0 d-none d-md-inline-block"
                        >
                          {t('about.certs.show')}
                        </Link>
                        <Link
                          href={certification.pageUrl}
                          class="btn btn-sm btn-primary flex-shrink-0 d-md-none"
                          aria-label={t('about.certs.view', { title: certification.title })}
                        >
                          <Eye size={18} />
                        </Link>
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
            class="card sticky-top overflow-hidden rounded-4"
          >
            <div class="card-header p-4">
              <h2 class="h4 mb-0">{t('about.nav.title')}</h2>
            </div>
            <div class="card-body">
              <ul class="nav nav-pills flex-column align-items-stretch">
                <li class="nav-item">
                  <a href="#about-me" class="nav-link py-3 w-100 text-start">{t('about.nav.about')}</a>
                </li>
                <li class="nav-item">
                  <a href="#history" class="nav-link py-3 w-100 text-start">{t('about.nav.history')}</a>
                </li>
                <li class="nav-item">
                  <a href="#skills" class="nav-link py-3 w-100 text-start">{t('about.nav.skills')}</a>
                </li>
                <li class="nav-item">
                  <a href="#certification" class="nav-link py-3 w-100 text-start">{t('about.nav.certs')}</a>
                </li>
                <li class="nav-item">
                  <a href="#skills" class="nav-link py-3 w-100 text-start">{t('about.nav.tools')}</a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
