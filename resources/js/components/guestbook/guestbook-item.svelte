<script lang="ts">
  import GuestbookForm from './guestbook-form.svelte';
  import GuestbookItem from './guestbook-item.svelte';
  import type { GuestbookNode } from './types';

  /** Replies keep nesting in data, but stop indenting past this depth. */
  const MAX_VISUAL_DEPTH = 4;

  let {
    entry,
    depth = 0,
    replyingTo,
    onReply,
  }: {
    entry: GuestbookNode;
    depth?: number;
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

      <!-- The server whitelists this markup (HtmlSanitizer::richText) on write and again on read. -->
      <div class="guestbook-body mb-3" style="overflow-wrap: anywhere;">
        {@html entry.body}
      </div>

      <button
          type="button"
          class="btn btn-sm btn-outline-secondary"
          aria-expanded={replyingTo === entry.id}
          onclick={() => onReply(replyingTo === entry.id ? null : entry.id)}
        >
          {replyingTo === entry.id ? 'Batal' : 'Balas'}
      </button>

      {#if replyingTo === entry.id}
        <div class="mt-3">
          <GuestbookForm
            parentId={entry.id}
            label={`Balas ${entry.author}`}
            submitLabel="Kirim Balasan"
            placeholder="Tulis balasan…"
            onSuccess={() => onReply(null)}
          />
        </div>
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
          {replyingTo}
          {onReply}
        />
      {/each}
    </ul>
  {/if}
</li>
