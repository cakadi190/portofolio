<script lang="ts">
  import { Link } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import { storageUrl } from '@/lib/utils';
  import { create, destroy, edit, index } from '@/wayfinder/routes/admin/portfolios';
  import type { Paginated, TableFilters } from '@/types/pagination';

  type Portfolio = {
    id: number;
    name: string;
    image: string;
    is_private: boolean;
    technologies: { id: number; name: string }[];
    services: { id: number; name: string }[];
  };

  let {
    portfolios,
    filters,
  }: {
    filters: TableFilters;
    portfolios: Paginated<Portfolio>;
  } = $props();
</script>

<AppHead title="Portofolio" />

<AdminPageHeader
  title="Portofolio"
  subtitle="Kelola daftar portofolio Anda."
  createHref={create().url}
  createLabel="Tambah Portofolio"
/>

<DataTable
  data={portfolios}
  {filters}
  url={index().url}
  columns={[
    { label: 'Sampul' },
    { label: 'Nama', key: 'name', sortable: true },
    { label: 'Teknologi' },
    { label: 'Layanan' },
    { label: 'Privat', key: 'is_private', sortable: true },
    { label: 'Aksi', align: 'end' },
  ]}
  emptyTitle="Belum ada portofolio"
  emptyText="Tambahkan portofolio pertama Anda."
>
  {#snippet row(portfolio)}
    <tr>
      <td>
        <img
          src={storageUrl(portfolio.image) ?? ''}
          alt={portfolio.name}
          loading="lazy"
          style="width:3.5rem;height:2.5rem;object-fit:cover;border-radius:0.5rem;"
        />
      </td>
      <td>
        <Link href={edit(portfolio.id).url} class="fw-semibold text-decoration-none">
          {portfolio.name}
        </Link>
      </td>
      <td
        ><div class="d-flex flex-wrap gap-1">
          {#each portfolio.technologies as item (item.id)}
            <span class="badge text-bg-secondary fw-normal">{item.name}</span>
          {:else}
            <span class="text-muted">&mdash;</span>
          {/each}
        </div></td
      >
      <td
        ><div class="d-flex flex-wrap gap-1">
          {#each portfolio.services as item (item.id)}
            <span class="badge text-bg-secondary fw-normal">{item.name}</span>
          {:else}
            <span class="text-muted">&mdash;</span>
          {/each}
        </div></td
      >
      <td>{portfolio.is_private ? 'Ya' : 'Tidak'}</td>
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          <Link
            href={edit(portfolio.id).url}
            class="btn btn-sm btn-outline-secondary"
          >
            Ubah
          </Link>
          <AdminDeleteButton
            href={destroy(portfolio.id).url}
            label={`Hapus portofolio "${portfolio.name}"?`}
          />
        </div>
      </td>
    </tr>
  {/snippet}
</DataTable>
