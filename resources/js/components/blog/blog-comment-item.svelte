<script lang="ts">
  import { Form } from '@inertiajs/svelte';
  import ChevronDown from '@lucide/svelte/icons/chevron-down';
  import ThreadAvatar from '@/components/thread-avatar.svelte';
  import Turnstile from '@/components/turnstile.svelte';
  import { Field } from '@/components/ui/field';
  import RichTextEditor from '@/components/ui/lazy-rich-text-editor.svelte';
  import { store } from '@/wayfinder/routes/blog/comments';
  import BlogCommentItem from './blog-comment-item.svelte';
  import type { BlogCommentNode } from './types';

  /** Replies keep nesting in data, but stop indenting past this depth. */
  const MAX_VISUAL_DEPTH = 4;

  let {
    comment,
    postSlug,
    depth = 0,
    canComment,
    replyingTo,
    onReply,
  }: {
    comment: BlogCommentNode;
    postSlug: string;
    depth?: number;
    canComment: boolean;
    replyingTo: number | null;
    onReply: (id: number | null) => void;
  } = $props();

  let collapsed = $state(false);

  const postedAt = $derived(
    new Date(comment.createdAt).toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }),
  );
</script>

<li class="thread-node" id={`komentar-${comment.id}`}>
  <div class="thread-card">
    <ThreadAvatar name={comment.author} src={comment.authorAvatar} />
    <div class="flex-grow-1" style="min-width: 0">
      <div class="thread-meta">
        <strong>{comment.author}</strong>
        <small>{postedAt}</small>
      </div>

      <div class="thread-body">
        {@html comment.body}
      </div>

      {#if canComment}
        <button
          type="button"
          class="thread-action"
          aria-expanded={replyingTo === comment.id}
          onclick={() => onReply(replyingTo === comment.id ? null : comment.id)}
        >
          {replyingTo === comment.id ? 'Batal' : 'Balas'}
        </button>
      {/if}

      {#if replyingTo === comment.id}
        <Form
          {...store.form(postSlug)}
          class="d-flex flex-column gap-3 mt-3"
          resetOnSuccess
          onSuccess={() => onReply(null)}
        >
          {#snippet children({ errors, processing })}
            <input type="hidden" name="parent_id" value={comment.id} />
            <Field.Group>
              <Field.Label>Balas {comment.author}</Field.Label>
              <RichTextEditor
                name="body"
                minimal
                placeholder="Tulis balasan..."
                invalid={!!errors.body || !!errors.parent_id}
              />
              <Field.Feedback message={errors.body ?? errors.parent_id} />
            </Field.Group>
            <Turnstile error={errors['cf-turnstile-response']} />
            <div>
              <button
                type="submit"
                class="btn btn-primary btn-sm"
                disabled={processing}>Kirim Balasan</button
              >
            </div>
          {/snippet}
        </Form>
      {/if}
    </div>
  </div>

  {#if comment.replies.length}
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
      {comment.replies.length} balasan
    </button>
  {/if}

  {#if comment.replies.length && !collapsed}
    <ul class="thread-children" class:is-flat={depth >= MAX_VISUAL_DEPTH - 1}>
      {#each comment.replies as reply (reply.id)}
        <BlogCommentItem
          comment={reply}
          {postSlug}
          depth={depth + 1}
          {canComment}
          {replyingTo}
          {onReply}
        />
      {/each}
    </ul>
  {/if}
</li>
