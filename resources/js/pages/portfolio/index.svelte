<script lang="ts">
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

<AppHead title="Portofolio" />

<div id="porto-page">
  <HeaderPage
    title="Portofolio"
    subtitle="Daftar semua proyek dan portofolio saya yang sudah diselesaikan maupun belum."
  />

  <section class="need-space pt-0">
    <div class="container">
      <div class="row">
        {#if portfolios.data.length === 0}
          <EmptyState title="Belum Ada Portofolio" text="Belum ada portofolio yang tersedia saat ini." />
        {:else}
          <div class="col-md-12 mx-auto">
            <InfiniteFeed data="portfolios">
            <div class="row">
              {#each portfolios.data as portfolio (portfolio.slug)}
                <div class="col-md-6 col-lg-4 mb-4">
                  <CardPortfolio {...portfolio} />
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
