<script lang="ts">
  import { badgeStyle } from '@/lib/badge';
  import AppHead from '@/components/app-head.svelte';
  import { highlightCode } from '@/lib/highlight/action';
  import HeaderPage from '@/components/header-page.svelte';
  import PostMeta from '@/components/blog/post-meta.svelte';
  import BlogComments from '@/components/blog/blog-comments.svelte';
  import type { BlogCommentNode } from '@/components/blog/types';
  import { Link } from '@inertiajs/svelte';
  import ChevronLeft from '@lucide/svelte/icons/chevron-left';
  import Folder from '@lucide/svelte/icons/folder';
  import Tags from '@lucide/svelte/icons/tags';
  import ChevronRight from '@lucide/svelte/icons/chevron-right';
  import type { BlogPostCard } from '@/components/blog/types';
  import { show as showCategory } from '@/wayfinder/routes/blog/categories';
  import { show as showTag } from '@/wayfinder/routes/blog/tags';

  type Post = BlogPostCard & {
    content: string;
    coverImageSrcset?: string | null;
    coverImageSize?: { width: number; height: number } | null;
  };

  type PostLink = { title: string; slug: string; publishedAt?: string | null };
  type Category = {
    id: number;
    name: string;
    slug: string;
    color: string | null;
    count: number;
  };

  let {
    post,
    comments,
    totalComments,
    otherPosts,
    categories,
    adjacent,
  }: {
    post: Post;
    comments: { data: BlogCommentNode[] };
    totalComments: number;
    otherPosts: PostLink[];
    categories: Category[];
    adjacent: { previous: PostLink | null; next: PostLink | null };
  } = $props();

  const publishedLabel = $derived(
    post.publishedAt
      ? new Date(post.publishedAt).toLocaleDateString('id-ID', {
          day: 'numeric',
          month: 'long',
          year: 'numeric',
        })
      : 'Belum dipublikasikan',
  );
</script>

<AppHead title={post.title} />

<div id="blog-detail">
  <HeaderPage
    backTo="/blog"
    title={post.title}
    subtitle={post.excerpt ?? publishedLabel}
  />

  <section class="need-space pt-0">
    <div class="container">
      <div class="row">
        <div class="col-md-12">
          {#if post.coverImage}
            <img
              src={post.coverImage}
              srcset={post.coverImageSrcset ?? undefined}
              sizes="(min-width: 1200px) 1140px, 100vw"
              width={post.coverImageSize?.width}
              height={post.coverImageSize?.height}
              fetchpriority="high"
              decoding="async"
              class="w-100 h-auto rounded-4 border overflow-hidden mt-4 mt-md-0"
              alt={post.title}
            />
          {/if}

          <div class="py-4 border-bottom mb-5 d-flex flex-column gap-3">
            <PostMeta
              author={post.author}
              publishedAt={post.publishedAt}
              title={post.title}
              editUrl={post.editUrl}
            />
          </div>

          <div class="row flex-column-reverse flex-md-row gy-5">
            <div class="col-md-8">
              <div
                class="wysiwyg-content-wrapper"
                use:highlightCode={post.content}
              >
                <!-- eslint-disable-next-line svelte/no-at-html-tags -->
                {@html post.content}
              </div>
            </div>
            <div class="col-md-4">
              <div class="sticky-sidebar d-flex flex-column gap-4">
                <div class="card rounded-4 overflow-hidden">
                  <div class="card-header p-4">
                    <h2 class="h4 mb-0">Artikel Lainnya</h2>
                  </div>
                  {#if otherPosts.length}
                    <ul class="list-group list-group-flush">
                      {#each otherPosts as other (other.slug)}
                        <li class="list-group-item">
                          <Link
                            href={`/blog/${other.slug}`}
                            class="text-decoration-none"
                          >
                            {other.title}
                          </Link>
                        </li>
                      {/each}
                    </ul>
                  {:else}
                    <div class="card-body opacity-75">
                      Belum ada artikel lain.
                    </div>
                  {/if}
                </div>

                <div class="card rounded-4 overflow-hidden">
                  <div class="card-header p-4">
                    <h2 class="h4 mb-0">Kategori</h2>
                  </div>
                  {#if categories.length}
                    <div class="card-body d-flex flex-wrap gap-2">
                      {#each categories as category (category.id)}
                        <Link
                          href={showCategory(category.slug).url}
                          class="badge text-decoration-none"
                          style={badgeStyle(category.color)}
                        >
                          {category.name} ({category.count})
                        </Link>
                      {/each}
                    </div>
                  {:else}
                    <div class="card-body opacity-75">Belum ada kategori.</div>
                  {/if}
                </div>

                <div class="card rounded-4 overflow-hidden">
                  <div class="card-header p-4">
                    <h2 class="h4 mb-0">Tag</h2>
                  </div>
                  {#if post.tags.length}
                    <div class="card-body d-flex flex-wrap gap-2">
                      {#each post.tags as tag (tag.slug)}
                        <Link
                          href={showTag(tag.slug).url}
                          class="badge tag-badge text-decoration-none"
                        >
                          {tag.name}
                        </Link>
                      {/each}
                    </div>
                  {:else}
                    <div class="card-body opacity-75">
                      Belum ada tag untuk artikel ini.
                    </div>
                  {/if}
                </div>
              </div>
            </div>
          </div>

          {#if post.categories.length || post.tags.length}
            <div class="post-taxonomy">
              {#if post.categories.length}
                <div class="post-taxonomy-row">
                  <Folder size={18} aria-label="Kategori" role="img" />
                  <div class="d-flex flex-wrap gap-2">
                    {#each post.categories as category (category.slug)}
                      <Link
                        href={showCategory(category.slug).url}
                        class="badge text-decoration-none"
                        style={badgeStyle(category.color)}
                      >
                        {category.name}
                      </Link>
                    {/each}
                  </div>
                </div>
              {/if}

              {#if post.tags.length}
                <div class="post-taxonomy-row">
                  <Tags size={18} aria-label="Tagar" role="img" />
                  <div class="d-flex flex-wrap gap-2">
                    {#each post.tags as tag (tag.slug)}
                      <Link
                        href={showTag(tag.slug).url}
                        class="badge tag-badge text-decoration-none"
                      >
                        {tag.name}
                      </Link>
                    {/each}
                  </div>
                </div>
              {/if}
            </div>
          {/if}

          <p class="small opacity-75 mt-2">
            Artikel ini adalah pendapat pribadi penulis, bukan nasihat
            profesional. Lihat <a href="/disclaimer">penyangkalan</a>.
          </p>

          {#if adjacent.previous || adjacent.next}
            <nav
              class="d-flex flex-column flex-md-row justify-content-between gap-3 mt-5 pt-4 border-top"
              aria-label="Navigasi artikel"
            >
              {#if adjacent.previous}
                <Link
                  href={`/blog/${adjacent.previous.slug}`}
                  class="btn btn-outline-secondary d-flex align-items-center gap-2 text-start"
                  rel="prev"
                >
                  <ChevronLeft size={18} />
                  <span>
                    <small class="d-block opacity-75">Artikel sebelumnya</small>
                    {adjacent.previous.title}
                  </span>
                </Link>
              {:else}
                <span></span>
              {/if}
              {#if adjacent.next}
                <Link
                  href={`/blog/${adjacent.next.slug}`}
                  class="btn btn-outline-secondary d-flex align-items-center justify-content-end gap-2 text-end"
                  rel="next"
                >
                  <span>
                    <small class="d-block opacity-75">Artikel berikutnya</small>
                    {adjacent.next.title}
                  </span>
                  <ChevronRight size={18} />
                </Link>
              {/if}
            </nav>
          {/if}

          <BlogComments {comments} total={totalComments} postSlug={post.slug} />
        </div>
      </div>
    </div>
  </section>
</div>

<style>
  .post-taxonomy {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding-top: 1.5rem;
    margin: 3rem 0 1rem;
    border-top: 1px solid var(--neo-border-color);
  }

  .post-taxonomy-row {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    color: var(--neo-secondary-color);
  }

  .post-taxonomy-row > :global(svg) {
    flex: 0 0 auto;
    margin-top: 0.125rem;
  }
</style>
