<script lang="ts">
  import { Link, router } from '@inertiajs/svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import AppHead from '@/components/app-head.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import {
    create,
    destroy,
    edit,
    index,
    restore,
  } from '@/wayfinder/routes/admin/posts';
  import type { InfiniteList, TableFilters } from '@/types/pagination';

  type Post = {
    id: number;
    title: string;
    slug: string;
    is_published: boolean;
    published_at: string | null;
    created_at: string;
  };

  let {
    posts,
    filters,
    trashed,
    trashedCount,
  }: {
    posts: InfiniteList<Post>;
    filters: TableFilters;
    trashed: boolean;
    trashedCount: number;
  } = $props();
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
  scrollKey="posts"
  {filters}
  url={trashed ? index({ query: { trashed: 1 } }).url : index().url}
  columns={[
    { label: 'Judul', key: 'title', sortable: true },
    { label: 'Status', key: 'is_published', sortable: true },
    { label: 'Tanggal', key: 'published_at', sortable: true },
    { label: 'Aksi', align: 'end' },
  ]}
  emptyTitle={trashed ? 'Sampah kosong' : 'Belum ada artikel'}
  emptyText={trashed
    ? 'Artikel yang dipindahkan ke sampah muncul di sini.'
    : 'Tulis artikel pertama Anda.'}
>
  {#snippet row(post)}
    {@const scheduled =
      post.is_published &&
      !!post.published_at &&
      new Date(post.published_at).getTime() > Date.now()}
    <tr>
      <td>
        <Link href={edit(post.id).url} class="fw-semibold text-decoration-none">
          {post.title}
        </Link>
      </td>
      <td>
        <span
          class={`badge ${scheduled ? 'text-bg-info' : post.is_published ? 'text-bg-success' : 'text-bg-secondary'}`}
        >
          {scheduled ? 'Terjadwal' : post.is_published ? 'Terbit' : 'Draf'}
        </span>
      </td>
      <td class="text-secondary">
        {new Date(post.published_at ?? post.created_at).toLocaleDateString(
          'id-ID',
          { day: 'numeric', month: 'short', year: 'numeric' },
        )}
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
