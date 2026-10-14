<script lang="ts">
  import { Form } from '@inertiajs/svelte';
  import ChevronDown from '@lucide/svelte/icons/chevron-down';
  import Pencil from '@lucide/svelte/icons/pencil';
  import Reply from '@lucide/svelte/icons/reply';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import ThreadAvatar from '@/components/thread-avatar.svelte';
  import GuestbookThreadNode from '@/components/admin/guestbook-thread-node.svelte';
  import RichTextEditor from '@/components/ui/rich-text-editor.svelte';
  import { Field } from '@/components/ui/field';
  import { formatDate } from '@/lib/utils';
  import { destroy, reply } from '@/wayfinder/routes/admin/guestbook-entries';
  import type { GuestbookAdminNode } from '@/components/admin/guestbook-thread';

  /** Replies keep nesting in data, but stop indenting past this depth. */
  const MAX_VISUAL_DEPTH = 4;

  let {
    entry,
    depth = 0,
    statusLabel,
    replyingTo,
    onReply,
    onEdit,
  }: {
    entry: GuestbookAdminNode;
    depth?: number;
    statusLabel: (value: string) => string;
    replyingTo: number | null;
    onReply: (id: number | null) => void;
    onEdit: (entry: GuestbookAdminNode) => void;
  } = $props();

  const author = $derived(
    entry.user?.name ?? entry.guest_name ?? 'Pengguna terhapus',
  );
  const isReplying = $derived(replyingTo === entry.id);
  let collapsed = $state(false);
</script>

<li id={`pesan-${entry.id}`} class="thread-node">
  <div class="thread-card" class:is-muted={entry.status !== 'approved'}>
    <ThreadAvatar name={author} src={entry.user?.avatar} isStaff={!!entry.user} />

    <div class="flex-grow-1" style="min-width: 0">
      <div class="thread-meta">
        <strong>{author}</strong>
        {#if entry.guest_email}
          <small>{entry.guest_email} · tamu</small>
        {:else if entry.user}
          <small>{entry.user.email}</small>
        {/if}
        <span
          class="badge"
          class:text-bg-success={entry.status === 'approved'}
          class:text-bg-warning={entry.status === 'pending'}
          class:text-bg-danger={entry.status === 'rejected'}
        >
          {statusLabel(entry.status)}
        </span>
        <small>{formatDate(entry.created_at)}</small>
      </div>

      <!-- The server whitelists this markup (HtmlSanitizer::richText) before sending it. -->
      <div class="thread-body">{@html entry.body}</div>

      <div class="thread-actions">
        <button
          type="button"
          class="thread-action"
          aria-expanded={isReplying}
          onclick={() => onReply(isReplying ? null : entry.id)}
        >
          <Reply size={14} aria-hidden="true" />
          {isReplying ? 'Batal' : 'Balas'}
        </button>
        <button
          type="button"
          class="thread-action"
          onclick={() => onEdit(entry)}
        >
          <Pencil size={14} aria-hidden="true" /> Ubah
        </button>
        <AdminDeleteButton
          class="thread-action is-danger"
          href={destroy(entry.id).url}
          label={`Hapus pesan #${entry.id}?`}
          description="Balasan pesan ini tidak ikut dihapus, melainkan naik satu tingkat."
        />
      </div>

      {#if isReplying}
        <Form
          {...reply.form(entry.id)}
          class="mt-3 d-flex flex-column gap-2"
          resetOnSuccess
          onSuccess={() => onReply(null)}
        >
          {#snippet children({ errors, processing })}
            <Field.Group>
              <Field.Label>Balasan untuk {author}</Field.Label>
              <RichTextEditor
                name="body"
                minimal
                placeholder={`Balas ${author}…`}
                invalid={!!errors.body}
              />
              <Field.Feedback message={errors.body} />
            </Field.Group>
            <div class="d-flex justify-content-end">
              <button
                type="submit"
                class="btn btn-sm btn-primary"
                disabled={processing}>Kirim Balasan</button
              >
            </div>
          {/snippet}
        </Form>
      {/if}
    </div>
  </div>

  {#if entry.replies.length}
    <button
      type="button"
      class="thread-toggle"
      aria-expanded={!collapsed}
      onclick={() => (collapsed = !collapsed)}
    >
      <ChevronDown
        size={14}
        aria-hidden="true"
        style={collapsed ? 'transform: rotate(-90deg)' : ''}
      />
      {collapsed ? 'Tampilkan' : 'Sembunyikan'}
      {entry.replies.length} balasan
    </button>
  {/if}

  {#if entry.replies.length && !collapsed}
    <ul class="thread-children" class:is-flat={depth >= MAX_VISUAL_DEPTH - 1}>
      {#each entry.replies as child (child.id)}
        <GuestbookThreadNode
          entry={child}
          depth={depth + 1}
          {statusLabel}
          {replyingTo}
          {onReply}
          {onEdit}
        />
      {/each}
    </ul>
  {/if}
</li>
