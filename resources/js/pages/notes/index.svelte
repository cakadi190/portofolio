<script lang="ts">
  import Eye from '@lucide/svelte/icons/eye';
  import { Link } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import HeaderPage from '@/components/header-page.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import { index } from '@/wayfinder/routes/notes';
  import type { Paginated } from '@/types/pagination';

  type Note = {
    title: string;
    slug: string;
    excerpt: string | null;
    publishedAt: string | null;
    views: number;
    tags: string[];
  };

  let {
    notes,
    tags,
    activeTag,
  }: { notes: Paginated<Note>; tags: string[]; activeTag: string | null } =
    $props();

  const numberFormat = new Intl.NumberFormat('id-ID');
</script>

<AppHead title="Catatan Singkat" />

<div id="notes-page">
  <HeaderPage
    title="Catatan Singkat"
    subtitle="Catatan pribadi saya yang tidak cukup panjang untuk jadi artikel."
  />

  <section class="need-space pt-0">
    <div class="container">
      <div class="row">
        <div class="col-lg-8 mx-auto">
          {#if tags.length}
            <nav class="d-flex flex-wrap gap-2 mb-4" aria-label="Filter topik">
              <Link
                href={index().url}
                class={`badge tag-badge text-decoration-none ${activeTag === null ? 'text-bg-primary' : ''}`}
              >
                Semua
              </Link>
              {#each tags as tag (tag)}
                <Link
                  href={index({ query: { tag } }).url}
                  class={`badge tag-badge text-decoration-none ${activeTag === tag ? 'text-bg-primary' : ''}`}
                >
                  {tag}
                </Link>
              {/each}
            </nav>
          {/if}

          {#if notes.data.length === 0}
            <EmptyState
              title="Belum Ada Catatan"
              text="Belum ada catatan yang tersedia saat ini."
            />
          {:else}
            <div class="d-flex flex-column gap-3">
              {#each notes.data as note (note.slug)}
                <Link
                  href={`/notes/${note.slug}`}
                  class="card rounded-4 text-decoration-none"
                >
                  <div class="card-body p-4">
                    <div
                      class="d-flex flex-wrap justify-content-between align-items-start gap-2"
                    >
                      <h5 class="card-title mb-0">{note.title}</h5>
                      <small
                        class="opacity-75 d-inline-flex align-items-center gap-1"
                        title="Jumlah tayangan"
                      >
                        <Eye size={14} />
                        {numberFormat.format(note.views)} tayangan
                      </small>
                    </div>
                    {#if note.excerpt}
                      <p class="opacity-75 mt-2 mb-0">{note.excerpt}</p>
                    {/if}
                    {#if note.tags.length}
                      <div class="d-flex flex-wrap gap-2 mt-3">
                        {#each note.tags as tag (tag)}
                          <span class="badge tag-badge">{tag}</span>
                        {/each}
                      </div>
                    {/if}
                  </div>
                </Link>
              {/each}
            </div>

            <SimplePaginator
              currentPage={notes.current_page}
              lastPage={notes.last_page}
              prevPageUrl={notes.prev_page_url}
              nextPageUrl={notes.next_page_url}
            />
          {/if}
        </div>
      </div>
    </div>
  </section>
</div>
