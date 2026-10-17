<script lang="ts">
  import { t } from '@/lib/i18n';
  import ChevronLeft from '@lucide/svelte/icons/chevron-left';
  import ChevronRight from '@lucide/svelte/icons/chevron-right';

  /**
   * Traditional numbered pagination (Previous / pages / Next) for server-side
   * paginated lists. Emits `onNavigate(page)`; the caller decides how to fetch.
   */
  let {
    currentPage,
    lastPage,
    total,
    from = null,
    to = null,
    disabled = false,
    onNavigate,
  }: {
    currentPage: number;
    lastPage: number;
    total?: number;
    from?: number | null;
    to?: number | null;
    disabled?: boolean;
    onNavigate: (page: number) => void;
  } = $props();

  /** Page numbers around the current one, with `null` marking a gap. */
  const pages = $derived.by((): (number | null)[] => {
    const wanted = new Set<number>([1, lastPage, currentPage - 1, currentPage, currentPage + 1]);
    const sorted = [...wanted].filter((page) => page >= 1 && page <= lastPage).sort((a, b) => a - b);
    const result: (number | null)[] = [];

    sorted.forEach((page, index) => {
      if (index > 0 && page - sorted[index - 1] > 1) {
        result.push(null);
      }

      result.push(page);
    });

    return result;
  });

  function go(page: number): void {
    if (!disabled && page >= 1 && page <= lastPage && page !== currentPage) {
      onNavigate(page);
    }
  }
</script>

{#if lastPage > 1 || (total ?? 0) > 0}
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
    <span class="small text-muted" aria-live="polite">
      {#if total !== undefined && from && to}
        {t('pager.summary', { from, to, total })} ·
      {/if}
      {t('pager.page', { current: currentPage, last: lastPage })}
    </span>

    {#if lastPage > 1}
      <nav aria-label={t('pager.nav')}>
        <ul class="pagination pagination-sm mb-0">
          <li class="page-item" class:disabled={disabled || currentPage <= 1}>
            <button
              type="button"
              class="page-link"
              aria-label={t('pager.prev')}
              disabled={disabled || currentPage <= 1}
              onclick={() => go(currentPage - 1)}
            >
              <ChevronLeft size={14} aria-hidden="true" />
            </button>
          </li>

          {#each pages as page}
            {#if page === null}
              <li class="page-item disabled" aria-hidden="true">
                <span class="page-link">…</span>
              </li>
            {:else}
              <li class="page-item" class:active={page === currentPage}>
                <button
                  type="button"
                  class="page-link"
                  aria-label={t('pager.go_to', { page })}
                  aria-current={page === currentPage ? 'page' : undefined}
                  {disabled}
                  onclick={() => go(page)}
                >
                  {page}
                </button>
              </li>
            {/if}
          {/each}

          <li class="page-item" class:disabled={disabled || currentPage >= lastPage}>
            <button
              type="button"
              class="page-link"
              aria-label={t('pager.next')}
              disabled={disabled || currentPage >= lastPage}
              onclick={() => go(currentPage + 1)}
            >
              <ChevronRight size={14} aria-hidden="true" />
            </button>
          </li>
        </ul>
      </nav>
    {/if}
  </div>
{/if}
