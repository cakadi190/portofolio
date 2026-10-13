<script lang="ts">
  import { Form } from '@inertiajs/svelte';
  import Turnstile from '@/components/turnstile.svelte';
  import { Field } from '@/components/ui/field';
  import { store } from '@/wayfinder/routes/guestbook';
  import GuestbookItem from './guestbook-item.svelte';
  import type { GuestbookNode } from './types';

  /** Replies keep nesting in data, but stop indenting past this depth. */
  const MAX_VISUAL_DEPTH = 4;

  let {
    entry,
    depth = 0,
    canWrite,
    replyingTo,
    onReply,
  }: {
    entry: GuestbookNode;
    depth?: number;
    canWrite: boolean;
    replyingTo: number | null;
    onReply: (id: number | null) => void;
  } = $props();

  const postedAt = $derived(
    new Date(entry.createdAt).toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }),
  );
</script>

<li id={`pesan-${entry.id}`}>
  <div class="card rounded-4 mb-3">
    <div class="card-body">
      <div
        class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2"
      >
        <strong>{entry.author}</strong>
        <small class="opacity-75">{postedAt}</small>
      </div>

      <p class="mb-3" style="white-space: pre-line; overflow-wrap: anywhere;">
        {entry.body}
      </p>

      {#if canWrite}
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary"
          aria-expanded={replyingTo === entry.id}
          onclick={() => onReply(replyingTo === entry.id ? null : entry.id)}
        >
          {replyingTo === entry.id ? 'Batal' : 'Balas'}
        </button>
      {/if}

      {#if replyingTo === entry.id}
        <Form
          {...store.form()}
          class="d-flex flex-column gap-3 mt-3"
          resetOnSuccess
          onSuccess={() => onReply(null)}
        >
          {#snippet children({ errors, processing })}
            <input type="hidden" name="parent_id" value={entry.id} />
            <Field.Group>
              <Field.Label for={`reply-${entry.id}`}
                >Balas {entry.author}</Field.Label
              >
              <textarea
                id={`reply-${entry.id}`}
                name="body"
                rows="3"
                maxlength="1000"
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

  {#if entry.replies.length}
    <ul
      class="list-unstyled mb-0"
      class:ms-2={depth < MAX_VISUAL_DEPTH}
      class:ms-md-4={depth < MAX_VISUAL_DEPTH}
      class:ps-2={depth < MAX_VISUAL_DEPTH}
      class:ps-md-3={depth < MAX_VISUAL_DEPTH}
      class:border-start={depth < MAX_VISUAL_DEPTH}
    >
      {#each entry.replies as reply (reply.id)}
        <GuestbookItem
          entry={reply}
          depth={depth + 1}
          {canWrite}
          {replyingTo}
          {onReply}
        />
      {/each}
    </ul>
  {/if}
</li>
