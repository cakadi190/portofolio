<script lang="ts">
  import { t } from '@/lib/i18n';
  import Select from '@/components/ui/select.svelte';
  import { router } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import GuestbookThreadNode from '@/components/admin/guestbook-thread-node.svelte';
  import type { GuestbookAdminNode } from '@/components/admin/guestbook-thread';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import GuestbookEntryEditModal from '@/components/admin/guestbook-entry-edit-modal.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import { index } from '@/wayfinder/routes/admin/guestbook-entries';
  import type { InfiniteList, TableFilters } from '@/types/pagination';

  type Option = { value: string; label: string };

  let {
    guestbookEntries,
    filters,
    statuses,
  }: {
    guestbookEntries: InfiniteList<GuestbookAdminNode>;
    filters: TableFilters & { status: string | null };
    statuses: Option[];
  } = $props();

  let editOpen = $state(false);
  let editing = $state<{ id: number; body: string; status: string } | null>(null);
  let replyingTo = $state<number | null>(null);

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
      { preserveState: true, replace: true, reset: ['guestbookEntries'] },
    );
  }

  function openEdit(entry: GuestbookAdminNode): void {
    editing = { id: entry.id, body: entry.raw_body, status: entry.status };
    editOpen = true;
  }
</script>

<AppHead title={t('admin-guestbook-entries.buku_tamu')} />

<AdminPageHeader
  title={t('admin-guestbook-entries.buku_tamu')}
  subtitle={t('admin-guestbook-entries.moderasi_pesan_dan_balas_langsung_dalam')}
/>

<DataTable
  variant="grid"
  data={guestbookEntries}
  scrollKey="guestbookEntries"
  {filters}
  url={index().url}
  emptyTitle={t('admin.belum_ada_pesan')}
  emptyText={t('admin.pesan_dari_pengunjung_akan_muncul_di')}
>
  {#snippet toolbar()}
    <Select
        inline
        ariaLabel={t('admin.filter_status')}
        placeholder={t('admin.semua_status')}
        items={statuses}
        value={filters.status || null}
        onchange={filterStatus}
      />
  {/snippet}
  {#snippet row(thread)}
    <div class="col-12">
      <ul class="list-unstyled mb-0">
        <GuestbookThreadNode
          entry={thread}
          {statusLabel}
          {replyingTo}
          onReply={(id) => (replyingTo = id)}
          onEdit={openEdit}
        />
      </ul>
    </div>
  {/snippet}
</DataTable>

<GuestbookEntryEditModal bind:open={editOpen} entry={editing} {statuses} />
