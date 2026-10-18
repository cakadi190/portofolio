<script lang="ts">
  import { t } from '@/lib/i18n';
  import { Link } from '@inertiajs/svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import AppHead from '@/components/app-head.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import { edit, index } from '@/wayfinder/routes/admin/services';
  import type { Paginated, TableFilters } from '@/types/pagination';

  type Service = {
    id: number;
    name: string;
    slug: string;
    color: string | null;
    portfolios_count: number;
  };

  let {
    services,
    filters,
  }: { services: Paginated<Service>; filters: TableFilters } = $props();
</script>

<AppHead title={t('admin.layanan')} />

<AdminPageHeader title={t('admin.layanan')} subtitle={t('admin-services.ubah_informasi_layanan_yang_anda_tawarkan')} />

<DataTable
  data={services}
  {filters}
  url={index().url}
  columns={[
    { label: t('admin.nama'), key: 'name', sortable: true },
    { label: t('admin-services.warna') },
    { label: t('admin.portofolio') },
    { label: t('admin-media.aksi'), align: 'end' },
  ]}
  emptyTitle={t('admin-services.belum_ada_layanan')}
  emptyText={t('admin-services.layanan_diisi_lewat_seeder')}
>
  {#snippet row(service)}
    <tr>
      <td>
        <Link href={edit(service.id).url} class="fw-semibold text-decoration-none">
          {service.name}
        </Link>
        <div class="text-muted small">{service.slug}</div>
      </td>
      <td>
        {#if service.color}
          <span class="badge" style={`background-color:${service.color}`}>{service.color}</span>
        {:else}
          <span class="text-muted">&mdash;</span>
        {/if}
      </td>
      <td>{service.portfolios_count}</td>
      <td class="text-end">
        <Link href={edit(service.id).url} class="btn btn-sm btn-outline-secondary">{t('admin.ubah')}</Link>
      </td>
    </tr>
  {/snippet}
</DataTable>
