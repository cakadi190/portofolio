<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import { Link } from '@inertiajs/svelte';
  import { create, destroy, edit } from '@/wayfinder/routes/admin/posts';
  import type { Paginated } from '@/types/pagination';

  type PostRow = {
    id: number;
    title: string;
    slug: string;
    is_published: boolean;
    tags_count: number;
    categories_count: number;
  };

  let { posts }: { posts: Paginated<PostRow> } = $props();
</script>

<AppHead title="Artikel Blog" />

<AdminPageHeader title="Artikel Blog" subtitle="Kelola artikel blog Anda." createHref={create().url} />

{#if posts.data.length === 0}
  <EmptyState title="Belum ada artikel" text="Tulis artikel pertama Anda." />
{:else}
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Judul</th>
          <th>Status</th>
          <th>Tag</th>
          <th>Kategori</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        {#each posts.data as post (post.id)}
          <tr>
            <td>{post.title}</td>
            <td>
              <span class={`badge ${post.is_published ? 'text-bg-success' : 'text-bg-secondary'}`}>
                {post.is_published ? 'Terbit' : 'Draf'}
              </span>
            </td>
            <td>{post.tags_count}</td>
            <td>{post.categories_count}</td>
            <td class="text-end">
              <div class="d-inline-flex gap-2">
                <Link href={edit(post.id).url} class="btn btn-sm btn-outline-secondary">
                  Ubah
                </Link>
                <AdminDeleteButton
                  href={destroy(post.id).url}
                  label={`Hapus artikel "${post.title}"?`}
                />
              </div>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>

  <SimplePaginator
    currentPage={posts.current_page}
    lastPage={posts.last_page}
    prevPageUrl={posts.prev_page_url}
    nextPageUrl={posts.next_page_url}
  />
{/if}
