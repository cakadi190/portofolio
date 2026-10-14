<script lang="ts">
  import { Link } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import PostArchive from '@/components/blog/post-archive.svelte';
  import type { BlogPostCard, BlogTagRef } from '@/components/blog/types';
  import HeaderPage from '@/components/header-page.svelte';
  import { index } from '@/wayfinder/routes/blog';
  import { show as showCategory } from '@/wayfinder/routes/blog/categories';
  import { show as showTag } from '@/wayfinder/routes/blog/tags';
  import type { Paginated } from '@/types/pagination';

  let {
    category,
    posts,
    relatedCategories,
    relatedTags,
  }: {
    category: { name: string; slug: string; color: string | null };
    posts: Paginated<BlogPostCard>;
    relatedCategories: {
      name: string;
      slug: string;
      color: string | null;
      count: number;
    }[];
    relatedTags: (BlogTagRef & { count: number })[];
  } = $props();
</script>

<AppHead title={`Kategori ${category.name}`} />

<div id="category-page">
  <HeaderPage
    backTo={index().url}
    title={category.name}
    subtitle={`${posts.total} artikel dalam kategori ini.`}
  />

  <section class="need-space pt-0">
    <div class="container">
      <div class="row gy-5">
        <div class="col-lg-9">
          <PostArchive
            {posts}
            emptyTitle="Belum Ada Artikel"
            emptyText={`Belum ada artikel yang diterbitkan dalam kategori ${category.name}.`}
          />
        </div>

        <aside class="col-lg-3" aria-label="Taksonomi terkait">
          <div class="d-flex flex-column gap-4">
            {#if relatedCategories.length}
              <div class="card rounded-4 overflow-hidden">
                <div class="card-header p-4">
                  <h2 class="h5 mb-0">Kategori Lain</h2>
                </div>
                <div class="card-body d-flex flex-wrap gap-2">
                  {#each relatedCategories as other (other.slug)}
                    <Link
                      href={showCategory(other.slug).url}
                      class="badge text-decoration-none"
                      style={`background-color: ${other.color ?? '#6c757d'}`}
                    >
                      {other.name} ({other.count})
                    </Link>
                  {/each}
                </div>
              </div>
            {/if}

            {#if relatedTags.length}
              <div class="card rounded-4 overflow-hidden">
                <div class="card-header p-4">
                  <h2 class="h5 mb-0">Tag Terkait</h2>
                </div>
                <div class="card-body d-flex flex-wrap gap-2">
                  {#each relatedTags as tag (tag.slug)}
                    <Link
                      href={showTag(tag.slug).url}
                      class="badge tag-badge text-decoration-none"
                    >
                      {tag.name} ({tag.count})
                    </Link>
                  {/each}
                </div>
              </div>
            {/if}
          </div>
        </aside>
      </div>
    </div>
  </section>
</div>
