<script lang="ts">
  import { Link, router } from '@inertiajs/svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import PostAuthorModal from '@/components/admin/post-author-modal.svelte';
  import AppHead from '@/components/app-head.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import {
    create,
    destroy,
    edit,
    index,
    restore,
  } from '@/wayfinder/routes/admin/posts';
  import type { Paginated, TableFilters } from '@/types/pagination';

  type Post = {
    id: number;
    title: string;
    slug: string;
    is_published: boolean;
    user_id: number | null;
    author: { id: number; name: string } | null;
    tags: { id: number; name: string }[];
    categories: { id: number; name: string }[];
  };

  type Author = { id: number; name: string };

  let {
    posts,
    filters,
    authors,
    trashed,
    trashedCount,
  }: {
    posts: Paginated<Post>;
    filters: TableFilters;
    authors: Author[];
    trashed: boolean;
    trashedCount: number;
  } = $props();

  let authorOpen = $state(false);
  let authorPost = $state<Post | null>(null);

  function openAuthor(post: Post): void {
    authorPost = post;
    authorOpen = true;
  }
</script>

<AppHead title="Artikel Blog" />

<AdminPageHeader
  title="Artikel Blog"
  subtitle="Kelola artikel blog Anda."
  createHref={create().url}
  createLabel="Tulis Artikel"
/>

<ul class="nav nav-pills mb-3 gap-2">
  <li class="nav-item">
    <Link href={index().url} class={`nav-link ${trashed ? '' : 'active'}`}
      >Semua</Link
    >
  </li>
  <li class="nav-item">
    <Link
      href={index({ query: { trashed: 1 } }).url}
      class={`nav-link ${trashed ? 'active' : ''}`}
      >Sampah{trashedCount > 0 ? ` (${trashedCount})` : ''}</Link
    >
  </li>
</ul>

<DataTable
  data={posts}
  {filters}
  url={trashed ? index({ query: { trashed: 1 } }).url : index().url}
  columns={[
    { label: 'Judul', key: 'title', sortable: true },
    { label: 'Penulis' },
    { label: 'Status', key: 'is_published', sortable: true },
    { label: 'Tag' },
    { label: 'Kategori' },
    { label: 'Aksi', align: 'end' },
  ]}
  emptyTitle={trashed ? 'Sampah kosong' : 'Belum ada artikel'}
  emptyText={trashed
    ? 'Artikel yang dipindahkan ke sampah muncul di sini.'
    : 'Tulis artikel pertama Anda.'}
>
  {#snippet row(post)}
    <tr>
      <td>
        <Link href={edit(post.id).url} class="fw-semibold text-decoration-none">
          {post.title}
        </Link>
      </td>
      <td>
        <button
          type="button"
          class="btn btn-sm btn-link p-0 text-decoration-none"
          title="Ganti penulis"
          onclick={() => openAuthor(post)}
        >
          {post.author?.name ?? 'Tanpa penulis'}
        </button>
      </td>
      <td>
        <span
          class={`badge ${post.is_published ? 'text-bg-success' : 'text-bg-secondary'}`}
        >
          {post.is_published ? 'Terbit' : 'Draf'}
        </span>
      </td>
      <td>
        <div class="d-flex flex-wrap gap-1">
          {#each post.tags as item (item.id)}
            <span class="badge text-bg-secondary fw-normal">{item.name}</span>
          {:else}
            <span class="text-muted">&mdash;</span>
          {/each}
        </div>
      </td>
      <td>
        <div class="d-flex flex-wrap gap-1">
          {#each post.categories as item (item.id)}
            <span class="badge text-bg-secondary fw-normal">{item.name}</span>
          {:else}
            <span class="text-muted">&mdash;</span>
          {/each}
        </div>
      </td>
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          {#if trashed}
            <button
              type="button"
              class="btn btn-sm btn-outline-secondary"
              onclick={() =>
                router.patch(
                  restore(post.id).url,
                  {},
                  { preserveScroll: true },
                )}>Pulihkan</button
            >
            <AdminDeleteButton
              href={destroy(post.id).url}
              label={`Hapus permanen artikel "${post.title}"?`}
              buttonLabel="Hapus permanen"
              confirmLabel="Hapus permanen"
            />
          {:else}
            <Link
              href={edit(post.id).url}
              class="btn btn-sm btn-outline-secondary">Ubah</Link
            >
            <AdminDeleteButton
              href={destroy(post.id).url}
              label={`Pindahkan artikel "${post.title}" ke sampah?`}
              description="Artikel tidak lagi tampil di situs dan bisa dipulihkan dari tab Sampah."
              buttonLabel="Sampah"
              confirmLabel="Pindahkan"
            />
          {/if}
        </div>
      </td>
    </tr>
  {/snippet}
</DataTable>

<PostAuthorModal bind:open={authorOpen} post={authorPost} {authors} />
