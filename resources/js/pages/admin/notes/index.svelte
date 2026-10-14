<script lang="ts">
  import { Link } from '@inertiajs/svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import AppHead from '@/components/app-head.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import { create, destroy, edit, index } from '@/wayfinder/routes/admin/notes';
  import type { InfiniteList, TableFilters } from '@/types/pagination';

  type Note = {
    id: number;
    title: string;
    slug: string;
    is_published: boolean;
    tags: { id: number; name: string }[];
  };

  let { notes, filters }: { notes: InfiniteList<Note>; filters: TableFilters } =
    $props();
</script>

<AppHead title="Catatan Singkat" />

<AdminPageHeader
  title="Catatan Singkat"
  subtitle="Kelola catatan singkat yang tampil di halaman /notes."
  createHref={create().url}
  createLabel="Tulis Catatan"
/>

<DataTable
  data={notes}
  scrollKey="notes"
  {filters}
  url={index().url}
  columns={[
    { label: 'Judul', key: 'title', sortable: true },
    { label: 'Status', key: 'is_published', sortable: true },
    { label: 'Tag' },
    { label: 'Aksi', align: 'end' },
  ]}
  emptyTitle="Belum ada catatan"
  emptyText="Tulis catatan pertama Anda."
>
  {#snippet row(note)}
    <tr>
      <td>
        <Link href={edit(note.id).url} class="fw-semibold text-decoration-none">
          {note.title}
        </Link>
      </td>
      <td>
        <span
          class={`badge ${note.is_published ? 'text-bg-success' : 'text-bg-secondary'}`}
        >
          {note.is_published ? 'Terbit' : 'Draf'}
        </span>
      </td>
      <td>
        <div class="d-flex flex-wrap gap-1">
          {#each note.tags as item (item.id)}
            <span class="badge text-bg-secondary fw-normal">{item.name}</span>
          {:else}
            <span class="text-muted">&mdash;</span>
          {/each}
        </div>
      </td>
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          <Link
            href={edit(note.id).url}
            class="btn btn-sm btn-outline-secondary">Ubah</Link
          >
          <AdminDeleteButton
            href={destroy(note.id).url}
            label={`Hapus catatan "${note.title}"?`}
          />
        </div>
      </td>
    </tr>
  {/snippet}
</DataTable>
