<script lang="ts">
  import { Link } from '@inertiajs/svelte';
  import Pencil from '@lucide/svelte/icons/pencil';
  import { show as showAuthor } from '@/wayfinder/routes/blog/authors';
  import { show as showCategory } from '@/wayfinder/routes/blog/categories';
  import { show as showPost } from '@/wayfinder/routes/blog';
  import { show as showTag } from '@/wayfinder/routes/blog/tags';
  import type { BlogPostCard } from './types';

  let { post }: { post: BlogPostCard } = $props();

  const publishedLabel = $derived(
    post.publishedAt
      ? new Date(post.publishedAt).toLocaleDateString('id-ID', {
          day: 'numeric',
          month: 'long',
          year: 'numeric',
        })
      : null,
  );
</script>

<!--
  The title link is stretched over the whole card; every other link sits above it
  (`position-relative`) so taxonomy links are never nested inside another anchor.
-->
<article
  class="card h-100 overflow-hidden rounded-4 card-blog position-relative"
>
  {#if post.coverImage}
    <img
      loading="lazy"
      src={post.coverImage}
      class="rounded-3 card-img-top"
      alt=""
      width="640"
      height="360"
    />
  {/if}

  <div class="card-body p-4 d-flex flex-column gap-2">
    {#if post.categories.length}
      <div class="d-flex flex-wrap gap-2">
        {#each post.categories as category (category.slug)}
          <Link
            href={showCategory(category.slug).url}
            class="badge text-decoration-none position-relative"
            style={`background-color: ${category.color ?? '#6c757d'}`}
          >
            {category.name}
          </Link>
        {/each}
      </div>
    {/if}

    <h2 class="h5 card-title mb-0">
      <Link
        href={showPost(post.slug).url}
        class="stretched-link text-decoration-none"
      >
        {post.title}
      </Link>
    </h2>

    {#if post.excerpt}
      <p class="card-text mb-0 opacity-75">{post.excerpt}</p>
    {/if}

    <div class="small opacity-75 d-flex flex-wrap gap-x-2">
      {#if publishedLabel}
        <time datetime={post.publishedAt ?? undefined}>{publishedLabel}</time>
      {/if}
      {#if post.author}
        <span>
          · Oleh
          {#if post.author.slug}
            <Link
              href={showAuthor(post.author.slug).url}
              class="position-relative">{post.author.name}</Link
            >
          {:else}
            {post.author.name}
          {/if}
        </span>
      {/if}
    </div>

    {#if post.tags.length}
      <div class="d-flex flex-wrap gap-2">
        {#each post.tags as tag (tag.slug)}
          <Link
            href={showTag(tag.slug).url}
            class="badge tag-badge text-decoration-none position-relative"
          >
            {tag.name}
          </Link>
        {/each}
      </div>
    {/if}

    {#if post.editUrl}
      <div class="mt-auto pt-2">
        <Link
          href={post.editUrl}
          class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 position-relative"
        >
          <Pencil size={14} aria-hidden="true" />
          Edit Artikel
          <span class="visually-hidden">: {post.title}</span>
        </Link>
      </div>
    {/if}
  </div>
</article>
