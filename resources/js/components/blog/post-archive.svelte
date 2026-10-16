<script lang="ts">
  import { t } from '@/lib/i18n';
  import EmptyState from '@/components/empty-state.svelte';
  import InfiniteFeed from '@/components/infinite-feed.svelte';
  import type { InfiniteList } from '@/types/pagination';
  import PostCard from './post-card.svelte';
  import type { BlogPostCard } from './types';

  let {
    posts,
    emptyTitle,
    emptyText,
  }: {
    posts: InfiniteList<BlogPostCard>;
    emptyTitle?: string;
    emptyText?: string;
  } = $props();
</script>

{#if posts.data.length === 0}
  <EmptyState
    title={emptyTitle ?? t('empty.no_articles_title')}
    text={emptyText ?? t('empty.no_articles_text')}
  />
{:else}
  <InfiniteFeed data="posts">
    <div class="row">
      {#each posts.data as post, index (post.slug)}
        <div class="col-md-6 col-lg-4 mb-4">
          <PostCard {post} eager={index === 0} />
        </div>
      {/each}
    </div>
  </InfiniteFeed>
{/if}
