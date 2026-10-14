<script lang="ts">
  import ShareIcon from '@lucide/svelte/icons/share-2';
  import { Link } from '@inertiajs/svelte';
  import ShareModal from '@/components/share-modal.svelte';
  import CalendarDays from '@lucide/svelte/icons/calendar-days';
  import { storageUrl } from '@/lib/utils';
  import type { BlogAuthorRef } from '@/components/blog/types';
  import { show as showAuthor } from '@/wayfinder/routes/blog/authors';

  let {
    author,
    publishedAt,
    title,
  }: {
    author: BlogAuthorRef | null;
    publishedAt: string | null;
    title: string;
  } = $props();

  const avatarUrl = $derived(storageUrl(author?.avatar));
  const initial = $derived(author?.name.trim().charAt(0).toUpperCase() || '?');

  let shareOpen = $state(false);
  let shareUrl = $state('');

  // Resolved on click so server and client markup stay identical.
  function openShare(): void {
    const { origin, pathname } = window.location;

    shareUrl = `${origin}${pathname}`;
    shareOpen = true;
  }

  const publishedLabel = $derived.by(() => {
    if (!publishedAt) {
      return 'Belum dipublikasikan';
    }

    const date = new Date(publishedAt);
    const day = date.toLocaleDateString('id-ID', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
    const time = date.toLocaleTimeString('id-ID', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
    });

    return `${day} | ${time.replace('.', ':')}`;
  });
</script>

<div class="post-meta">
  <div class="post-meta-author">
    {#if author}
      <span class="post-meta-avatar" aria-hidden="true">
        {#if avatarUrl}
          <img src={avatarUrl} alt="" width="48" height="48" decoding="async" />
        {:else}
          {initial}
        {/if}
      </span>
    {/if}

    <div class="post-meta-text">
      {#if author}
        <span class="post-meta-name">
          {#if author.slug}
            <Link href={showAuthor(author.slug).url}>{author.name}</Link>
          {:else}
            {author.name}
          {/if}
        </span>
      {/if}
      <time class="post-meta-date" datetime={publishedAt ?? undefined}>
        <CalendarDays size={14} aria-hidden="true" />
        {publishedLabel}
      </time>
    </div>
  </div>

  <button type="button" class="post-meta-share" onclick={openShare}>
    <ShareIcon size={16} aria-hidden="true" />
    Bagikan
  </button>
</div>

<ShareModal bind:open={shareOpen} url={shareUrl} {title} />

<style>
  .post-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
  }

  .post-meta-author {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    min-width: 0;
  }

  .post-meta-avatar {
    display: inline-flex;
    flex: none;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    overflow: hidden;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--neo-primary);
    background: var(--neo-primary-bg-subtle);
    border-radius: 50%;
    box-shadow: 0 0 0 2px var(--neo-body-bg), 0 0 0 3px var(--neo-border-color);
    user-select: none;
  }

  .post-meta-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .post-meta-text {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
    min-width: 0;
  }

  .post-meta-name {
    font-weight: 600;
    line-height: 1.25;
  }

  .post-meta-name :global(a) {
    color: inherit;
    text-decoration: none;
  }

  .post-meta-name :global(a:hover) {
    color: var(--neo-primary);
  }

  .post-meta-date {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.875rem;
    color: var(--neo-secondary-color);
  }

  .post-meta-share {
    display: inline-flex;
    flex: none;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--neo-body-color);
    background: transparent;
    border: 1px solid var(--neo-border-color);
    border-radius: 999px;
    transition:
      color 0.15s,
      border-color 0.15s,
      background-color 0.15s;
  }

  .post-meta-share:hover,
  .post-meta-share:focus-visible {
    color: var(--neo-primary);
    border-color: var(--neo-primary);
    background: var(--neo-primary-bg-subtle);
  }

</style>
