<script lang="ts">
  import ArrowRight from '@lucide/svelte/icons/arrow-right';
  import Link from '@/components/link.svelte';
  import CardBlog from '@/components/card-blog.svelte';
  import { t } from '@/lib/i18n';

  type Post = {
    title: string;
    slug: string;
    excerpt: string | null;
    coverImage: string | null;
    categories: { name: string; color: string | null }[];
    tags: string[];
    author: string | null;
  };

  let { posts = [] }: { posts?: Post[] } = $props();
</script>

<section class="need-space" id="blog-home-section">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
      <div>
        <h2 class="mb-1">{t('home.blog.title')}</h2>
        <p class="mb-0 opacity-75">{t('home.blog.subtitle')}</p>
      </div>
      <Link href="/blog" class="btn btn-outline-primary d-flex align-items-center gap-2" aria-label={`${t('home.more')}: ${t('home.blog.title')}`}>
        <span>{t('home.more')}</span>
        <ArrowRight size={16} />
      </Link>
    </div>

    {#if posts.length}
      <div class="row">
        {#each posts as post (post.slug)}
          <div class="col-md-6 col-lg-4 mb-4">
            <CardBlog {...post} />
          </div>
        {/each}
      </div>
    {:else}
      <div class="text-center opacity-75">{t('home.blog.empty')}</div>
    {/if}
  </div>
</section>
