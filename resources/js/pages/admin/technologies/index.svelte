<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import { Link } from '@inertiajs/svelte';
  import { create, destroy, edit } from '@/wayfinder/routes/admin/technologies';
  import type { Paginated } from '@/types/pagination';

  let { technologies }: { technologies: Paginated<{ id: number; name: string }> } = $props();
</script>

<AppHead title="Teknologi" />

<AdminPageHeader title="Teknologi" subtitle="Kelola daftar teknologi yang digunakan pada portofolio." createHref={create().url} />

{#if technologies.data.length === 0}
  <EmptyState title="Belum ada teknologi" text="Tambahkan teknologi pertama untuk portofolio Anda." />
{:else}
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Nama</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        {#each technologies.data as technology (technology.id)}
          <tr>
            <td>{technology.name}</td>
            <td class="text-end">
              <div class="d-inline-flex gap-2">
                <Link href={edit(technology.id).url} class="btn btn-sm btn-outline-secondary">Ubah</Link>
                <AdminDeleteButton href={destroy(technology.id).url} label={`Hapus teknologi "${technology.name}"?`} />
              </div>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>

  <SimplePaginator
    currentPage={technologies.current_page}
    lastPage={technologies.last_page}
    prevPageUrl={technologies.prev_page_url}
    nextPageUrl={technologies.next_page_url}
  />
{/if}
