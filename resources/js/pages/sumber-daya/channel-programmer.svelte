<script lang="ts">
  import ExternalLink from '@lucide/svelte/icons/external-link';
  import Users from '@lucide/svelte/icons/users';
  import { router } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import HeaderPage from '@/components/header-page.svelte';
  import InfiniteFeed from '@/components/infinite-feed.svelte';
  import type { InfiniteList } from '@/types/pagination';

  type Item = {
    id: number;
    name: string;
    platform: string;
    platformLabel: string;
    description: string | null;
    url: string;
    isFeatured: boolean;
  };

  let {
    channels,
    platforms,
    filters,
  }: {
    channels: InfiniteList<Item>;
    platforms: { value: string; label: string }[];
    filters: { platform: string | null };
  } = $props();

  function selectPlatform(platform: string | null): void {
    router.get(
      '/resources/programmer-channels',
      { platform },
      { preserveState: true, preserveScroll: true },
    );
  }
</script>

<AppHead title="Channel Programmer" />

<div id="channel-programmer-page">
  <HeaderPage
    title="Channel Programmer"
    subtitle="Channel YouTube, Telegram, dan akun lain seputar pemrograman yang layak kamu ikuti."
  />

  <section class="need-space pt-0 position-relative">
    <div class="container">
      <div class="d-flex flex-wrap gap-2 mb-5">
        <button
          type="button"
          class={`btn btn-sm ${filters.platform ? 'btn-outline-primary' : 'btn-primary'}`}
          onclick={() => selectPlatform(null)}
        >
          Semua Platform
        </button>
        {#each platforms as platform (platform.value)}
          <button
            type="button"
            class={`btn btn-sm ${filters.platform === platform.value ? 'btn-primary' : 'btn-outline-primary'}`}
            onclick={() => selectPlatform(platform.value)}
          >
            {platform.label}
          </button>
        {/each}
      </div>

      {#if channels.data.length === 0}
        <EmptyState
          title="Channel Belum Tersedia"
          text="Belum ada channel dengan kriteria tersebut."
        />
      {:else}
        <InfiniteFeed data="channels">
          <div class="row">
            {#each channels.data as item (item.id)}
              <div class="col-md-6 col-lg-4 mb-4">
                <div class="card rounded-4 h-100">
                  <div class="card-body d-flex flex-column gap-2">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                      <span class="badge text-bg-secondary"
                        >{item.platformLabel}</span
                      >
                      {#if item.isFeatured}
                        <span class="badge text-bg-warning">★ Unggulan</span>
                      {/if}
                    </div>
                    <h2 class="h5 mb-0">{item.name}</h2>
                    {#if item.description}
                      <p class="opacity-75 mb-0">{item.description}</p>
                    {/if}
                    <div class="mt-auto pt-2">
                      <a
                        href={item.url}
                        class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2"
                        target="_blank"
                        rel="noopener noreferrer nofollow"
                      >
                        Kunjungi
                        <ExternalLink size={14} aria-hidden="true" />
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            {/each}
          </div>
        </InfiniteFeed>
      {/if}
    </div>
  </section>
</div>
