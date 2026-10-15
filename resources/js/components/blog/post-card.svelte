<script lang="ts">
  import Folder from '@lucide/svelte/icons/folder';
  import { Link } from '@inertiajs/svelte';
  import { show as showAuthor } from '@/wayfinder/routes/blog/authors';
  import { show as showCategory } from '@/wayfinder/routes/blog/categories';
  import { show as showPost } from '@/wayfinder/routes/blog';
  import type { BlogPostCard } from './types';

  let { post, eager = false }: { post: BlogPostCard; eager?: boolean } =
    $props();

  const category = $derived(post.categories[0]);
</script>

<!--
  The title link is stretched over the whole card; every other link sits above it
  (`position-relative`) so taxonomy links are never nested inside another anchor.
  Markup mirrors `card-blog.svelte` so every blog card looks identical.
-->
<div class="wrapper h-100">
  <article
    class="card h-100 overflow-hidden rounded-4 card-blog position-relative"
  >
    {#if post.coverImage}
      <div class="position-relative">
        <img
          loading={eager ? 'eager' : 'lazy'}
          fetchpriority={eager ? 'high' : undefined}
          decoding="async"
          src={post.coverImage}
          class="rounded-3 card-img-top"
          alt={post.title}
        />
        {#if category}
          <Link
            href={showCategory(category.slug).url}
            class="post-taxonomy-tag post-card-category position-absolute"
          >
            <Folder size={14} aria-hidden="true" />
            {category.name}
          </Link>
        {/if}
      </div>
    {/if}

    <div class="card-body p-4">
      <div class="d-flex gap-2 mb-2 justify-content-between">
        <h2 class="h5 card-title mb-0">
          <Link
            href={showPost(post.slug).url}
            class="stretched-link text-decoration-none"
          >
            {post.title}
          </Link>
        </h2>
      </div>

      {#if post.excerpt}
        <div class="card-text mb-3 opacity-75">{post.excerpt}</div>
      {/if}

      {#if post.author}
        <div class="small opacity-75">
          Oleh
          {#if post.author.username}
            <Link
              href={showAuthor(post.author.username).url}
              class="position-relative">{post.author.name}</Link
            >
          {:else}
            {post.author.name}
          {/if}
        </div>
      {/if}
    </div>
  </article>
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
