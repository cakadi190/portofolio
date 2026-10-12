<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import { Link } from '@inertiajs/svelte';
  import { index } from '@/wayfinder/routes/admin/activity-logs';

  type Activity = {
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
    subject_exists: boolean;
    route_name: string | null;
    http_method: string | null;
    http_status: number | null;
    ip_address: string | null;
    user_agent: string | null;
    request_id: string | null;
    related_count: number;
    properties: Record<string, unknown>;
    changes: Record<string, unknown>;
  };

  let { activity }: { activity: Activity } = $props();

  const hasChanges = $derived(Object.keys(activity.changes).length > 0);
  const hasProperties = $derived(Object.keys(activity.properties).length > 0);
</script>

<AppHead title="Detail Aktivitas" />

<AdminPageHeader title={`Aktivitas #${activity.id}`} subtitle={activity.description} />

<div class="card mb-3">
  <div class="card-body">
    <dl class="row mb-0">
      <dt class="col-sm-3">Waktu</dt>
      <dd class="col-sm-9">{activity.created_at ? new Date(activity.created_at).toLocaleString('id-ID') : '—'}</dd>
      <dt class="col-sm-3">Kategori / Event</dt>
      <dd class="col-sm-9">{activity.log_name} / <code>{activity.event}</code></dd>
      <dt class="col-sm-3">Hasil</dt>
      <dd class="col-sm-9">{activity.outcome === 'failure' ? 'Gagal' : 'Berhasil'}</dd>
      <dt class="col-sm-3">Aktor</dt>
      <dd class="col-sm-9">
        {#if activity.causer}{activity.causer.name} ({activity.causer.email}){:else}{activity.actor_type === 'guest' ? 'Tamu' : 'Sistem'}{/if}
      </dd>
      <dt class="col-sm-3">Objek</dt>
      <dd class="col-sm-9">
        {#if activity.subject_type}
          {activity.subject_type} #{activity.subject_id}
          {#if !activity.subject_exists}<span class="text-muted">(sudah dihapus)</span>{/if}
        {:else}—{/if}
      </dd>
      <dt class="col-sm-3">Request</dt>
      <dd class="col-sm-9">
        {activity.http_method ?? '—'} {activity.route_name ?? ''} {activity.http_status ?? ''}
        {#if activity.request_id}<br /><code>{activity.request_id}</code>{/if}
        {#if activity.related_count > 0}<br /><small class="text-muted">{activity.related_count} aktivitas lain dalam request ini</small>{/if}
      </dd>
      <dt class="col-sm-3">Jaringan</dt>
      <dd class="col-sm-9">{activity.ip_address ?? '—'}<br /><small class="text-muted">{activity.user_agent ?? ''}</small></dd>
    </dl>
  </div>
</div>

{#if hasChanges}
  <div class="card mb-3">
    <div class="card-header">Perubahan (sebelum → sesudah)</div>
    <div class="card-body"><pre class="mb-0">{JSON.stringify(activity.changes, null, 2)}</pre></div>
  </div>
{/if}

{#if hasProperties}
  <div class="card mb-3">
    <div class="card-header">Properti</div>
    <div class="card-body"><pre class="mb-0">{JSON.stringify(activity.properties, null, 2)}</pre></div>
  </div>
{/if}

<Link class="btn btn-outline-secondary" href={index().url}>Kembali</Link>
