<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import { Link } from '@inertiajs/svelte';
  import { create, destroy, edit } from '@/wayfinder/routes/admin/careers';
  import type { Paginated } from '@/types/pagination';

  type CareerRow = {
    id: number;
    position: string;
    company: string;
    location: string;
    start_date: string;
    end_date: string | null;
  };

  let { careers }: { careers: Paginated<CareerRow> } = $props();
</script>

<AppHead title="Riwayat Karier" />

<AdminPageHeader title="Riwayat Karier" subtitle="Kelola riwayat pekerjaan Anda." createHref={create().url} />

{#if careers.data.length === 0}
  <EmptyState title="Belum ada karier" text="Tambahkan riwayat karier pertama Anda." />
{:else}
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Posisi</th>
          <th>Perusahaan</th>
          <th>Lokasi</th>
          <th>Periode</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        {#each careers.data as career (career.id)}
          <tr>
            <td>{career.position}</td>
            <td>{career.company}</td>
            <td>{career.location}</td>
            <td>{career.start_date} &ndash; {career.end_date ?? 'Sekarang'}</td>
            <td class="text-end">
              <div class="d-inline-flex gap-2">
                <Link href={edit(career.id).url} class="btn btn-sm btn-outline-secondary">
                  Ubah
                </Link>
                <AdminDeleteButton
                  href={destroy(career.id).url}
                  label={`Hapus karier "${career.position}"?`}
                />
              </div>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>

  <SimplePaginator
    currentPage={careers.current_page}
    lastPage={careers.last_page}
    prevPageUrl={careers.prev_page_url}
    nextPageUrl={careers.next_page_url}
  />
{/if}
