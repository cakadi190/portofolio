<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import { Link } from '@inertiajs/svelte';
  import { create, destroy, edit } from '@/wayfinder/routes/admin/educations';
  import type { Paginated } from '@/types/pagination';

  type EducationRow = {
    id: number;
    name: string;
    level: string;
    place: string;
    start_date: string;
    end_date: string | null;
  };

  let { educations }: { educations: Paginated<EducationRow> } = $props();
</script>

<AppHead title="Riwayat Pendidikan" />

<AdminPageHeader
  title="Riwayat Pendidikan"
  subtitle="Kelola riwayat pendidikan Anda."
  createHref={create().url}
/>

{#if educations.data.length === 0}
  <EmptyState title="Belum ada riwayat pendidikan" text="Tambahkan riwayat pendidikan pertama Anda." />
{:else}
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Nama</th>
          <th>Jenjang</th>
          <th>Tempat</th>
          <th>Periode</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        {#each educations.data as education (education.id)}
          <tr>
            <td>{education.name}</td>
            <td>{education.level}</td>
            <td>{education.place}</td>
            <td>{education.start_date} &ndash; {education.end_date ?? 'Sekarang'}</td>
            <td class="text-end">
              <div class="d-inline-flex gap-2">
                <Link href={edit(education.id).url} class="btn btn-sm btn-outline-secondary">
                  Ubah
                </Link>
                <AdminDeleteButton
                  href={destroy(education.id).url}
                  label={`Hapus riwayat pendidikan "${education.name}"?`}
                />
              </div>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>

  <SimplePaginator
    currentPage={educations.current_page}
    lastPage={educations.last_page}
    prevPageUrl={educations.prev_page_url}
    nextPageUrl={educations.next_page_url}
  />
{/if}
