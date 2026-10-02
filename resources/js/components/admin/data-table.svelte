<script lang="ts" generics="T extends { id: number }">
  import ArrowDown from '@lucide/svelte/icons/arrow-down';
  import ArrowUp from '@lucide/svelte/icons/arrow-up';
  import ArrowUpDown from '@lucide/svelte/icons/arrow-up-down';
  import ChevronLeft from '@lucide/svelte/icons/chevron-left';
  import ChevronRight from '@lucide/svelte/icons/chevron-right';
  import Search from '@lucide/svelte/icons/search';
  import X from '@lucide/svelte/icons/x';
  import { router } from '@inertiajs/svelte';
  import type { Snippet } from 'svelte';
  import type {
    Paginated,
    TableColumn,
    TableFilters,
  } from '@/types/pagination';

  let {
    data,
    filters,
    url,
    columns = [],
    variant = 'table',
    emptyTitle = 'Belum ada data',
    emptyText = 'Data akan muncul di sini.',
    row,
  }: {
    data: Paginated<T>;
    filters: TableFilters;
    /** Index route URL the table reloads against. */
    url: string;
    columns?: TableColumn[];
    variant?: 'table' | 'grid';
    emptyTitle?: string;
    emptyText?: string;
    row: Snippet<[T]>;
  } = $props();

  const perPageOptions = [10, 25, 50, 100];

  // svelte-ignore state_referenced_locally
  let search = $state(filters.search);
  let timer: ReturnType<typeof setTimeout> | undefined;

  const isFiltered = $derived(filters.search !== '');
  const pages = $derived(pageWindow(data.current_page, data.last_page));

  function visit(params: Partial<TableFilters> & { page?: number }): void {
    const next = { ...filters, page: 1, ...params };
    const query: Record<string, string | number> = {};

    if (next.search) query.search = next.search;
    if (next.sort) {
      query.sort = next.sort;
      query.direction = next.direction;
    }
    if (next.per_page !== 10) query.per_page = next.per_page;
    if (next.page > 1) query.page = next.page;

    router.get(url, query, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    });
  }

  function onSearch(): void {
    clearTimeout(timer);
    timer = setTimeout(() => visit({ search: search.trim() }), 350);
  }

  function clearSearch(): void {
    clearTimeout(timer);
    search = '';
    visit({ search: '' });
  }

  /** Cycles a column: ascending, then descending, then sorting off. */
  function toggleSort(column: TableColumn): void {
    if (!column.sortable || !column.key) return;

    if (filters.sort !== column.key) {
      visit({ sort: column.key, direction: 'asc' });
    } else if (filters.direction === 'asc') {
      visit({ sort: column.key, direction: 'desc' });
    } else {
      visit({ sort: null, direction: 'asc' });
    }
  }

  function pageWindow(current: number, last: number): (number | null)[] {
    const result: (number | null)[] = [];
    let previous = 0;

    for (let page = 1; page <= last; page++) {
      if (page === 1 || page === last || Math.abs(page - current) <= 1) {
        if (page - previous > 1) result.push(null);
        result.push(page);
        previous = page;
      }
    }

    return result;
  }
</script>

<div
  class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3"
>
  <div class="d-flex align-items-center gap-2 small text-muted">
    <label for="table-per-page">Tampilkan</label>
    <select
      id="table-per-page"
      class="form-select form-select-sm w-auto"
      value={filters.per_page}
      onchange={(event) =>
        visit({ per_page: Number(event.currentTarget.value) })}
    >
      {#each perPageOptions as option (option)}
        <option value={option}>{option}</option>
      {/each}
    </select>
    <span>data</span>
  </div>

  <div class="input-group input-group-sm" style="max-width: 18rem;">
    <span class="input-group-text"><Search size={14} /></span>
    <input
      type="search"
      class="form-control"
      placeholder="Cari..."
      aria-label="Cari"
      bind:value={search}
      oninput={onSearch}
    />
    {#if search}
      <button
        type="button"
        class="btn btn-outline-secondary"
        aria-label="Hapus pencarian"
        onclick={clearSearch}
      >
        <X size={14} />
      </button>
    {/if}
  </div>
</div>

{#if variant === 'grid'}
  {#if data.data.length === 0}
    <div class="text-center py-5">
      <h5>{isFiltered ? 'Tidak ada hasil' : emptyTitle}</h5>
      <p class="text-muted mb-0">
        {isFiltered
          ? `Tidak ada data yang cocok dengan "${filters.search}".`
          : emptyText}
      </p>
    </div>
  {:else}
    <div class="row g-3">
      {#each data.data as item (item.id)}
        {@render row(item)}
      {/each}
    </div>
  {/if}
{:else}
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          {#each columns as column (column.label)}
            {@const active = column.sortable && filters.sort === column.key}
            <th
              class:text-end={column.align === 'end'}
              aria-sort={active
                ? filters.direction === 'asc'
                  ? 'ascending'
                  : 'descending'
                : undefined}
            >
              {#if column.sortable}
                <button
                  type="button"
                  class="btn btn-link p-0 text-reset text-decoration-none fw-bold d-inline-flex align-items-center gap-1"
                  onclick={() => toggleSort(column)}
                >
                  {column.label}
                  {#if active && filters.direction === 'asc'}
                    <ArrowUp size={14} />
                  {:else if active}
                    <ArrowDown size={14} />
                  {:else}
                    <ArrowUpDown size={14} class="opacity-50" />
                  {/if}
                </button>
              {:else}
                {column.label}
              {/if}
            </th>
          {/each}
        </tr>
      </thead>
      <tbody>
        {#each data.data as item (item.id)}
          {@render row(item)}
        {:else}
          <tr>
            <td colspan={columns.length} class="text-center text-muted py-5">
              {isFiltered
                ? `Tidak ada data yang cocok dengan "${filters.search}".`
                : emptyText}
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>
{/if}

<div
  class="d-flex flex-wrap align-items-center justify-content-between gap-3"
>
  <span class="small text-muted">
    {#if data.total > 0}
      Menampilkan {data.from} sampai {data.to} dari {data.total} data
    {:else}
      Tidak ada data
    {/if}
  </span>

  {#if data.last_page > 1}
    <nav aria-label="Paginasi tabel">
      <ul class="pagination pagination-sm mb-0">
        <li class="page-item" class:disabled={data.current_page === 1}>
          <button
            type="button"
            class="page-link"
            aria-label="Sebelumnya"
            disabled={data.current_page === 1}
            onclick={() => visit({ page: data.current_page - 1 })}
          >
            <ChevronLeft size={14} />
          </button>
        </li>
        {#each pages as page, index (page ?? `gap-${index}`)}
          {#if page === null}
            <li class="page-item disabled">
              <span class="page-link">&hellip;</span>
            </li>
          {:else}
            <li class="page-item" class:active={page === data.current_page}>
              <button
                type="button"
                class="page-link"
                onclick={() => visit({ page })}>{page}</button
              >
            </li>
          {/if}
        {/each}
        <li
          class="page-item"
          class:disabled={data.current_page === data.last_page}
        >
          <button
            type="button"
            class="page-link"
            aria-label="Berikutnya"
            disabled={data.current_page === data.last_page}
            onclick={() => visit({ page: data.current_page + 1 })}
          >
            <ChevronRight size={14} />
          </button>
        </li>
      </ul>
    </nav>
  {/if}
</div>
