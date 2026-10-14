<script lang="ts">
  import EmptyState from '@/components/empty-state.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import type { Paginated } from '@/types/pagination';
  import PostCard from './post-card.svelte';
  import type { BlogPostCard } from './types';

  let {
    posts,
    emptyTitle = 'Belum Ada Artikel',
    emptyText = 'Belum ada artikel yang tersedia saat ini.',
  }: {
    posts: Paginated<BlogPostCard>;
    emptyTitle?: string;
    emptyText?: string;
  } = $props();
</script>

{#if posts.data.length === 0}
  <EmptyState title={emptyTitle} text={emptyText} />
{:else}
  <div class="row">
    {#each posts.data as post (post.slug)}
      <div class="col-md-6 col-lg-4 mb-4">
        <PostCard {post} />
      </div>
    {/each}
  </div>

  <SimplePaginator
    currentPage={posts.current_page}
    lastPage={posts.last_page}
    prevPageUrl={posts.prev_page_url}
    nextPageUrl={posts.next_page_url}
  />
{/if}
