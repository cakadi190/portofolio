<script lang="ts">
  import { Link } from '@inertiajs/svelte';
  import ChevronLeft from '@lucide/svelte/icons/chevron-left';
  import FileText from '@lucide/svelte/icons/file-text';
  import AppHead from '@/components/app-head.svelte';
  import PostArchive from '@/components/blog/post-archive.svelte';
  import type { BlogPostCard } from '@/components/blog/types';
  import { index } from '@/wayfinder/routes/blog';
  import type { InfiniteList } from '@/types/pagination';

  let {
    author,
    posts,
    totalPosts = posts.data.length,
  }: {
    author: {
      name: string;
      username: string | null;
      avatar: string | null;
      bio?: string | null;
    };
    posts: InfiniteList<BlogPostCard>;
    totalPosts?: number;
  } = $props();

  const initial = $derived(author.name.trim().charAt(0).toUpperCase() || '?');
</script>

<AppHead title={`Penulis ${author.name}`} />

<div id="author-page">
  <section class="author-hero">
    <div class="container">
      <Link href={index().url} class="author-back">
        <ChevronLeft size={16} aria-hidden="true" />
        Kembali
      </Link>

      <div class="author-card">
        <span
          class="author-avatar reveal reveal-spring reveal-right"
          style="--reveal-delay: 400ms"
          aria-hidden={author.avatar ? undefined : 'true'}
        >
          {#if author.avatar}
            <img
              src={author.avatar}
              alt={`Foto profil ${author.name}`}
              width="128"
              height="128"
            />
          {:else}
            {initial}
          {/if}
        </span>

        <div class="author-body">
          <h1 class="author-name reveal reveal-spring reveal-left">
            {author.name}
          </h1>

          <div
            class="author-chips reveal reveal-spring reveal-left"
            style="--reveal-delay: 100ms"
          >
            {#if author.username}
              <span class="author-chip author-chip-handle"
                >@{author.username}</span
              >
            {/if}
            <span class="author-chip">
              <FileText size={14} aria-hidden="true" />
              {totalPosts} artikel diterbitkan
            </span>
          </div>

          {#if author.bio}
            <div
              class="wysiwyg-content-wrapper author-bio reveal reveal-spring reveal-left"
              style="--reveal-delay: 200ms"
            >
              <!-- eslint-disable-next-line svelte/no-at-html-tags -->
              {@html author.bio}
            </div>
          {/if}
        </div>
      </div>
    </div>
  </section>

  <section class="need-space">
    <div class="container">
      <h2 class="author-posts-title">Artikel oleh {author.name}</h2>

      <PostArchive
        {posts}
        emptyText={`${author.name} belum menerbitkan artikel.`}
      />
    </div>
  </section>
</div>

<style>
  .author-hero {
    padding: 6.5rem 0 2.5rem;
  }

  .author-hero :global(.author-back) {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    margin-bottom: 1.25rem;
    font-weight: 600;
    text-decoration: none;
  }

  .author-card {
    display: flex;
    align-items: flex-start;
    gap: 2rem;
    padding: 2rem;
    background: linear-gradient(
      135deg,
      var(--neo-primary-bg-subtle),
      var(--neo-body-bg) 70%
    );
    border: 1px solid var(--neo-border-color);
    border-radius: 1.5rem;
  }

  .author-avatar {
    display: inline-flex;
    flex: none;
    align-items: center;
    justify-content: center;
    width: 8rem;
    height: 8rem;
    overflow: hidden;
    font-size: 3rem;
    font-weight: 600;
    color: var(--neo-primary);
    background: var(--neo-body-bg);
    border-radius: 50%;
    box-shadow:
      0 0 0 4px var(--neo-body-bg),
      0 0 0 5px var(--neo-border-color),
      0 0.75rem 1.5rem rgb(0 0 0 / 0.1);
  }

  .author-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .author-body {
    flex: 1;
    min-width: 0;
  }

  .author-name {
    margin: 0 0 0.75rem;
    font-size: clamp(1.75rem, 4vw, 2.5rem);
    line-height: 1.15;
  }

  .author-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1.25rem;
  }

  .author-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.75rem;
    font-size: 0.875rem;
    color: var(--neo-secondary-color);
    background: var(--neo-body-bg);
    border: 1px solid var(--neo-border-color);
    border-radius: 999px;
  }

  .author-chip-handle {
    font-weight: 600;
    color: var(--neo-primary);
    border-color: var(--neo-primary);
  }

  .author-bio {
    max-width: 65ch;
  }

  .author-bio :global(> :last-child) {
    margin-bottom: 0;
  }

  .author-posts-title {
    margin-bottom: 1.5rem;
    font-size: 1.25rem;
  }

  @media (max-width: 767.98px) {
    .author-hero {
      padding-top: 5.5rem;
    }

    .author-card {
      flex-direction: column;
      align-items: center;
      padding: 1.5rem 1.25rem;
      text-align: center;
    }

    .author-chips {
      justify-content: center;
    }

    .author-bio {
      text-align: left;
    }

    .author-avatar {
      width: 6.5rem;
      height: 6.5rem;
      font-size: 2.5rem;
    }
  }
</style>
