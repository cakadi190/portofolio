<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import PostArchive from '@/components/blog/post-archive.svelte';
  import type { BlogPostCard } from '@/components/blog/types';
  import HeaderPage from '@/components/header-page.svelte';
  import { index } from '@/wayfinder/routes/blog';
  import type { InfiniteList } from '@/types/pagination';

  let {
    author,
    posts,
    totalPosts,
  }: {
    author: {
      name: string;
      username: string | null;
      avatar: string | null;
      bio: string | null;
    };
    posts: InfiniteList<BlogPostCard>;
    totalPosts: number;
  } = $props();

  const initial = $derived(author.name.trim().charAt(0).toUpperCase() || '?');
</script>

<AppHead title={`Penulis ${author.name}`} />

<div id="author-page">
  <HeaderPage
    backTo={index().url}
    title={author.name}
    subtitle={author.username ? `@${author.username}` : `${totalPosts} artikel diterbitkan.`}
  >
    <div class="mt-4">
      {#if author.avatar}
        <img
          src={author.avatar}
          alt={`Foto profil ${author.name}`}
          width="96"
          height="96"
          class="rounded-circle border object-fit-cover"
        />
      {:else}
        <span
          class="d-inline-flex align-items-center justify-content-center rounded-circle border fs-2 fw-bold"
          style="width: 96px; height: 96px"
          aria-hidden="true">{initial}</span
        >
      {/if}
    </div>

    <p class="mt-3 mb-0 opacity-75">{totalPosts} artikel diterbitkan.</p>

    {#if author.bio}
      <div class="wysiwyg-content-wrapper mt-3 author-bio">
        <!-- eslint-disable-next-line svelte/no-at-html-tags -->
        {@html author.bio}
      </div>
    {/if}
  </HeaderPage>

  <section class="need-space pt-0">
    <div class="container">
      <PostArchive
        {posts}
        emptyText={`${author.name} belum menerbitkan artikel.`}
      />
    </div>
  </section>
</div>
