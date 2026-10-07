<script lang="ts">
  import { Link } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import BlogCommentEditModal from '@/components/admin/blog-comment-edit-modal.svelte';
  import { formatDate } from '@/lib/utils';
  import {
    destroy,
    index,
    show,
  } from '@/wayfinder/routes/admin/blog-comments';

  type Option = { value: string; label: string };

  type Node = {
    id: number;
    parent_id: number | null;
    body: string;
    status: string;
    depth: number;
    created_at: string;
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
</script>

{#snippet author(node: Node)}
  <strong>{node.user?.name ?? 'Pengguna terhapus'}</strong>
  {#if node.user}<small class="opacity-75"> · {node.user.email}</small>{/if}
  <small class="opacity-75"> · {formatDate(node.created_at)}</small>
  <span
    class="badge ms-1"
    class:text-bg-success={node.status === 'approved'}
    class:text-bg-warning={node.status === 'pending'}
    class:text-bg-danger={node.status === 'rejected'}>{statusLabel(node.status)}</span
  >
{/snippet}

<AppHead title={`Komentar #${blogComment.id}`} />

<AdminPageHeader
  title={`Komentar #${blogComment.id}`}
  subtitle="Lihat posisi komentar ini di dalam percakapan."
/>

<div class="mb-4">
  <Link href={index().url} class="btn btn-sm btn-outline-secondary"
    >&larr; Semua komentar</Link
  >
</div>

{#if blogComment.post}
  <p class="mb-4">
    Artikel: <a href={`/blog/${blogComment.post.slug}`} target="_blank" rel="noopener"
      >{blogComment.post.title}</a
    >
  </p>
{/if}

{#if ancestors.length}
  <h2 class="h6 text-muted">Komentar yang dibalas</h2>
  <ul class="list-unstyled mb-4">
    {#each ancestors as ancestor, level (ancestor.id)}
      <li class="card mb-2" style={`margin-left: ${level * 1.25}rem;`}>
        <div class="card-body py-2">
          <div>{@render author(ancestor)}</div>
          <div class="text-truncate">{ancestor.body}</div>
          <Link href={show(ancestor.id).url} class="small">Buka</Link>
        </div>
      </li>
    {/each}
  </ul>
{/if}

<div class="card border-primary mb-4">
  <div class="card-body">
    <div class="mb-2">{@render author(blogComment)}</div>
    <p class="mb-3" style="white-space: pre-line; overflow-wrap: anywhere;">
      {blogComment.body}
    </p>
    <div class="d-flex gap-2">
      <button
        type="button"
        class="btn btn-sm btn-outline-secondary"
        onclick={() => openEdit(blogComment)}>Ubah</button
      >
      <AdminDeleteButton
        href={destroy(blogComment.id).url}
        label={`Hapus komentar #${blogComment.id}?`}
        description="Balasan komentar ini tidak ikut dihapus, melainkan naik satu tingkat."
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
          <div style="white-space: pre-line; overflow-wrap: anywhere;">{reply.body}</div>
          <div class="d-flex gap-3 mt-1">
            <Link href={show(reply.id).url} class="small">Buka</Link>
            <button
              type="button"
              class="btn btn-link btn-sm p-0"
              onclick={() => openEdit(reply)}>Ubah</button
            >
          </div>
        </div>
      </li>
    {/each}
  </ul>
{:else}
  <p class="opacity-75">Belum ada balasan.</p>
{/if}

<BlogCommentEditModal bind:open={editOpen} comment={editing} {statuses} />
