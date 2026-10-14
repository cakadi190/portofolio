<script lang="ts">
  import { Link } from '@inertiajs/svelte';
  import { show as showAuthor } from '@/wayfinder/routes/blog/authors';
  import { show as showCategory } from '@/wayfinder/routes/blog/categories';
  import { show as showPost } from '@/wayfinder/routes/blog';
  import { show as showTag } from '@/wayfinder/routes/blog/tags';
  import type { BlogPostCard } from './types';

  let { post }: { post: BlogPostCard } = $props();

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
      <img
        loading="lazy"
        src={post.coverImage}
        class="rounded-3 card-img-top"
        alt={post.title}
      />
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
        {#if category}
          <div>
            <Link
              href={showCategory(category.slug).url}
              class="badge text-decoration-none position-relative"
              style={`background-color: ${category.color ?? '#6c757d'}`}
            >
              {category.name}
            </Link>
          </div>
        {/if}
      </div>

      {#if post.excerpt}
        <div class="card-text mb-3 opacity-75">{post.excerpt}</div>
      {/if}

      {#if post.author}
        <div class="small opacity-75 mb-3">
          Oleh
          {#if post.author.slug}
            <Link
              href={showAuthor(post.author.slug).url}
              class="position-relative">{post.author.name}</Link
            >
          {:else}
            {post.author.name}
          {/if}
        </div>
      {/if}

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
    </div>
  </article>
</div>
