<script lang="ts">
  import { t } from '@/lib/i18n';
  import AppHead from '@/components/app-head.svelte';
  import PostCard from '@/components/blog/post-card.svelte';
  import type { BlogPostCard } from '@/components/blog/types';
  import EmptyState from '@/components/empty-state.svelte';
  import HeaderPage from '@/components/header-page.svelte';
  import InfiniteFeed from '@/components/infinite-feed.svelte';
  import type { InfiniteList } from '@/types/pagination';

  let {
    posts,
    activeCategory = null,
  }: {
    posts: InfiniteList<BlogPostCard>;
    activeCategory?: { id: number; name: string } | null;
  } = $props();
</script>

<AppHead title={t('blog.title')} />

<div id="articles-page">
  <HeaderPage
    title={t('blog.title')}
    subtitle={activeCategory
      ? t('blog.subtitle_category', { name: activeCategory.name })
      : t('blog.subtitle')}
  />

  <section class="need-space pt-0">
    <div class="container">
      <div class="row">
        {#if posts.data.length === 0}
          <EmptyState
            title={t('empty.no_articles_title')}
            text={t('blog.empty_text')}
          />
        {:else}
          <div class="col-md-12 mx-auto">
            <InfiniteFeed data="posts">
              <div class="row">
                {#each posts.data as post, index (post.slug)}
                  <div class="col-md-6 col-lg-4 mb-4">
                    <PostCard {post} eager={index === 0} />
                  </div>
                {/each}
              </div>
            </InfiniteFeed>
          </div>
        {/if}
      </div>
    </div>
  </section>
</div>
