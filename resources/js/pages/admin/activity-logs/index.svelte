<script lang="ts">
  import { t, intlLocale } from '@/lib/i18n';
  import Select from '@/components/ui/select.svelte';
  import AppHead from '@/components/app-head.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import ChevronDown from '@lucide/svelte/icons/chevron-down';
  import SlidersHorizontal from '@lucide/svelte/icons/sliders-horizontal';
  import { Link, router } from '@inertiajs/svelte';
  import { index, show, exportMethod } from '@/wayfinder/routes/admin/activity-logs';
  import PaginationNav from '@/components/pagination-nav.svelte';
  import type { Paginated } from '@/types/pagination';

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
    activities: Paginated<ActivityRow>;
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
    return value ? new Date(value).toLocaleString(intlLocale()) : '—';
  }
</script>

<AppHead title={t('admin-activity-logs.log_aktivitas')} />

<AdminPageHeader title={t('admin-activity-logs.log_aktivitas')} subtitle={t('admin-activity-logs.jejak_audit_tindakan_penting_pada_situs')} />

<form class="activity-filter" onsubmit={apply}>
  <div class="activity-filter-bar">
    <input class="form-control" type="search" placeholder={t('admin-activity-logs.cari_deskripsi_event_atau_id_request')} bind:value={form.search} aria-label={t('admin-activity-logs.cari')} />
    <button
      class="btn btn-outline-secondary activity-filter-toggle"
      type="button"
      aria-expanded={filtersOpen}
      aria-controls="activity-filter-panel"
      onclick={() => (filtersOpen = !filtersOpen)}
    >
      <SlidersHorizontal size={16} aria-hidden="true" />
      <span class="d-none d-sm-inline">{t('admin-activity-logs.filter')}</span>
      {#if activeFilterCount > 0}
        <span class="badge text-bg-primary">{activeFilterCount}</span>
      {/if}
      <ChevronDown size={16} aria-hidden="true" class="activity-filter-chevron" />
    </button>
    <button class="btn btn-primary" type="submit">{t('admin-activity-logs.terapkan')}</button>
  </div>

  {#if filtersOpen}
  <div class="activity-filter-panel" id="activity-filter-panel">
  <div class="row g-2">
    <div class="col-md-2">
      <Select
        ariaLabel={t('admin.kategori')}
        placeholder={t('admin-activity-logs.semua_kategori')}
        items={options.log_names.map((name) => ({ value: name, label: name }))}
        value={form.log_name || null}
        onchange={(next) => (form.log_name = next === null ? '' : String(next))}
      />
    </div>
    <div class="col-md-2">
      <Select
        ariaLabel={t('admin-activity-logs.event')}
        placeholder={t('admin-activity-logs.semua_event')}
        items={options.events.map((name) => ({ value: name, label: name }))}
        value={form.event || null}
        onchange={(next) => (form.event = next === null ? '' : String(next))}
      />
    </div>
    <div class="col-md-2">
      <Select
        ariaLabel={t('admin-activity-logs.hasil')}
        placeholder={t('admin-activity-logs.semua_hasil')}
        items={[{ value: 'success', label: t('admin-activity-logs.berhasil') }, { value: 'failure', label: t('admin-activity-logs.gagal') }]}
        value={form.outcome || null}
        onchange={(next) => (form.outcome = next === null ? '' : String(next))}
      />
    </div>
    <div class="col-md-2">
      <Select
        ariaLabel={t('admin.pengguna')}
        placeholder={t('admin-activity-logs.semua_pengguna')}
        items={options.users.map((user) => ({ value: user.id, label: user.name }))}
        value={form.causer_id || null}
        onchange={(next) => (form.causer_id = next === null ? '' : String(next))}
      />
    </div>
    <div class="col-md-3">
      <Select
        ariaLabel={t('admin-activity-logs.tipe_objek')}
        placeholder={t('admin-activity-logs.semua_objek')}
        items={options.subject_types.map((type) => ({ value: type, label: type.split('\\').pop() ?? type }))}
        value={form.subject_type || null}
        onchange={(next) => (form.subject_type = next === null ? '' : String(next))}
      />
    </div>
    <div class="col-md-2">
      <input class="form-control" type="number" min="1" placeholder={t('admin-activity-logs.id_objek')} bind:value={form.subject_id} aria-label={t('admin-activity-logs.id_objek')} />
    </div>
    <div class="col-md-3">
      <input class="form-control" type="text" placeholder={t('admin-activity-logs.alamat_ip')} bind:value={form.ip_address} aria-label={t('admin-activity-logs.alamat_ip')} />
    </div>
    <div class="col-md-2">
      <input class="form-control" type="datetime-local" bind:value={form.from} aria-label={t('admin-activity-logs.dari')} />
    </div>
    <div class="col-md-2">
      <input class="form-control" type="datetime-local" bind:value={form.to} aria-label={t('admin-activity-logs.sampai')} />
    </div>
  </div>
    <div class="d-flex flex-wrap gap-2 mt-3">
      <button class="btn btn-outline-secondary" type="button" onclick={reset}>{t('admin-activity-logs.atur_ulang')}</button>
      <a class="btn btn-outline-secondary ms-auto" href={exportUrl}>{t('admin-activity-logs.ekspor_csv_maks_5_000')}</a>
    </div>
  </div>
  {/if}
</form>

{#if activities.data.length === 0}
  <EmptyState title={t('admin-activity-logs.tidak_ada_aktivitas')} text={t('admin-activity-logs.tidak_ada_catatan_yang_cocok_dengan')} />
{:else}
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <th><button class="btn btn-link p-0" onclick={() => sortBy('created_at')}>{t('admin-activity-logs.waktu')}</button></th>
          <th>{t('admin-activity-logs.aktor')}</th>
          <th><button class="btn btn-link p-0" onclick={() => sortBy('event')}>{t('admin-activity-logs.event')}</button></th>
          <th>{t('admin-activity-logs.objek')}</th>
          <th>{t('admin.deskripsi')}</th>
          <th><button class="btn btn-link p-0" onclick={() => sortBy('log_name')}>{t('admin.kategori')}</button></th>
          <th>IP</th>
          <th>{t('admin-activity-logs.hasil')}</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        {#each activities.data as activity (activity.id)}
          <tr>
            <td class="text-nowrap">{formatTime(activity.created_at)}</td>
            <td>{activity.causer?.name ?? (activity.actor_type === 'guest' ? t('admin-activity-logs.tamu') : t('dashboard.sistem'))}</td>
            <td><code>{activity.event ?? '—'}</code></td>
            <td>{activity.subject_type ? `${activity.subject_type} #${activity.subject_id}` : '—'}</td>
            <td>{activity.description}</td>
            <td><span class="badge text-bg-secondary">{activity.log_name}</span></td>
            <td>{activity.ip_address ?? '—'}</td>
            <td>
              <span class="badge {activity.outcome === 'failure' ? 'text-bg-danger' : 'text-bg-success'}">
                {activity.outcome === 'failure' ? t('admin-activity-logs.gagal') : t('admin-activity-logs.berhasil')}
              </span>
            </td>
            <td class="text-end"><Link class="btn btn-sm btn-outline-secondary" href={show(activity.id).url}>{t('admin-activity-logs.detail')}</Link></td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>

  <PaginationNav
    currentPage={activities.current_page}
    lastPage={activities.last_page}
    total={totalActivities}
    from={activities.from}
    to={activities.to}
    onNavigate={(page) =>
      router.get(index().url, query({ page }), { preserveState: true, preserveScroll: true, replace: true })}
  />
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
