<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import { Link } from '@inertiajs/svelte';
  import { create, destroy, edit } from '@/wayfinder/routes/admin/awards';
  import type { Paginated } from '@/types/pagination';

  type AwardRow = {
    id: number;
    event_name: string;
    title: string;
    icon: string | null;
    year: number;
    rank: number | null;
  };

  let { awards }: { awards: Paginated<AwardRow> } = $props();
</script>

<AppHead title="Penghargaan" />

<AdminPageHeader
  title="Penghargaan"
  subtitle="Kelola penghargaan dan sertifikat Anda."
  createHref={create().url}
/>

{#if awards.data.length === 0}
  <EmptyState title="Belum ada penghargaan" text="Tambahkan penghargaan pertama Anda." />
{:else}
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Ikon</th>
          <th>Judul</th>
          <th>Acara</th>
          <th>Tahun</th>
          <th>Peringkat</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        {#each awards.data as award (award.id)}
          <tr>
            <td>
              {#if award.icon}
                <img
                  src={`/storage/${award.icon}`}
                  alt={award.title}
                  style="width:2.5rem;height:2.5rem;object-fit:cover;border-radius:0.5rem;"
                />
              {:else}
                <span class="text-muted">&mdash;</span>
              {/if}
            </td>
            <td>{award.title}</td>
            <td>{award.event_name}</td>
            <td>{award.year}</td>
            <td>{award.rank ?? '—'}</td>
            <td class="text-end">
              <div class="d-inline-flex gap-2">
                <Link href={edit(award.id).url} class="btn btn-sm btn-outline-secondary">
                  Ubah
                </Link>
                <AdminDeleteButton
                  href={destroy(award.id).url}
                  label={`Hapus penghargaan "${award.title}"?`}
                />
              </div>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>

  <SimplePaginator
    currentPage={awards.current_page}
    lastPage={awards.last_page}
    prevPageUrl={awards.prev_page_url}
    nextPageUrl={awards.next_page_url}
  />
{/if}
