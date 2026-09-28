<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import { Link } from '@inertiajs/svelte';
  import { create, destroy, edit } from '@/wayfinder/routes/admin/tags';
  import type { Paginated } from '@/types/pagination';

  let { tags }: { tags: Paginated<{ id: number; name: string }> } = $props();
</script>

<AppHead title="Tag" />

<AdminPageHeader title="Tag" subtitle="Kelola tag artikel blog." createHref={create().url} />

{#if tags.data.length === 0}
  <EmptyState title="Belum ada tag" text="Tambahkan tag pertama untuk artikel blog Anda." />
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
        {#each tags.data as tag (tag.id)}
          <tr>
            <td>{tag.name}</td>
            <td class="text-end">
              <div class="d-inline-flex gap-2">
                <Link href={edit(tag.id).url} class="btn btn-sm btn-outline-secondary">Ubah</Link>
                <AdminDeleteButton href={destroy(tag.id).url} label={`Hapus tag "${tag.name}"?`} />
              </div>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>

  <SimplePaginator
    currentPage={tags.current_page}
    lastPage={tags.last_page}
    prevPageUrl={tags.prev_page_url}
    nextPageUrl={tags.next_page_url}
  />
{/if}
