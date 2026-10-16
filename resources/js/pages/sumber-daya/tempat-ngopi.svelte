<script lang="ts">
  import { localizeHref, t } from '@/lib/i18n';
  import Info from '@lucide/svelte/icons/info';
  import Search from '@lucide/svelte/icons/search';
  import { router } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import CardCoffee from '@/components/card-coffee.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import HeaderPage from '@/components/header-page.svelte';
  import InfiniteFeed from '@/components/infinite-feed.svelte';
  import type { InfiniteList } from '@/types/pagination';

  type CoffeePlace = {
    id: number;
    name: string;
    region: string | null;
    description: string | null;
    image: string | null;
    address: string;
    mapUrl: string | null;
    opensAt: string | null;
    closesAt: string | null;
    parkFee: number | null;
    isRecommended: boolean;
    latitude: string | number | null;
    longitude: string | number | null;
    wifiProvider: string | null;
    wifiSpeed: string | null;
    wifiSpeedColor: string | null;
    priceTier: string | null;
    priceTierColor: string | null;
    facilities: string[];
    galleries: { url: string; title: string | null }[];
  };

  let {
    places,
    regions,
    filters,
  }: {
    places: InfiniteList<CoffeePlace>;
    regions: string[];
    filters: { region: string | null; search: string | null };
  } = $props();

  // svelte-ignore state_referenced_locally
  let searchQuery = $state(filters.search ?? '');

  function selectRegion(region: string | null): void {
    router.get(
      localizeHref('/resources/coffee-shops'),
      { region, search: filters.search },
      { preserveState: true, preserveScroll: true },
    );
  }

  function handleSearch(event: SubmitEvent): void {
    event.preventDefault();

    router.get(
      localizeHref('/resources/coffee-shops'),
      { region: filters.region, search: searchQuery || undefined },
      { preserveState: true, preserveScroll: true },
    );
  }
</script>

<AppHead title={t('resources.coffee.title')} />

<div id="coffee-page">
  <HeaderPage
    title={t('resources.coffee.title')}
    subtitle={t('resources.coffee.subtitle')}
  >
    <p class="text-muted mt-3 align-items-center gap-2">
      <Info size={16} />
      <span>{t('resources.coffee.disclaimer')}</span>
    </p>
  </HeaderPage>

  <section class="need-space pt-0 position-relative">
    <div class="container">
      <div class="coffee-filter-bar">
        <div class="dropdown d-inline-block">
          <button
            class={`btn dropdown-toggle ${filters.region ? 'btn-primary' : 'btn-outline-primary'}`}
            type="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
          >
            {filters.region ?? t('resources.coffee.choose_city')}
          </button>
          <ul class="dropdown-menu">
            <li>
              <button
                class="dropdown-item"
                type="button"
                onclick={() => selectRegion(null)}
              >
                {t('resources.coffee.all_cities')}
              </button>
            </li>
            {#each regions as region (region)}
              <li>
                <button
                  class={`dropdown-item ${filters.region === region ? 'active' : ''}`}
                  type="button"
                  onclick={() => selectRegion(region)}
                >
                  {region}
                </button>
              </li>
            {/each}
          </ul>
        </div>

        <form class="input-group ms-auto" onsubmit={handleSearch}>
          <input
            bind:value={searchQuery}
            type="text"
            class="form-control"
            aria-label={t('resources.coffee.search_label')}
            placeholder={t('resources.coffee.search_label')}
          />
          <button class="btn btn-primary" type="submit" aria-label={t('resources.coffee.search')}>
            <Search size={16} />
          </button>
        </form>
      </div>

      <div class="row pt-5">
        {#if places.data.length === 0}
          <EmptyState
            title={t('resources.coffee.empty_title')}
            text={t('resources.coffee.empty_text')}
          />
        {:else}
          <div class="col-md-12 mx-auto">
            <InfiniteFeed data="places">
            <div class="row">
              {#each places.data as place, index (place.id)}
                <div class="col-md-6 col-lg-4 mb-4">
                  <CardCoffee {...place} eager={index === 0} />
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
