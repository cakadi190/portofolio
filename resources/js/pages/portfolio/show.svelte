<script lang="ts">
  import Icon from '@iconify/svelte';
  import Info from '@lucide/svelte/icons/info';
  import Link2 from '@lucide/svelte/icons/link-2';
  import { techIcon } from '@/lib/tech-icon';
  import AppHead from '@/components/app-head.svelte';
  import HeaderPage from '@/components/header-page.svelte';

  type Portfolio = {
    name: string;
    shortDesc: string | null;
    description: string | null;
    image: string;
    demoLink: string | null;
    sourceCode: string | null;
    isPrivate: boolean;
    technologies: string[];
  };

  let { portfolio }: { portfolio: Portfolio } = $props();
</script>

<AppHead title={portfolio.name}>
  <meta name="description" content={portfolio.shortDesc ?? portfolio.name} />
</AppHead>

<div id="project-detail">
  <HeaderPage
    backTo="/portofolio"
    title="Detail Proyek"
    subtitle="Berikut saya tampilkan detail proyek yang saya kerjakan ini."
  />

  <section class="need-space pt-0">
    <div class="container">
      <div class="row">
        <div class="col-md-12">
          <img src={portfolio.image} class="w-100 rounded-4 border overflow-hidden" alt={portfolio.name} />

          <div
            class="pt-5 pb-4 flex-column flex-lg-row border-bottom mb-5 align-items-start align-items-lg-center d-flex justify-content-between gap-3"
          >
            <div>
              <h1 class="h3">{portfolio.name}</h1>
              {#if portfolio.shortDesc}
                <p class="opacity-75 mb-0">{portfolio.shortDesc}</p>
              {/if}
            </div>
            <div class="d-flex flex-shrink-0 gap-3">
              {#if !portfolio.isPrivate && portfolio.sourceCode}
                <a
                  href={portfolio.sourceCode}
                  target="_blank"
                  rel="noopener"
                  class="d-flex gap-2 align-items-center btn btn-outline-primary"
                >
                  <Icon icon="fa6-brands:github" />
                  <span>Source Code</span>
                </a>
              {/if}
              {#if portfolio.demoLink}
                <a
                  href={portfolio.demoLink}
                  target="_blank"
                  rel="noopener"
                  class="d-flex gap-2 align-items-center btn btn-primary"
                >
                  <Link2 size={16} />
                  <span>Live Demo</span>
                </a>
              {/if}
            </div>
          </div>

          <div class="row flex-column-reverse flex-md-row gy-5">
            <div class="col-md-8">
              <div class="mb-5">
                <h3 id="description">Deskripsi Proyek</h3>
                {#if portfolio.description}
                  <!-- eslint-disable-next-line svelte/no-at-html-tags -->
                  {@html portfolio.description}
                {:else}
                  <p class="opacity-75">Belum ditambahkan deskripsi.</p>
                {/if}
              </div>

              <div class="mb-5">
                <h3 id="techstack">Dibangun Dengan</h3>

                <div class="d-flex gap-3 align-items-center flex-wrap">
                  {#each portfolio.technologies as tech (tech)}
                    <Icon icon={techIcon(tech)} width={32} height={32} />
                  {/each}
                </div>
              </div>

              <div class="mb-5" id="gallery">
                <h3>Galeri dan Pratinjau Proyek</h3>

                <div class="alert bg-info-subtle d-flex gap-3">
                  <Info size={32} class="flex-shrink-0" />
                  <span>
                    Mohon bersabar ya, karena fitur ini masih saya kembangkan untuk temen-temen yang
                    <em>mau ngeliat</em> pratinjau proyek ini.
                  </span>
                </div>
              </div>

              <div id="rating">
                <h3>Penilaian Proyek Ini</h3>

                <div class="alert bg-info-subtle mb-0 d-flex gap-3">
                  <Info size={32} class="flex-shrink-0" />
                  <span>
                    Mohon bersabar ya, karena fitur ini masih saya kembangkan untuk temen-temen yang
                    <em>mau ngasih</em> penilaian di proyek ini.
                  </span>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card sticky-top rounded-4">
                <div class="card-header p-4">
                  <h4 class="mb-0">Navigasi</h4>
                </div>
                <div class="card-body">
                  <ul class="nav nav-pills flex-column align-items-stretch">
                    <li class="nav-item">
                      <a href="#description" class="nav-link py-3 w-100 text-start">Deskripsi Proyek</a>
                    </li>
                    <li class="nav-item">
                      <a href="#techstack" class="nav-link py-3 w-100 text-start">Dibangun Dengan</a>
                    </li>
                    <li class="nav-item">
                      <a href="#gallery" class="nav-link py-3 w-100 text-start">Galeri</a>
                    </li>
                    <li class="nav-item">
                      <a href="#rating" class="nav-link py-3 w-100 text-start">Penilaian</a>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
