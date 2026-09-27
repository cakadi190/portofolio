<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import CardPortfolio from '@/components/card-portfolio.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import HeaderPage from '@/components/header-page.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import type { Paginated } from '@/types/pagination';

  type Portfolio = {
    name: string;
    slug: string;
    image: string;
    shortDesc: string | null;
    categories: { name: string; color: string | null }[];
    technologies: string[];
  };

  let { portfolios }: { portfolios: Paginated<Portfolio> } = $props();
</script>

<AppHead title="Portofolio">
  <meta
    name="description"
    content="Berikut daftar portofolio yang sudah saya kerjakan dan selesaikan akhir-akhir ini."
  />
</AppHead>

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
            <div class="row">
              {#each portfolios.data as portfolio (portfolio.slug)}
                <div class="col-md-6 col-lg-4 mb-4">
                  <CardPortfolio {...portfolio} />
                </div>
              {/each}
            </div>

            <SimplePaginator
              currentPage={portfolios.current_page}
              lastPage={portfolios.last_page}
              prevPageUrl={portfolios.prev_page_url}
              nextPageUrl={portfolios.next_page_url}
            />
          </div>
        {/if}
      </div>
    </div>
  </section>
</div>
