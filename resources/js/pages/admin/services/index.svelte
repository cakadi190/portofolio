<script lang="ts">
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

<AppHead title="Layanan" />

<AdminPageHeader title="Layanan" subtitle="Ubah informasi layanan yang Anda tawarkan." />

<DataTable
  data={services}
  {filters}
  url={index().url}
  columns={[
    { label: 'Nama', key: 'name', sortable: true },
    { label: 'Warna' },
    { label: 'Portofolio' },
    { label: 'Aksi', align: 'end' },
  ]}
  emptyTitle="Belum ada layanan"
  emptyText="Layanan diisi lewat seeder."
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
        <Link href={edit(service.id).url} class="btn btn-sm btn-outline-secondary">Ubah</Link>
      </td>
    </tr>
  {/snippet}
</DataTable>
