<script lang="ts">
  import { t } from '@/lib/i18n';
  import AppHead from '@/components/app-head.svelte';
  import PostArchive from '@/components/blog/post-archive.svelte';
  import type { BlogPostCard } from '@/components/blog/types';
  import HeaderPage from '@/components/header-page.svelte';
  import { index } from '@/wayfinder/routes/blog';
  import type { InfiniteList } from '@/types/pagination';

  let {
    category,
    posts,
    totalPosts,
  }: {
    category: { name: string; slug: string; color: string | null };
    posts: InfiniteList<BlogPostCard>;
    totalPosts: number;
  } = $props();
</script>

<AppHead title={t('blog.category.title', { name: category.name })} />

<div id="category-page">
  <HeaderPage
    backTo={index().url}
    title={category.name}
    subtitle={t('blog.category.count', { total: totalPosts })}
  />

  <section class="need-space pt-0">
    <div class="container">
      <div class="row">
        <div class="col-12">
          <PostArchive
            {posts}
            emptyText={t('blog.category.empty', { name: category.name })}
          />
        </div>
      </div>
    </div>
  </section>
</div>
