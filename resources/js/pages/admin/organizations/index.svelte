<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import { Link } from '@inertiajs/svelte';
  import { create, destroy, edit } from '@/wayfinder/routes/admin/organizations';
  import type { Paginated } from '@/types/pagination';

  type OrganizationRow = {
    id: number;
    name: string;
    start_date: string;
    end_date: string | null;
  };

  let { organizations }: { organizations: Paginated<OrganizationRow> } = $props();
</script>

<AppHead title="Pengalaman Organisasi" />

<AdminPageHeader
  title="Pengalaman Organisasi"
  subtitle="Kelola riwayat pengalaman organisasi Anda."
  createHref={create().url}
/>

{#if organizations.data.length === 0}
  <EmptyState title="Belum ada organisasi" text="Tambahkan pengalaman organisasi pertama Anda." />
{:else}
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Nama</th>
          <th>Periode</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        {#each organizations.data as organization (organization.id)}
          <tr>
            <td>{organization.name}</td>
            <td>{organization.start_date} &ndash; {organization.end_date ?? 'Sekarang'}</td>
            <td class="text-end">
              <div class="d-inline-flex gap-2">
                <Link href={edit(organization.id).url} class="btn btn-sm btn-outline-secondary">
                  Ubah
                </Link>
                <AdminDeleteButton
                  href={destroy(organization.id).url}
                  label={`Hapus organisasi "${organization.name}"?`}
                />
              </div>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>

  <SimplePaginator
    currentPage={organizations.current_page}
    lastPage={organizations.last_page}
    prevPageUrl={organizations.prev_page_url}
    nextPageUrl={organizations.next_page_url}
  />
{/if}
