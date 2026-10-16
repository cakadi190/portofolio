<script lang="ts">
  import { t } from '@/lib/i18n';
  import AppHead from '@/components/app-head.svelte';
  import CardPortfolio from '@/components/card-portfolio.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import HeaderPage from '@/components/header-page.svelte';
  import InfiniteFeed from '@/components/infinite-feed.svelte';
  import type { InfiniteList } from '@/types/pagination';

  type Portfolio = {
    name: string;
    slug: string;
    image: string;
    shortDesc: string | null;
    services: { name: string; color: string | null }[];
    technologies: string[];
  };

  let { portfolios }: { portfolios: InfiniteList<Portfolio> } = $props();
</script>

<AppHead title={t('portfolios.title')} />

<div id="porto-page">
  <HeaderPage
    title={t('portfolios.title')}
    subtitle={t('portfolios.subtitle')}
  />

  <section class="need-space pt-0">
    <div class="container">
      <div class="row">
        {#if portfolios.data.length === 0}
          <EmptyState title={t('portfolios.empty_title')} text={t('portfolios.empty_text')} />
        {:else}
          <div class="col-md-12 mx-auto">
            <InfiniteFeed data="portfolios">
            <div class="row">
              {#each portfolios.data as portfolio, index (portfolio.slug)}
                <div class="col-md-6 col-lg-4 mb-4">
                  <CardPortfolio {...portfolio} eager={index === 0} />
                </div>
              {/each}
            </div>
            </InfiniteFeed>
          </div>
        {/if}
      </div>
    </div>
  </section>
</div>
