<script lang="ts">
  import { Form } from '@inertiajs/svelte';
  import Pencil from '@lucide/svelte/icons/pencil';
  import Reply from '@lucide/svelte/icons/reply';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
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
  const initial = $derived(author.trim().charAt(0).toUpperCase() || '?');
  const isReplying = $derived(replyingTo === entry.id);
</script>

<li id={`pesan-${entry.id}`} class="gb-node">
  <div class="gb-card" class:gb-card-muted={entry.status !== 'approved'}>
    <span
      class="gb-avatar"
      class:gb-avatar-staff={!!entry.user}
      aria-hidden="true">{initial}</span
    >

    <div class="flex-grow-1 min-w-0">
      <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
        <strong>{author}</strong>
        {#if entry.guest_email}
          <small class="opacity-75">{entry.guest_email} · tamu</small>
        {:else if entry.user}
          <small class="opacity-75">{entry.user.email}</small>
        {/if}
        <span
          class="badge"
          class:text-bg-success={entry.status === 'approved'}
          class:text-bg-warning={entry.status === 'pending'}
          class:text-bg-danger={entry.status === 'rejected'}
        >
          {statusLabel(entry.status)}
        </span>
        <small class="opacity-75 ms-md-auto"
          >{formatDate(entry.created_at)}</small
        >
      </div>

      <!-- The server whitelists this markup (HtmlSanitizer::richText) before sending it. -->
      <div class="gb-body mb-2">{@html entry.body}</div>

      <div class="d-flex flex-wrap gap-2">
        <button
          type="button"
          class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
          aria-expanded={isReplying}
          onclick={() => onReply(isReplying ? null : entry.id)}
        >
          <Reply size={14} aria-hidden="true" />
          {isReplying ? 'Batal' : 'Balas'}
        </button>
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
          onclick={() => onEdit(entry)}
        >
          <Pencil size={14} aria-hidden="true" /> Ubah
        </button>
        <AdminDeleteButton
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
    <ul
      class="list-unstyled mb-0 gb-replies"
      class:gb-indent={depth < MAX_VISUAL_DEPTH}
    >
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

<style>
  .min-w-0 {
    min-width: 0;
  }

  .gb-card {
    display: flex;
    gap: 0.75rem;
    margin-top: 0.75rem;
    padding: 0.875rem 1rem;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 1rem;
  }

  .gb-card-muted {
    background: var(--bs-tertiary-bg);
  }

  .gb-avatar {
    display: inline-flex;
    flex: 0 0 2.25rem;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    font-weight: 700;
    color: var(--bs-secondary-color);
    background: var(--bs-secondary-bg);
    border-radius: 50%;
  }

  .gb-avatar-staff {
    color: #fff;
    background: #16a34a;
  }

  .gb-body {
    overflow-wrap: anywhere;
  }

  .gb-replies {
    position: relative;
  }

  .gb-indent {
    margin-left: 1.125rem;
    padding-left: 1rem;
    border-left: 2px solid var(--bs-border-color);
  }
</style>
