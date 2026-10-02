<script lang="ts">
  import ChevronLeft from '@lucide/svelte/icons/chevron-left';
  import ChevronRight from '@lucide/svelte/icons/chevron-right';
  import { Link } from '@inertiajs/svelte';

  let {
    currentPage,
    lastPage,
    prevPageUrl,
    nextPageUrl,
  }: {
    currentPage: number;
    lastPage: number;
    prevPageUrl: string | null;
    nextPageUrl: string | null;
  } = $props();
</script>

{#if lastPage > 1}
  <div class="d-flex justify-content-center align-items-center gap-3">
    {#if prevPageUrl}
      <Link href={prevPageUrl} class="btn btn-square btn-primary" aria-label="Halaman sebelumnya">
        <ChevronLeft size={16} aria-hidden="true" />
      </Link>
    {:else}
      <button class="btn btn-square btn-primary" type="button" disabled aria-label="Halaman sebelumnya">
        <ChevronLeft size={16} aria-hidden="true" />
      </button>
    {/if}

    <span>Halaman {currentPage} dari {lastPage}</span>

    {#if nextPageUrl}
      <Link href={nextPageUrl} class="btn btn-square btn-primary" aria-label="Halaman berikutnya">
        <ChevronRight size={16} aria-hidden="true" />
      </Link>
    {:else}
      <button class="btn btn-square btn-primary" type="button" disabled aria-label="Halaman berikutnya">
        <ChevronRight size={16} aria-hidden="true" />
      </button>
    {/if}
  </div>
{/if}
