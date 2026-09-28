<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import { Link } from '@inertiajs/svelte';
  import { create, destroy, edit } from '@/wayfinder/routes/admin/post-categories';
  import type { Paginated } from '@/types/pagination';

  type CategoryRow = { id: number; name: string; color: string | null };

  let { postCategories }: { postCategories: Paginated<CategoryRow> } = $props();
</script>

<AppHead title="Kategori Artikel" />

<AdminPageHeader
  title="Kategori Artikel"
  subtitle="Kelola kategori artikel blog."
  createHref={create().url}
/>

{#if postCategories.data.length === 0}
  <EmptyState title="Belum ada kategori" text="Tambahkan kategori artikel pertama Anda." />
{:else}
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Nama</th>
          <th>Warna</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        {#each postCategories.data as category (category.id)}
          <tr>
            <td>{category.name}</td>
            <td>
              {#if category.color}
                <span class="badge" style={`background-color:${category.color}`}
                  >{category.color}</span
                >
              {:else}
                <span class="text-muted">&mdash;</span>
              {/if}
            </td>
            <td class="text-end">
              <div class="d-inline-flex gap-2">
                <Link href={edit(category.id).url} class="btn btn-sm btn-outline-secondary">
                  Ubah
                </Link>
                <AdminDeleteButton
                  href={destroy(category.id).url}
                  label={`Hapus kategori "${category.name}"?`}
                />
              </div>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>

  <SimplePaginator
    currentPage={postCategories.current_page}
    lastPage={postCategories.last_page}
    prevPageUrl={postCategories.prev_page_url}
    nextPageUrl={postCategories.next_page_url}
  />
{/if}
