<script lang="ts">
  import Folder from '@lucide/svelte/icons/folder';
  import Link from '@/components/link.svelte';

  type Props = {
    title: string;
    slug: string;
    excerpt: string | null;
    coverImage: string | null;
    categories: { name: string; color: string | null }[];
    tags?: string[];
    author?: string | null;
    href?: string;
    /** First card of a list: loaded eagerly with high priority (LCP candidate). */
    eager?: boolean;
  };

  let {
    title,
    slug,
    excerpt,
    coverImage,
    categories,
    author = null,
    href = `/blog/${slug}`,
    eager = false,
  }: Props = $props();

  const category = $derived(categories[0]);
</script>

<div class="wrapper">
  <Link {href} class="card h-100 overflow-hidden rounded-4 card-blog">
    {#if coverImage}
      <div class="position-relative">
        <img
          loading={eager ? 'eager' : 'lazy'}
          fetchpriority={eager ? 'high' : undefined}
          decoding="async"
          src={coverImage}
          class="rounded-3 card-img-top"
          alt={title}
        />
        {#if category}
          <span class="post-taxonomy-tag post-card-category position-absolute">
            <Folder size={14} aria-hidden="true" />
            {category.name}
          </span>
        {/if}
      </div>
    {/if}

    <div class="card-body p-4">
      <div class="d-flex gap-2 mb-2 justify-content-between">
        <h2 class="card-title h5 mb-0">{title}</h2>
      </div>
      {#if excerpt}
        <div class="card-text mb-3 opacity-75">{excerpt}</div>
      {/if}

      {#if author}
        <div class="small opacity-75">Oleh {author}</div>
      {/if}
    </div>
  </Link>
</div>

<style>
  :global(.post-card-category) {
    right: 0.75rem;
    bottom: 0.75rem;
    z-index: 2;
    white-space: nowrap;
    background: var(--neo-body-bg);
  }
</style>
