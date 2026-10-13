<script lang="ts">
  import Select from '@/components/ui/select.svelte';
  import { router } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import GuestbookEntryEditModal from '@/components/admin/guestbook-entry-edit-modal.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import { formatDate } from '@/lib/utils';
  import { destroy, index } from '@/wayfinder/routes/admin/guestbook-entries';
  import type { Paginated, TableFilters } from '@/types/pagination';

  type Option = { value: string; label: string };

  type Row = {
    id: number;
    parent_id: number | null;
    body: string;
    status: string;
    replies_count: number;
    created_at: string;
    user: { id: number; name: string; email: string } | null;
    guest_name: string | null;
    guest_email: string | null;
    parent: { id: number; body: string } | null;
  };

  let {
    guestbookEntries,
    filters,
    statuses,
  }: {
    guestbookEntries: Paginated<Row>;
    filters: TableFilters & { status: string | null };
    statuses: Option[];
  } = $props();

  let editOpen = $state(false);
  let editing = $state<Row | null>(null);

  const statusLabel = (value: string): string =>
    statuses.find((status) => status.value === value)?.label ?? value;

  function filterStatus(status: string | number | null): void {
    router.get(
      index().url,
      {
        ...filters,
        status: status ? String(status) : undefined,
        page: undefined,
      },
      { preserveState: true, replace: true },
    );
  }

  function openEdit(row: Row): void {
    editing = row;
    editOpen = true;
  }
</script>

<AppHead title="Buku Tamu" />

<AdminPageHeader
  title="Buku Tamu"
  subtitle="Moderasi pesan dan balasan di buku tamu."
/>

<div class="mb-3">
  <Select
    inline
    ariaLabel="Filter status"
    placeholder="Semua status"
    items={statuses}
    value={filters.status || null}
    onchange={filterStatus}
  />
</div>

<DataTable
  data={guestbookEntries}
  {filters}
  url={index().url}
  columns={[
    { label: 'Pesan' },
    { label: 'Penulis' },
    { label: 'Status', key: 'status', sortable: true },
    { label: 'Dikirim', key: 'created_at', sortable: true },
    { label: 'Aksi', align: 'end' },
  ]}
  emptyTitle="Belum ada pesan"
  emptyText="Pesan dari pengunjung akan muncul di sini."
>
  {#snippet row(entry)}
    <tr>
      <td style="max-width: 20rem;">
        <div class="text-truncate">{entry.body}</div>
        <small class="opacity-75">
          {#if entry.parent}
            Balasan untuk #{entry.parent.id} ·
          {:else}
            Pesan utama ·
          {/if}
          {entry.replies_count} balasan
        </small>
      </td>
      <td>
        {#if entry.user}
          {entry.user.name}<br /><small class="opacity-75"
            >{entry.user.email}</small
          >
        {:else if entry.guest_name}
          {entry.guest_name}<br /><small class="opacity-75"
            >{entry.guest_email} · tamu</small
          >
        {:else}
          <em class="opacity-75">Pengguna terhapus</em>
        {/if}
      </td>
      <td>
        <span
          class="badge"
          class:text-bg-success={entry.status === 'approved'}
          class:text-bg-warning={entry.status === 'pending'}
          class:text-bg-danger={entry.status === 'rejected'}
        >
          {statusLabel(entry.status)}
        </span>
      </td>
      <td>{formatDate(entry.created_at)}</td>
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            onclick={() => openEdit(entry)}>Ubah</button
          >
          <AdminDeleteButton
            href={destroy(entry.id).url}
            label={`Hapus pesan #${entry.id}?`}
            description="Balasan pesan ini tidak ikut dihapus, melainkan naik satu tingkat."
          />
        </div>
      </td>
    </tr>
  {/snippet}
</DataTable>

<GuestbookEntryEditModal bind:open={editOpen} entry={editing} {statuses} />
