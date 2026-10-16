<script lang="ts">
  import { t } from '@/lib/i18n';
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

<AppHead title={t('admin-notes.catatan_singkat')} />

<AdminPageHeader
  title={t('admin-notes.catatan_singkat')}
  subtitle={t('admin-notes.kelola_catatan_singkat_yang_tampil_di')}
  createHref={create().url}
  createLabel={t('admin-notes.tulis_catatan')}
/>

<DataTable
  data={notes}
  scrollKey="notes"
  {filters}
  url={index().url}
  columns={[
    { label: t('ui.judul'), key: 'title', sortable: true },
    { label: t('admin.status'), key: 'is_published', sortable: true },
    { label: t('admin.tag') },
    { label: t('admin-media.aksi'), align: 'end' },
  ]}
  emptyTitle={t('admin-notes.belum_ada_catatan')}
  emptyText={t('admin-notes.tulis_catatan_pertama_anda')}
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
          {note.is_published ? t('admin-certifications.terbit') : t('admin.draf')}
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
            class="btn btn-sm btn-outline-secondary">{t('admin.ubah')}</Link
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
