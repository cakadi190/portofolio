<script lang="ts">
  import { Form, page } from '@inertiajs/svelte';
  import ChevronDown from '@lucide/svelte/icons/chevron-down';
  import ThreadAvatar from '@/components/thread-avatar.svelte';
  import CaptchaSubmitRow from '@/components/captcha-submit-row.svelte';
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
    replyingTo,
    onReply,
  }: {
    comment: BlogCommentNode;
    postSlug: string;
    depth?: number;
    replyingTo: number | null;
    onReply: (id: number | null) => void;
  } = $props();

  let collapsed = $state(false);

  const isGuest = $derived(!page.props.auth?.user);

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

      <button
        type="button"
        class="thread-action"
        aria-expanded={replyingTo === comment.id}
        onclick={() => onReply(replyingTo === comment.id ? null : comment.id)}
      >
        {replyingTo === comment.id ? 'Batal' : 'Balas'}
      </button>

      {#if replyingTo === comment.id}
        <Form
          {...store.form(postSlug)}
          class="d-flex flex-column gap-3 mt-3"
          resetOnSuccess
          onSuccess={() => onReply(null)}
        >
          {#snippet children({ errors, processing })}
            <input type="hidden" name="parent_id" value={comment.id} />
            {#if isGuest}
              <div class="row g-3">
                <Field.Group class="col-md-6">
                  <Field.Label for={`reply-guest-name-${comment.id}`}
                    >Nama</Field.Label
                  >
                  <Field.Input
                    id={`reply-guest-name-${comment.id}`}
                    name="guest_name"
                    invalid={!!errors.guest_name}
                  />
                  <Field.Feedback message={errors.guest_name} />
                </Field.Group>
                <Field.Group class="col-md-6">
                  <Field.Label for={`reply-guest-email-${comment.id}`}
                    >Email</Field.Label
                  >
                  <Field.Input
                    id={`reply-guest-email-${comment.id}`}
                    name="guest_email"
                    type="email"
                    invalid={!!errors.guest_email}
                  />
                  <Field.Feedback message={errors.guest_email} />
                </Field.Group>
              </div>
            {/if}
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
            <CaptchaSubmitRow error={errors['cf-turnstile-response']}>
              <button
                type="submit"
                class="btn btn-primary btn-sm"
                disabled={processing}>Kirim Balasan</button
              >
            </CaptchaSubmitRow>
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
          {replyingTo}
          {onReply}
        />
      {/each}
    </ul>
  {/if}
</li>
