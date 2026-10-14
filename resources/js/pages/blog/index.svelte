<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import PostCard from '@/components/blog/post-card.svelte';
  import type { BlogPostCard } from '@/components/blog/types';
  import EmptyState from '@/components/empty-state.svelte';
  import HeaderPage from '@/components/header-page.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import type { Paginated } from '@/types/pagination';

  let {
    posts,
    activeCategory = null,
  }: {
    posts: Paginated<BlogPostCard>;
    activeCategory?: { id: number; name: string } | null;
  } = $props();
</script>

<AppHead title="Artikel" />

<div id="articles-page">
  <HeaderPage
    title="Artikel"
    subtitle={activeCategory
      ? `Artikel dalam kategori ${activeCategory.name}.`
      : 'Berikut daftar artikel yang saya tulis.'}
  />

  <section class="need-space pt-0">
    <div class="container">
      <div class="row">
        {#if posts.data.length === 0}
          <EmptyState
            title="Belum Ada Artikel"
            text="Belum ada artikel yang tersedia saat ini."
          />
        {:else}
          <div class="col-md-12 mx-auto">
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
          </div>
        {/if}
      </div>
    </div>
  </section>
</div>
