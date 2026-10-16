<script lang="ts">
  import { t } from '@/lib/i18n';
  import { Link } from '@inertiajs/svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import AppHead from '@/components/app-head.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import VerdictBadge from '@/components/clarification/verdict-badge.svelte';
  import type { InfiniteList, TableFilters } from '@/types/pagination';
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
  }: { clarifications: InfiniteList<Clarification>; filters: TableFilters } =
    $props();
</script>

<AppHead title={t('admin-clarifications.klarifikasi')} />

<AdminPageHeader
  title={t('admin-clarifications.klarifikasi')}
  subtitle={t('admin-clarifications.kelola_klarifikasi_dan_hak_jawab_atas')}
  createHref={create().url}
  createLabel={t('admin-clarifications.tulis_klarifikasi')}
/>

<DataTable
  data={clarifications}
  scrollKey="clarifications"
  {filters}
  url={index().url}
  columns={[
    { label: t('ui.judul'), key: 'title', sortable: true },
    { label: t('admin-clarifications.vonis'), key: 'verdict', sortable: true },
    { label: t('admin.status'), key: 'is_published', sortable: true },
    { label: t('admin-media.aksi'), align: 'end' },
  ]}
  emptyTitle={t('admin-clarifications.belum_ada_klarifikasi')}
  emptyText={t('admin-clarifications.tulis_klarifikasi_pertama_anda')}
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
          {item.is_published ? t('admin-certifications.terbit') : t('admin.draf')}
        </span>
      </td>
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          <Link
            href={edit(item.id).url}
            class="btn btn-sm btn-outline-secondary">{t('admin.ubah')}</Link
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
