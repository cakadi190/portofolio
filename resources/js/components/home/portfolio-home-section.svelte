<script lang="ts">
  import ArrowRight from '@lucide/svelte/icons/arrow-right';
  import { Link } from '@inertiajs/svelte';
  import CardPortfolio from '@/components/card-portfolio.svelte';

  type Portfolio = {
    name: string;
    slug: string;
    image: string;
    shortDesc: string | null;
    services: { name: string; color: string | null }[];
    technologies: string[];
  };

  let { portfolios = [] }: { portfolios?: Portfolio[] } = $props();
</script>

<section class="need-space" id="portfolio-home-section">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
      <div>
        <h2 class="mb-1">Portofolio</h2>
        <p class="mb-0 opacity-75">Beberapa proyek yang sudah saya kerjakan akhir-akhir ini.</p>
      </div>
      <Link href="/portofolio" class="btn btn-outline-primary d-flex align-items-center gap-2">
        <span>Lihat Selengkapnya</span>
        <ArrowRight size={16} />
      </Link>
    </div>

    {#if portfolios.length}
      <div class="row">
        {#each portfolios as portfolio (portfolio.slug)}
          <div class="col-md-6 col-lg-4 mb-4">
            <CardPortfolio {...portfolio} />
          </div>
        {/each}
      </div>
    {:else}
      <div class="text-center opacity-75">Belum ada portofolio yang tersedia saat ini.</div>
    {/if}
  </div>
</section>
