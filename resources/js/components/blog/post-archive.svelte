<script lang="ts">
  import EmptyState from '@/components/empty-state.svelte';
  import InfiniteFeed from '@/components/infinite-feed.svelte';
  import type { InfiniteList } from '@/types/pagination';
  import PostCard from './post-card.svelte';
  import type { BlogPostCard } from './types';

  let {
    posts,
    emptyTitle = 'Belum Ada Artikel',
    emptyText = 'Belum ada artikel yang tersedia saat ini.',
  }: {
    posts: InfiniteList<BlogPostCard>;
    emptyTitle?: string;
    emptyText?: string;
  } = $props();
</script>

{#if posts.data.length === 0}
  <EmptyState title={emptyTitle} text={emptyText} />
{:else}
  <InfiniteFeed data="posts">
  <div class="row">
    {#each posts.data as post (post.slug)}
      <div class="col-md-6 col-lg-4 mb-4">
        <PostCard {post} />
      </div>
    {/each}
  </div>
  </InfiniteFeed>
{/if}
