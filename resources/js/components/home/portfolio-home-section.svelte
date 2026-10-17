<script lang="ts">
  import type { TechnologyChip } from '@/lib/tech-icon';
  import ArrowRight from '@lucide/svelte/icons/arrow-right';
  import Link from '@/components/link.svelte';
  import CardPortfolio from '@/components/card-portfolio.svelte';
  import { t } from '@/lib/i18n';

  type Portfolio = {
    name: string;
    slug: string;
    image: string;
    shortDesc: string | null;
    services: { name: string; color: string | null }[];
    technologies: TechnologyChip[];
  };

  let { portfolios = [] }: { portfolios?: Portfolio[] } = $props();
</script>

<section class="need-space" id="portfolio-home-section">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
      <div>
        <h2 class="mb-1">{t('home.portfolio.title')}</h2>
        <p class="mb-0 opacity-75">{t('home.portfolio.subtitle')}</p>
      </div>
      <Link href="/portfolio" class="btn btn-outline-primary d-flex align-items-center gap-2" aria-label={`${t('home.more')}: ${t('home.portfolio.title')}`}>
        <span>{t('home.more')}</span>
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
      <div class="text-center opacity-75">{t('home.portfolio.empty')}</div>
    {/if}
  </div>
</section>
