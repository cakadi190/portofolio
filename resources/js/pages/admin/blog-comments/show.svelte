<script lang="ts">
  import { t } from '@/lib/i18n';
  import { Link } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import BlogCommentEditModal from '@/components/admin/blog-comment-edit-modal.svelte';
  import { formatDate } from '@/lib/utils';
  import { destroy, index, show } from '@/wayfinder/routes/admin/blog-comments';

  type Option = { value: string; label: string };

  type Node = {
    id: number;
    parent_id: number | null;
    body: string;
    status: string;
    depth: number;
    created_at: string;
    guest_name: string | null;
    guest_email: string | null;
    user: { id: number; name: string; email: string } | null;
    post: { id: number; title: string; slug: string } | null;
  };

  let {
    blogComment,
    ancestors,
    descendants,
    statuses,
  }: {
    blogComment: Node;
    ancestors: Node[];
    descendants: Node[];
    statuses: Option[];
  } = $props();

  let editOpen = $state(false);
  let editing = $state<Node | null>(null);

  const statusLabel = (value: string): string =>
    statuses.find((status) => status.value === value)?.label ?? value;

  function openEdit(node: Node): void {
    editing = node;
    editOpen = true;
  }

  /** Comment bodies are stored as HTML; lists and headings show them as one line of text. */
  const plain = (html: string): string =>
    html
      .replace(/<[^>]*>/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
</script>

{#snippet author(node: Node)}
  <strong>{node.user?.name ?? node.guest_name ?? t('admin-blog-comments.pengguna_terhapus')}</strong>
  {#if node.user}<small class="opacity-75">
      · {node.user.email}</small
    >{:else if node.guest_email}<small class="opacity-75">
      · {node.guest_email} · tamu</small
    >{/if}
  <small class="opacity-75"> · {formatDate(node.created_at)}</small>
  <span
    class="badge ms-1"
    class:text-bg-success={node.status === 'approved'}
    class:text-bg-warning={node.status === 'pending'}
    class:text-bg-danger={node.status === 'rejected'}
    >{statusLabel(node.status)}</span
  >
{/snippet}

<AppHead title={`Komentar #${blogComment.id}`} />

<AdminPageHeader
  title={`Komentar #${blogComment.id}`}
  subtitle={t('admin-blog-comments.lihat_posisi_komentar_ini_di_dalam')}
/>

<div class="mb-4">
  <Link href={index().url} class="btn btn-sm btn-outline-secondary"
    >&larr; Semua komentar</Link
  >
</div>

{#if blogComment.post}
  <p class="mb-4">
    {t('admin-blog-comments.artikel')} <a
      href={`/blog/${blogComment.post.slug}`}
      target="_blank"
      rel="noopener">{blogComment.post.title}</a
    >
  </p>
{/if}

{#if ancestors.length}
  <h2 class="h6 text-muted">{t('admin-blog-comments.komentar_yang_dibalas')}</h2>
  <ul class="list-unstyled mb-4">
    {#each ancestors as ancestor, level (ancestor.id)}
      <li class="card mb-2" style={`margin-left: ${level * 1.25}rem;`}>
        <div class="card-body py-2">
          <div>{@render author(ancestor)}</div>
          <div class="text-truncate">{plain(ancestor.body)}</div>
          <Link href={show(ancestor.id).url} class="small">{t('admin.buka')}</Link>
        </div>
      </li>
    {/each}
  </ul>
{/if}

<div class="card border-primary mb-4">
  <div class="card-body">
    <div class="mb-2">{@render author(blogComment)}</div>
    <div class="mb-3" style="overflow-wrap: anywhere;">
      {@html blogComment.body}
    </div>
    <div class="d-flex gap-2">
      <button
        type="button"
        class="btn btn-sm btn-outline-secondary"
        onclick={() => openEdit(blogComment)}>{t('admin.ubah')}</button
      >
      <AdminDeleteButton
        href={destroy(blogComment.id).url}
        label={t('admin-blog-comments.hapus_komentar', { id: blogComment.id })}
        description={t('admin-blog-comments.balasan_komentar_ini_tidak_ikut_dihapus')}
      />
    </div>
  </div>
</div>

<h2 class="h6 text-muted">Balasan ({descendants.length})</h2>
{#if descendants.length}
  <ul class="list-unstyled">
    {#each descendants as reply (reply.id)}
      <li
        class="card mb-2"
        style={`margin-left: ${Math.min(reply.depth - 1, 5) * 1.25}rem;`}
      >
        <div class="card-body py-2">
          <div>{@render author(reply)}</div>
          <div style="overflow-wrap: anywhere;">{@html reply.body}</div>
          <div class="d-flex gap-3 mt-1">
            <Link href={show(reply.id).url} class="small">{t('admin.buka')}</Link>
            <button
              type="button"
              class="btn btn-link btn-sm p-0"
              onclick={() => openEdit(reply)}>{t('admin.ubah')}</button
            >
          </div>
        </div>
      </li>
    {/each}
  </ul>
{:else}
  <p class="opacity-75">{t('admin-blog-comments.belum_ada_balasan')}</p>
{/if}

<BlogCommentEditModal bind:open={editOpen} comment={editing} {statuses} />
