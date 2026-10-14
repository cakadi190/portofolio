<script lang="ts">
  import Select from '@/components/ui/select.svelte';
  import AppHead from '@/components/app-head.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import ChevronDown from '@lucide/svelte/icons/chevron-down';
  import SlidersHorizontal from '@lucide/svelte/icons/sliders-horizontal';
  import { Link, router } from '@inertiajs/svelte';
  import { index, show, exportMethod } from '@/wayfinder/routes/admin/activity-logs';
  import InfiniteFeed from '@/components/infinite-feed.svelte';
  import type { InfiniteList } from '@/types/pagination';

  type ActivityRow = {
    id: number;
    created_at: string | null;
    log_name: string | null;
    event: string | null;
    description: string;
    outcome: string;
    actor_type: string;
    causer: { id: number; name: string; email: string } | null;
    subject_type: string | null;
    subject_id: number | null;
    route_name: string | null;
    ip_address: string | null;
  };

  type Filters = Record<string, string | number | undefined> & {
    sort: string;
    direction: 'asc' | 'desc';
    per_page: number;
  };

  let {
    activities,
    totalActivities,
    filters,
    options,
  }: {
    activities: InfiniteList<ActivityRow>;
    totalActivities: number;
    filters: Filters;
    options: {
      log_names: string[];
      events: string[];
      subject_types: string[];
      users: { id: number; name: string }[];
    };
  } = $props();

  // svelte-ignore state_referenced_locally
  let form = $state<Record<string, string>>({
    search: String(filters.search ?? ''),
    log_name: String(filters.log_name ?? ''),
    event: String(filters.event ?? ''),
    outcome: String(filters.outcome ?? ''),
    causer_id: String(filters.causer_id ?? ''),
    subject_type: String(filters.subject_type ?? ''),
    subject_id: String(filters.subject_id ?? ''),
    ip_address: String(filters.ip_address ?? ''),
    from: String(filters.from ?? ''),
    to: String(filters.to ?? ''),
  });

  function query(extra: Record<string, string | number> = {}): Record<string, string | number> {
    const result: Record<string, string | number> = {};
    for (const [key, value] of Object.entries(form)) {
      if (value !== '') result[key] = value;
    }
    if (filters.sort !== 'created_at' || filters.direction !== 'desc') {
      result.sort = filters.sort;
      result.direction = filters.direction;
    }
    if (filters.per_page !== 25) result.per_page = filters.per_page;
    return { ...result, ...extra };
  }

  let filtersOpen = $state(false);

  const activeFilterCount = $derived(
    Object.entries(form).filter(([key, value]) => key !== 'search' && value !== '').length,
  );

  function apply(event: Event): void {
    event.preventDefault();
    router.get(index().url, query(), { preserveState: true, replace: true });
  }

  function reset(): void {
    for (const key of Object.keys(form)) form[key] = '';
    router.get(index().url, {}, { preserveState: true, replace: true });
  }

  function sortBy(column: string): void {
    const direction = filters.sort === column && filters.direction === 'desc' ? 'asc' : 'desc';
    router.get(index().url, query({ sort: column, direction }), { preserveState: true, replace: true });
  }

  const exportUrl = $derived(
    exportMethod().url + '?' + new URLSearchParams(Object.entries(query()).map(([k, v]) => [k, String(v)])).toString(),
  );

  function formatTime(value: string | null): string {
    return value ? new Date(value).toLocaleString('id-ID') : '—';
  }
</script>

<AppHead title="Log Aktivitas" />

<AdminPageHeader title="Log Aktivitas" subtitle="Jejak audit tindakan penting pada situs. Hanya admin yang dapat melihat." />

<form class="activity-filter" onsubmit={apply}>
  <div class="activity-filter-bar">
    <input class="form-control" type="search" placeholder="Cari deskripsi, event, atau ID request" bind:value={form.search} aria-label="Cari" />
    <button
      class="btn btn-outline-secondary activity-filter-toggle"
      type="button"
      aria-expanded={filtersOpen}
      aria-controls="activity-filter-panel"
      onclick={() => (filtersOpen = !filtersOpen)}
    >
      <SlidersHorizontal size={16} aria-hidden="true" />
      <span class="d-none d-sm-inline">Filter</span>
      {#if activeFilterCount > 0}
        <span class="badge text-bg-primary">{activeFilterCount}</span>
      {/if}
      <ChevronDown size={16} aria-hidden="true" class="activity-filter-chevron" />
    </button>
    <button class="btn btn-primary" type="submit">Terapkan</button>
  </div>

  {#if filtersOpen}
  <div class="activity-filter-panel" id="activity-filter-panel">
  <div class="row g-2">
    <div class="col-md-2">
      <Select
        ariaLabel="Kategori"
        placeholder="Semua kategori"
        items={options.log_names.map((name) => ({ value: name, label: name }))}
        value={form.log_name || null}
        onchange={(next) => (form.log_name = next === null ? '' : String(next))}
      />
    </div>
    <div class="col-md-2">
      <Select
        ariaLabel="Event"
        placeholder="Semua event"
        items={options.events.map((name) => ({ value: name, label: name }))}
        value={form.event || null}
        onchange={(next) => (form.event = next === null ? '' : String(next))}
      />
    </div>
    <div class="col-md-2">
      <Select
        ariaLabel="Hasil"
        placeholder="Semua hasil"
        items={[{ value: 'success', label: 'Berhasil' }, { value: 'failure', label: 'Gagal' }]}
        value={form.outcome || null}
        onchange={(next) => (form.outcome = next === null ? '' : String(next))}
      />
    </div>
    <div class="col-md-2">
      <Select
        ariaLabel="Pengguna"
        placeholder="Semua pengguna"
        items={options.users.map((user) => ({ value: user.id, label: user.name }))}
        value={form.causer_id || null}
        onchange={(next) => (form.causer_id = next === null ? '' : String(next))}
      />
    </div>
    <div class="col-md-3">
      <Select
        ariaLabel="Tipe objek"
        placeholder="Semua objek"
        items={options.subject_types.map((type) => ({ value: type, label: type.split('\\').pop() ?? type }))}
        value={form.subject_type || null}
        onchange={(next) => (form.subject_type = next === null ? '' : String(next))}
      />
    </div>
    <div class="col-md-2">
      <input class="form-control" type="number" min="1" placeholder="ID objek" bind:value={form.subject_id} aria-label="ID objek" />
    </div>
    <div class="col-md-3">
      <input class="form-control" type="text" placeholder="Alamat IP" bind:value={form.ip_address} aria-label="Alamat IP" />
    </div>
    <div class="col-md-2">
      <input class="form-control" type="datetime-local" bind:value={form.from} aria-label="Dari" />
    </div>
    <div class="col-md-2">
      <input class="form-control" type="datetime-local" bind:value={form.to} aria-label="Sampai" />
    </div>
  </div>
    <div class="d-flex flex-wrap gap-2 mt-3">
      <button class="btn btn-outline-secondary" type="button" onclick={reset}>Atur ulang</button>
      <a class="btn btn-outline-secondary ms-auto" href={exportUrl}>Ekspor CSV (maks. 5.000)</a>
    </div>
  </div>
  {/if}
</form>

{#if activities.data.length === 0}
  <EmptyState title="Tidak ada aktivitas" text="Tidak ada catatan yang cocok dengan filter." />
{:else}
  <InfiniteFeed data="activities">
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <th><button class="btn btn-link p-0" onclick={() => sortBy('created_at')}>Waktu</button></th>
          <th>Aktor</th>
          <th><button class="btn btn-link p-0" onclick={() => sortBy('event')}>Event</button></th>
          <th>Objek</th>
          <th>Deskripsi</th>
          <th><button class="btn btn-link p-0" onclick={() => sortBy('log_name')}>Kategori</button></th>
          <th>IP</th>
          <th>Hasil</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        {#each activities.data as activity (activity.id)}
          <tr>
            <td class="text-nowrap">{formatTime(activity.created_at)}</td>
            <td>{activity.causer?.name ?? (activity.actor_type === 'guest' ? 'Tamu' : 'Sistem')}</td>
            <td><code>{activity.event ?? '—'}</code></td>
            <td>{activity.subject_type ? `${activity.subject_type} #${activity.subject_id}` : '—'}</td>
            <td>{activity.description}</td>
            <td><span class="badge text-bg-secondary">{activity.log_name}</span></td>
            <td>{activity.ip_address ?? '—'}</td>
            <td>
              <span class="badge {activity.outcome === 'failure' ? 'text-bg-danger' : 'text-bg-success'}">
                {activity.outcome === 'failure' ? 'Gagal' : 'Berhasil'}
              </span>
            </td>
            <td class="text-end"><Link class="btn btn-sm btn-outline-secondary" href={show(activity.id).url}>Detail</Link></td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>
  </InfiniteFeed>

  <small class="text-muted">{activities.data.length} dari {totalActivities}</small>
{/if}

<style>
  .activity-filter {
    position: sticky;
    top: 68px;
    z-index: 5;
    margin: 0 0 1rem;
    padding: 0.5rem 0;
    background: var(--neo-surface-raised);
  }
  .activity-filter-bar {
    display: flex;
    gap: 0.5rem;
  }
  .activity-filter-bar .form-control {
    flex: 1;
    min-width: 0;
  }
  .activity-filter-toggle {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    white-space: nowrap;
  }
  .activity-filter-toggle[aria-expanded='true'] :global(.activity-filter-chevron) {
    transform: rotate(180deg);
  }
  .activity-filter-panel {
    max-height: calc(100svh - 12rem);
    overflow-y: auto;
    margin-top: 0.5rem;
    padding: 0.75rem;
    border: 1px solid var(--neo-border-color);
    border-radius: var(--neo-border-radius-lg);
    background: var(--neo-surface-inset);
  }
</style>
