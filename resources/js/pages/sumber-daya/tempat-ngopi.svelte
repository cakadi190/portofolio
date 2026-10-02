<script lang="ts">
  import Info from '@lucide/svelte/icons/info';
  import Search from '@lucide/svelte/icons/search';
  import { router } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import CardCoffee from '@/components/card-coffee.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import HeaderPage from '@/components/header-page.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import type { Paginated } from '@/types/pagination';

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
  };

  let {
    places,
    regions,
    filters,
  }: {
    places: Paginated<CoffeePlace>;
    regions: string[];
    filters: { region: string | null; search: string | null };
  } = $props();

  let searchQuery = $state(filters.search ?? '');

  function selectRegion(region: string | null): void {
    router.get(
      '/sumber-daya/tempat-ngopi',
      { region, search: filters.search },
      { preserveState: true, preserveScroll: true },
    );
  }

  function handleSearch(event: SubmitEvent): void {
    event.preventDefault();

    router.get(
      '/sumber-daya/tempat-ngopi',
      { region: filters.region, search: searchQuery || undefined },
      { preserveState: true, preserveScroll: true },
    );
  }
</script>

<AppHead title="Tempat Ngopi">
  <meta
    name="description"
    content="Berikut daftar tempat ngopi yang saya rekomendasikan."
  />
</AppHead>

<div id="coffee-page">
  <HeaderPage
    title="Tempat Ngopi"
    subtitle="Berikut daftar tempat ngopi yang aku rekomendasikan."
  >
    <p class="text-muted mt-3 align-items-center gap-2">
      <Info size={16} />
      <span>
        Dan mohon maaf, saya tidak terafiliasi terhadap salah satu kafe / warkop
        ini, jadi apabila ada kesalahan mohon segera hubungi saya supaya segera
        saya perbaharui.
      </span>
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
            {filters.region ?? 'Pilih Kota'}
          </button>
          <ul class="dropdown-menu">
            <li>
              <button
                class="dropdown-item"
                type="button"
                onclick={() => selectRegion(null)}
              >
                Semua Kota
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
            aria-label="Cari kafe atau lokasi"
            placeholder="Cari kafe atau lokasi"
          />
          <button class="btn btn-primary" type="submit" aria-label="Cari">
            <Search size={16} />
          </button>
        </form>
      </div>

      <div class="row pt-5">
        {#if places.data.length === 0}
          <EmptyState
            title="Tempat Ngopi Tidak Ditemukan"
            text="Kami tidak dapat menemukan tempat ngopi dengan kriteria tersebut."
          />
        {:else}
          <div class="col-md-12 mx-auto">
            <div class="row">
              {#each places.data as place (place.id)}
                <div class="col-md-6 col-lg-4 mb-4">
                  <CardCoffee {...place} />
                </div>
              {/each}
            </div>

            <SimplePaginator
              currentPage={places.current_page}
              lastPage={places.last_page}
              prevPageUrl={places.prev_page_url}
              nextPageUrl={places.next_page_url}
            />
          </div>
        {/if}
      </div>
    </div>
  </section>
</div>
