<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import { Link } from '@inertiajs/svelte';
  import { create, destroy, edit } from '@/wayfinder/routes/admin/portfolios';
  import type { Paginated } from '@/types/pagination';

  type PortfolioRow = {
    id: number;
    name: string;
    slug: string;
    is_private: boolean;
    technologies_count: number;
    categories_count: number;
  };

  let { portfolios }: { portfolios: Paginated<PortfolioRow> } = $props();
</script>

<AppHead title="Portofolio" />

<AdminPageHeader title="Portofolio" subtitle="Kelola daftar portofolio Anda." createHref={create().url} />

{#if portfolios.data.length === 0}
  <EmptyState title="Belum ada portofolio" text="Tambahkan portofolio pertama Anda." />
{:else}
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Nama</th>
          <th>Teknologi</th>
          <th>Kategori</th>
          <th>Privat</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        {#each portfolios.data as portfolio (portfolio.id)}
          <tr>
            <td>{portfolio.name}</td>
            <td>{portfolio.technologies_count}</td>
            <td>{portfolio.categories_count}</td>
            <td>{portfolio.is_private ? 'Ya' : 'Tidak'}</td>
            <td class="text-end">
              <div class="d-inline-flex gap-2">
                <Link href={edit(portfolio.id).url} class="btn btn-sm btn-outline-secondary">
                  Ubah
                </Link>
                <AdminDeleteButton
                  href={destroy(portfolio.id).url}
                  label={`Hapus portofolio "${portfolio.name}"?`}
                />
              </div>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>

  <SimplePaginator
    currentPage={portfolios.current_page}
    lastPage={portfolios.last_page}
    prevPageUrl={portfolios.prev_page_url}
    nextPageUrl={portfolios.next_page_url}
  />
{/if}
