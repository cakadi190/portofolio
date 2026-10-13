<script lang="ts">
  import Calendar from '@lucide/svelte/icons/calendar';
  import User from '@lucide/svelte/icons/user';
  import AppHead from '@/components/app-head.svelte';
  import { highlightCode } from '@/lib/highlight/action';
  import HeaderPage from '@/components/header-page.svelte';
  import BlogComments from '@/components/blog/blog-comments.svelte';
  import type { BlogCommentNode } from '@/components/blog/types';
  import { Link } from '@inertiajs/svelte';
  import ChevronLeft from '@lucide/svelte/icons/chevron-left';
  import ChevronRight from '@lucide/svelte/icons/chevron-right';
  import { index } from '@/wayfinder/routes/blog';

  type Post = {
    slug: string;
    title: string;
    excerpt: string | null;
    content: string;
    coverImage: string | null;
    author: string | null;
    publishedAt: string | null;
    categories: { name: string; color: string | null }[];
    tags: string[];
  };

  type PostLink = { title: string; slug: string; publishedAt?: string | null };
  type Category = {
    id: number;
    name: string;
    color: string | null;
    count: number;
  };

  let {
    post,
    comments,
    otherPosts,
    categories,
    adjacent,
  }: {
    post: Post;
    comments: BlogCommentNode[];
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
              class="w-100 rounded-4 border overflow-hidden mt-4 mt-md-0"
              alt={post.title}
            />
          {/if}

          <div
            class="py-4 flex-column border-bottom mb-5 align-items-start d-flex gap-3"
          >
            <div class="d-flex flex-wrap align-items-center gap-2">
              <div class="d-flex align-items-center gap-2 opacity-75">
                <Calendar size={16} />
                <span>{publishedLabel}</span>
              </div>

              {#if post.author}
                <div class="d-flex align-items-center gap-2 opacity-75">
                  <User size={16} />
                  <span>{post.author}</span>
                </div>
              {/if}

              {#each post.categories as category (category.name)}
                <span
                  class="badge"
                  style={`background-color: ${category.color ?? '#6c757d'}`}
                >
                  {category.name}
                </span>
              {/each}
            </div>
          </div>

          <div class="row flex-column-reverse flex-md-row gy-5">
            <div class="col-md-8">
              <div class="wysiwyg-content-wrapper" use:highlightCode={post.content}>
                <!-- eslint-disable-next-line svelte/no-at-html-tags -->
                {@html post.content}
              </div>
            </div>
            <div class="col-md-4">
              <div class="sticky-top d-flex flex-column gap-4">
              <div class="card rounded-4">
                <div class="card-header p-4">
                  <h4 class="mb-0">Artikel Lainnya</h4>
                </div>
                {#if otherPosts.length}
                  <ul class="list-group list-group-flush rounded-bottom-4">
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

              <div class="card rounded-4">
                <div class="card-header p-4">
                  <h4 class="mb-0">Kategori</h4>
                </div>
                {#if categories.length}
                  <div class="card-body d-flex flex-wrap gap-2">
                    {#each categories as category (category.id)}
                      <Link
                        href={index({ query: { category: category.id } }).url}
                        class="badge text-decoration-none"
                        style={`background-color: ${category.color ?? '#6c757d'}`}
                      >
                        {category.name} ({category.count})
                      </Link>
                    {/each}
                  </div>
                {:else}
                  <div class="card-body opacity-75">Belum ada kategori.</div>
                {/if}
              </div>

              <div class="card rounded-4">
                <div class="card-header p-4">
                  <h4 class="mb-0">Tag</h4>
                </div>
                {#if post.tags.length}
                  <div class="card-body d-flex flex-wrap gap-2">
                    {#each post.tags as tag (tag)}
                      <span class="badge tag-badge">{tag}</span>
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

          <p class="small opacity-75 mt-5">
            Artikel ini adalah pendapat pribadi penulis, bukan nasihat
            profesional. Lihat <a href="/disclaimer">penyangkalan</a>.
          </p>

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

          <BlogComments {comments} postSlug={post.slug} />
        </div>
      </div>
    </div>
  </section>
</div>
