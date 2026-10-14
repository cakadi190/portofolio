<script lang="ts">
  import { Link } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import PostArchive from '@/components/blog/post-archive.svelte';
  import type { BlogPostCard, BlogTagRef } from '@/components/blog/types';
  import HeaderPage from '@/components/header-page.svelte';
  import { index } from '@/wayfinder/routes/blog';
  import { show as showTag } from '@/wayfinder/routes/blog/tags';
  import type { Paginated } from '@/types/pagination';

  let {
    tag,
    posts,
    relatedTags,
  }: {
    tag: BlogTagRef;
    posts: Paginated<BlogPostCard>;
    relatedTags: (BlogTagRef & { count: number })[];
  } = $props();
</script>

<AppHead title={`Tag ${tag.name}`} />

<div id="tag-page">
  <HeaderPage
    backTo={index().url}
    title={`#${tag.name}`}
    subtitle={`${posts.total} artikel dengan tag ini.`}
  />

  <section class="need-space pt-0">
    <div class="container">
      <div class="row gy-5">
        <div class="col-lg-9">
          <PostArchive
            {posts}
            emptyText={`Belum ada artikel yang diterbitkan dengan tag ${tag.name}.`}
          />
        </div>

        <aside class="col-lg-3" aria-label="Tag terkait">
          {#if relatedTags.length}
            <div class="card rounded-4 overflow-hidden">
              <div class="card-header p-4">
                <h2 class="h5 mb-0">Tag Terkait</h2>
              </div>
              <div class="card-body d-flex flex-wrap gap-2">
                {#each relatedTags as other (other.slug)}
                  <Link
                    href={showTag(other.slug).url}
                    class="badge tag-badge text-decoration-none"
                  >
                    {other.name} ({other.count})
                  </Link>
                {/each}
              </div>
            </div>
          {/if}
        </aside>
      </div>
    </div>
  </section>
</div>
