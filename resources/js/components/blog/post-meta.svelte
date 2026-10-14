<script lang="ts">
  import ShareIcon from '@lucide/svelte/icons/share-2';
  import { Link } from '@inertiajs/svelte';
  import ShareModal from '@/components/share-modal.svelte';
  import ThreadAvatar from '@/components/thread-avatar.svelte';
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

<div class="post-meta d-flex align-items-center justify-content-between gap-3">
  <div class="d-flex align-items-center gap-3 min-w-0">
    {#if author}
      <ThreadAvatar name={author.name} src={author.avatar} />
    {/if}
    <div class="lh-sm">
      {#if author}
        <div class="fw-semibold">
          {#if author.slug}
            <Link href={showAuthor(author.slug).url}>{author.name}</Link>
          {:else}
            {author.name}
          {/if}
        </div>
      {/if}
      <time class="small opacity-75" datetime={publishedAt ?? undefined}
        >{publishedLabel}</time
      >
    </div>
  </div>

  <button
    type="button"
    class="btn btn-link text-body fw-semibold text-decoration-none d-inline-flex align-items-center gap-2 p-0 flex-none"
    onclick={openShare}
  >
    Bagikan
    <ShareIcon size={16} aria-hidden="true" />
  </button>
</div>

<ShareModal bind:open={shareOpen} url={shareUrl} {title} />
