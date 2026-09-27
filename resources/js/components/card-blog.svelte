<script lang="ts">
  import { Link } from '@inertiajs/svelte';

  type Props = {
    title: string;
    slug: string;
    excerpt: string | null;
    coverImage: string | null;
    categories: { name: string; color: string | null }[];
    tags: string[];
  };

  let { title, slug, excerpt, coverImage, categories, tags }: Props = $props();

  const category = $derived(categories[0]);
</script>

<div class="wrapper">
  <Link href={`/blog/${slug}`} class="card h-100 overflow-hidden rounded-4 card-blog">
    {#if coverImage}
      <img loading="lazy" src={coverImage} class="rounded-3 card-img-top" alt={title} />
    {/if}

    <div class="card-body p-4">
      <div class="d-flex gap-2 mb-2 justify-content-between">
        <h5 class="card-title mb-0">{title}</h5>
        {#if category}
          <div>
            <span class="badge" style={`background-color: ${category.color ?? '#6c757d'}`}>
              {category.name}
            </span>
          </div>
        {/if}
      </div>
      {#if excerpt}
        <div class="card-text mb-3 opacity-75">{excerpt}</div>
      {/if}

      {#if tags.length}
        <div class="d-flex flex-wrap gap-2">
          {#each tags as tag (tag)}
            <span class="badge tag-badge">{tag}</span>
          {/each}
        </div>
      {/if}
    </div>
  </Link>
</div>
