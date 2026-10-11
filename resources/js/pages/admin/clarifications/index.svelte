<script lang="ts">
  import { Link } from '@inertiajs/svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import AppHead from '@/components/app-head.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import VerdictBadge from '@/components/clarification/verdict-badge.svelte';
  import type { Paginated, TableFilters } from '@/types/pagination';
  import {
    create,
    destroy,
    edit,
    index,
  } from '@/wayfinder/routes/admin/clarifications';

  type Clarification = {
    id: number;
    title: string;
    slug: string;
    verdict_label: string;
    verdict_color: string;
    is_published: boolean;
    published_at: string | null;
  };

  let {
    clarifications,
    filters,
  }: { clarifications: Paginated<Clarification>; filters: TableFilters } =
    $props();
</script>

<AppHead title="Klarifikasi" />

<AdminPageHeader
  title="Klarifikasi"
  subtitle="Kelola klarifikasi dan hak jawab atas klaim yang beredar."
  createHref={create().url}
  createLabel="Tulis Klarifikasi"
/>

<DataTable
  data={clarifications}
  {filters}
  url={index().url}
  columns={[
    { label: 'Judul', key: 'title', sortable: true },
    { label: 'Vonis', key: 'verdict', sortable: true },
    { label: 'Status', key: 'is_published', sortable: true },
    { label: 'Aksi', align: 'end' },
  ]}
  emptyTitle="Belum ada klarifikasi"
  emptyText="Tulis klarifikasi pertama Anda."
>
  {#snippet row(item)}
    <tr>
      <td>
        <Link
          href={edit(item.id).url}
          class="fw-semibold text-decoration-none"
        >
          {item.title}
        </Link>
      </td>
      <td>
        <VerdictBadge label={item.verdict_label} color={item.verdict_color} />
      </td>
      <td>
        <span
          class={`badge ${item.is_published ? 'text-bg-success' : 'text-bg-secondary'}`}
        >
          {item.is_published ? 'Terbit' : 'Draf'}
        </span>
      </td>
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          <Link
            href={edit(item.id).url}
            class="btn btn-sm btn-outline-secondary">Ubah</Link
          >
          <AdminDeleteButton
            href={destroy(item.id).url}
            label={`Hapus klarifikasi "${item.title}"?`}
          />
        </div>
      </td>
    </tr>
  {/snippet}
</DataTable>
