<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import PostArchive from '@/components/blog/post-archive.svelte';
  import type { BlogPostCard, BlogTagRef } from '@/components/blog/types';
  import HeaderPage from '@/components/header-page.svelte';
  import { index } from '@/wayfinder/routes/blog';
  import type { InfiniteList } from '@/types/pagination';

  let {
    tag,
    posts,
    totalPosts,
  }: {
    tag: BlogTagRef;
    posts: InfiniteList<BlogPostCard>;
    totalPosts: number;
  } = $props();
</script>

<AppHead title={`Tag ${tag.name}`} />

<div id="tag-page">
  <HeaderPage
    backTo={index().url}
    title={`#${tag.name}`}
    subtitle={`${totalPosts} artikel dengan tag ini.`}
  />

  <section class="need-space pt-0">
    <div class="container">
      <div class="row">
        <div class="col-12">
          <PostArchive
            {posts}
            emptyText={`Belum ada artikel yang diterbitkan dengan tag ${tag.name}.`}
          />
        </div>
      </div>
    </div>
  </section>
</div>
