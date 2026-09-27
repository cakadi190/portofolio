<script lang="ts">
  import Calendar from '@lucide/svelte/icons/calendar';
  import AppHead from '@/components/app-head.svelte';
  import HeaderPage from '@/components/header-page.svelte';

  type Post = {
    title: string;
    excerpt: string | null;
    content: string;
    coverImage: string | null;
    publishedAt: string | null;
    categories: { name: string; color: string | null }[];
    tags: string[];
  };

  let { post }: { post: Post } = $props();

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

<AppHead title={post.title}>
  <meta name="description" content={post.excerpt ?? post.title} />
</AppHead>

<div id="blog-detail">
  <HeaderPage backTo="/blog" title="Detail Artikel" subtitle="Berikut saya tampilkan detail artikel yang saya tulis ini." />

  <section class="need-space pt-0">
    <div class="container">
      <div class="row">
        <div class="col-md-12">
          {#if post.coverImage}
            <img src={post.coverImage} class="w-100 rounded-4 border overflow-hidden" alt={post.title} />
          {/if}

          <div class="pt-5 pb-4 flex-column border-bottom mb-5 align-items-start d-flex gap-3">
            {#if post.categories.length}
              <div class="d-flex flex-wrap gap-2">
                {#each post.categories as category (category.name)}
                  <span class="badge" style={`background-color: ${category.color ?? '#6c757d'}`}>
                    {category.name}
                  </span>
                {/each}
              </div>
            {/if}

            <h1 class="h3 mb-0">{post.title}</h1>

            <div class="d-flex align-items-center gap-2 opacity-75">
              <Calendar size={16} />
              <span>{publishedLabel}</span>
            </div>

            {#if post.excerpt}
              <p class="opacity-75 mb-0">{post.excerpt}</p>
            {/if}
          </div>

          <div class="row flex-column-reverse flex-md-row gy-5">
            <div class="col-md-8">
              <!-- eslint-disable-next-line svelte/no-at-html-tags -->
              {@html post.content}
            </div>
            <div class="col-md-4">
              <div class="card sticky-top rounded-4">
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
                  <div class="card-body opacity-75">Belum ada tag untuk artikel ini.</div>
                {/if}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
