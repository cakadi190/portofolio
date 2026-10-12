<script lang="ts">
  import Select from '@/components/ui/select.svelte';
  import AppHead from '@/components/app-head.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import { Link, router } from '@inertiajs/svelte';
  import { index, show, exportMethod } from '@/wayfinder/routes/admin/activity-logs';
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
    filters,
    options,
  }: {
    activities: Paginated<ActivityRow>;
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

<form class="card card-body mb-3" onsubmit={apply}>
  <div class="row g-2">
    <div class="col-md-4">
      <input class="form-control" type="search" placeholder="Cari deskripsi, event, atau ID request" bind:value={form.search} aria-label="Cari" />
    </div>
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
  <div class="d-flex gap-2 mt-3">
    <button class="btn btn-primary" type="submit">Terapkan</button>
    <button class="btn btn-outline-secondary" type="button" onclick={reset}>Atur ulang</button>
    <a class="btn btn-outline-secondary ms-auto" href={exportUrl}>Ekspor CSV (maks. 5.000)</a>
  </div>
</form>

{#if activities.data.length === 0}
  <EmptyState title="Tidak ada aktivitas" text="Tidak ada catatan yang cocok dengan filter." />
{:else}
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

  <div class="d-flex justify-content-between align-items-center">
    <small class="text-muted">{activities.from}–{activities.to} dari {activities.total}</small>
    <div class="btn-group">
      {#if activities.prev_page_url}<Link class="btn btn-outline-secondary" href={activities.prev_page_url}>Sebelumnya</Link>{/if}
      {#if activities.next_page_url}<Link class="btn btn-outline-secondary" href={activities.next_page_url}>Berikutnya</Link>{/if}
    </div>
  </div>
{/if}
