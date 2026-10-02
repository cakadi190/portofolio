<script lang="ts">
  import { Link } from '@inertiajs/svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import AppHead from '@/components/app-head.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import { create, destroy, edit, index } from '@/wayfinder/routes/admin/posts';
  import type { Paginated, TableFilters } from '@/types/pagination';

  type Post = {
    id: number;
    title: string;
    slug: string;
    is_published: boolean;
    tags: { id: number; name: string }[];
    categories: { id: number; name: string }[];
  };

  let { posts, filters }: { posts: Paginated<Post>; filters: TableFilters } =
    $props();
</script>

<AppHead title="Artikel Blog" />

<AdminPageHeader
  title="Artikel Blog"
  subtitle="Kelola artikel blog Anda."
  createHref={create().url}
  createLabel="Tulis Artikel"
/>

<DataTable
  data={posts}
  {filters}
  url={index().url}
  columns={[
    { label: 'Judul', key: 'title', sortable: true },
    { label: 'Status', key: 'is_published', sortable: true },
    { label: 'Tag' },
    { label: 'Kategori' },
    { label: 'Aksi', align: 'end' },
  ]}
  emptyTitle="Belum ada artikel"
  emptyText="Tulis artikel pertama Anda."
>
  {#snippet row(post)}
    <tr>
      <td>
        <Link href={edit(post.id).url} class="fw-semibold text-decoration-none">
          {post.title}
        </Link>
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
          <Link
            href={edit(post.id).url}
            class="btn btn-sm btn-outline-secondary">Ubah</Link
          >
          <AdminDeleteButton
            href={destroy(post.id).url}
            label={`Hapus artikel "${post.title}"?`}
          />
        </div>
      </td>
    </tr>
  {/snippet}
</DataTable>
