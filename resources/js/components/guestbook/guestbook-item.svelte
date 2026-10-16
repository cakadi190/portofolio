<script lang="ts">
  import { t, tChoice, intlLocale } from '@/lib/i18n';
  import ChevronDown from '@lucide/svelte/icons/chevron-down';
  import ThreadAvatar from '@/components/thread-avatar.svelte';
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

  let collapsed = $state(false);

  const postedAt = $derived(
    new Date(entry.createdAt).toLocaleDateString(intlLocale(), {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }),
  );
</script>

<li id={`pesan-${entry.id}`} class="thread-node">
  <div class="thread-card">
    <ThreadAvatar name={entry.author} src={entry.authorAvatar} />
    <div class="flex-grow-1" style="min-width: 0">
      <div class="thread-meta">
        <strong>{entry.author}</strong>
        <small>{postedAt}</small>
      </div>

      <!-- The server whitelists this markup (HtmlSanitizer::richText) on write and again on read. -->
      <div class="thread-body">
        {@html entry.body}
      </div>

      <button
          type="button"
          class="thread-action"
          aria-expanded={replyingTo === entry.id}
          onclick={() => onReply(replyingTo === entry.id ? null : entry.id)}
        >
          {replyingTo === entry.id ? t('thread.cancel') : t('thread.reply')}
      </button>

      {#if replyingTo === entry.id}
        <div class="mt-3">
          <GuestbookForm
            parentId={entry.id}
            label={t('thread.reply_to', { name: entry.author })}
            submitLabel={t('thread.send_reply')}
            placeholder={t('thread.reply_placeholder')}
            onSuccess={() => onReply(null)}
          />
        </div>
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
      {collapsed ? t('thread.show') : t('thread.hide')}
      {tChoice('thread.replies', entry.replies.length)}
    </button>
  {/if}

  {#if entry.replies.length && !collapsed}
    <ul class="thread-children" class:is-flat={depth >= MAX_VISUAL_DEPTH - 1}>
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
