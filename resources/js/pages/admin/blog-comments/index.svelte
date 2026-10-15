<script lang="ts">
  import Select from '@/components/ui/select.svelte';
  import { Link, router } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import BlogCommentEditModal from '@/components/admin/blog-comment-edit-modal.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import { formatDate } from '@/lib/utils';
  import { destroy, index, show } from '@/wayfinder/routes/admin/blog-comments';
  import type { InfiniteList, TableFilters } from '@/types/pagination';

  type Option = { value: string; label: string };

  type Row = {
    id: number;
    parent_id: number | null;
    body: string;
    status: string;
    replies_count: number;
    created_at: string;
    post: { id: number; title: string; slug: string };
    guest_name: string | null;
    guest_email: string | null;
    user: { id: number; name: string; email: string } | null;
    parent: { id: number; body: string } | null;
  };

  let {
    blogComments,
    filters,
    statuses,
  }: {
    blogComments: InfiniteList<Row>;
    filters: TableFilters & { status: string | null };
    statuses: Option[];
  } = $props();

  let editOpen = $state(false);
  let editing = $state<Row | null>(null);

  const statusLabel = (value: string): string =>
    statuses.find((status) => status.value === value)?.label ?? value;

  function filterStatus(status: string | number | null): void {
    router.get(
      index().url,
      {
        ...filters,
        status: status ? String(status) : undefined,
        page: undefined,
      },
      { preserveState: true, replace: true, reset: ['blogComments'] },
    );
  }

  function openEdit(row: Row): void {
    editing = row;
    editOpen = true;
  }

  /** Comment bodies are stored as HTML; lists and headings show them as one line of text. */
  const plain = (html: string): string =>
    html
      .replace(/<[^>]*>/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
</script>

<AppHead title="Komentar Blog" />

<AdminPageHeader
  title="Komentar Blog"
  subtitle="Moderasi komentar dan balasan pada artikel blog."
/>

<DataTable
  data={blogComments}
  scrollKey="blogComments"
  {filters}
  url={index().url}
  columns={[
    { label: 'Komentar' },
    { label: 'Artikel' },
    { label: 'Penulis' },
    { label: 'Status', key: 'status', sortable: true },
    { label: 'Dikirim', key: 'created_at', sortable: true },
    { label: 'Aksi', align: 'end' },
  ]}
  emptyTitle="Belum ada komentar"
  emptyText="Komentar dari pembaca akan muncul di sini."
>
  {#snippet toolbar()}
    <Select
      inline
      ariaLabel="Filter status"
      placeholder="Semua status"
      items={statuses}
      value={filters.status || null}
      onchange={filterStatus}
    />
  {/snippet}
  {#snippet row(comment)}
    <tr>
      <td style="max-width: 20rem;">
        <div class="text-truncate">{plain(comment.body)}</div>
        <small class="opacity-75">
          {#if comment.parent}
            Balasan untuk #{comment.parent.id} ·
          {:else}
            Komentar utama ·
          {/if}
          {comment.replies_count} balasan
        </small>
      </td>
      <td>{comment.post.title}</td>
      <td>
        {#if comment.user}
          {comment.user.name}<br /><small class="opacity-75"
            >{comment.user.email}</small
          >
        {:else if comment.guest_name}
          {comment.guest_name}<br /><small class="opacity-75"
            >{comment.guest_email} · tamu</small
          >
        {:else}
          <em class="opacity-75">Pengguna terhapus</em>
        {/if}
      </td>
      <td>
        <span
          class="badge"
          class:text-bg-success={comment.status === 'approved'}
          class:text-bg-warning={comment.status === 'pending'}
          class:text-bg-danger={comment.status === 'rejected'}
        >
          {statusLabel(comment.status)}
        </span>
      </td>
      <td>{formatDate(comment.created_at)}</td>
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          <Link
            href={show(comment.id).url}
            class="btn btn-sm btn-outline-primary">Lihat</Link
          >
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            onclick={() => openEdit(comment)}>Ubah</button
          >
          <AdminDeleteButton
            href={destroy(comment.id).url}
            label={`Hapus komentar #${comment.id}?`}
            description="Balasan komentar ini tidak ikut dihapus, melainkan naik satu tingkat."
          />
        </div>
      </td>
    </tr>
  {/snippet}
</DataTable>

<BlogCommentEditModal bind:open={editOpen} comment={editing} {statuses} />
