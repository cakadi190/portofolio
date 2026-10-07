<script lang="ts">
  import { Form } from '@inertiajs/svelte';
  import Turnstile from '@/components/turnstile.svelte';
  import { Field } from '@/components/ui/field';
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

  const postedAt = $derived(
    new Date(comment.createdAt).toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }),
  );
</script>

<li class="blog-comment" id={`komentar-${comment.id}`}>
  <div class="card rounded-4 mb-3">
    <div class="card-body">
      <div
        class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2"
      >
        <strong>{comment.author}</strong>
        <small class="opacity-75">{postedAt}</small>
      </div>

      <p class="mb-3" style="white-space: pre-line; overflow-wrap: anywhere;">
        {comment.body}
      </p>

      {#if canComment}
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary"
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
              <Field.Label for={`reply-${comment.id}`}
                >Balas {comment.author}</Field.Label
              >
              <textarea
                id={`reply-${comment.id}`}
                name="body"
                rows="3"
                maxlength="2000"
                required
                class="form-control"
                class:is-invalid={!!errors.body || !!errors.parent_id}
              ></textarea>
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
    <ul
      class="list-unstyled mb-0"
      class:ms-2={depth < MAX_VISUAL_DEPTH}
      class:ms-md-4={depth < MAX_VISUAL_DEPTH}
      class:ps-2={depth < MAX_VISUAL_DEPTH}
      class:ps-md-3={depth < MAX_VISUAL_DEPTH}
      class:border-start={depth < MAX_VISUAL_DEPTH}
    >
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
