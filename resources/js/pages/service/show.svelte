<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import CardPortfolio from '@/components/card-portfolio.svelte';
  import HeaderPage from '@/components/header-page.svelte';
  import { highlightCode } from '@/lib/highlight/action';

  type Portfolio = {
    name: string;
    slug: string;
    image: string;
    shortDesc: string | null;
    services: { name: string; color: string | null }[];
    technologies: string[];
  };

  type Service = {
    name: string;
    slug: string;
    color: string | null;
    image: string | null;
    description: string | null;
  };

  let { service, portfolios }: { service: Service; portfolios: Portfolio[] } = $props();
</script>

<AppHead title={service.name} />

<div id="service-detail">
  <HeaderPage
    backTo="/layanan"
    title={service.name}
    subtitle="Detail layanan yang bisa saya berikan untuk anda."
  />

  <section class="need-space pt-0">
    <div class="container">
      {#if service.image}
        <img
          src={service.image}
          class="w-100 rounded-4 border overflow-hidden mb-5"
          alt={service.name}
        />
      {/if}

      <div class="mb-5">
        {#if service.description}
          <div class="wysiwyg-content-wrapper" use:highlightCode={service.description}>
            <!-- eslint-disable-next-line svelte/no-at-html-tags -->
            {@html service.description}
          </div>
        {:else}
          <p class="opacity-75">Belum ditambahkan deskripsi.</p>
        {/if}
      </div>

      <div id="portfolios">
        <h3 class="mb-4">Portofolio {service.name}</h3>

        {#if portfolios.length > 0}
          <div class="row">
            {#each portfolios as portfolio (portfolio.slug)}
              <div class="col-md-6 col-lg-4 mb-4">
                <CardPortfolio {...portfolio} />
              </div>
            {/each}
          </div>
        {:else}
          <p class="opacity-75">Belum ada portofolio untuk layanan ini.</p>
        {/if}
      </div>
    </div>
  </section>
</div>
