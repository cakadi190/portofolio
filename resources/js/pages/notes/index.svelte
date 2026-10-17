<script lang="ts">
  import { t, intlLocale } from '@/lib/i18n';
  import Eye from '@lucide/svelte/icons/eye';
  import Tags from '@lucide/svelte/icons/tags';
  import Link from '@/components/link.svelte';
  import AppHead from '@/components/app-head.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import HeaderPage from '@/components/header-page.svelte';
  import InfiniteFeed from '@/components/infinite-feed.svelte';
  import { index } from '@/wayfinder/routes/notes';
  import type { InfiniteList } from '@/types/pagination';

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
  }: { notes: InfiniteList<Note>; tags: string[]; activeTag: string | null } =
    $props();

  const numberFormat = $derived(new Intl.NumberFormat(intlLocale()));
</script>

<AppHead title={t('notes.title')} />

<div id="notes-page">
  <HeaderPage
    title={t('notes.title')}
    subtitle={t('notes.subtitle')}
  />

  <section class="need-space pt-0">
    <div class="container">
      <div class="row">
        <div class="col-lg-8 mx-auto">
          {#if tags.length}
            <nav class="notes-filter mb-4" aria-label={t('notes.filter')}>
              <Tags size={18} aria-hidden="true" />
              <div class="d-flex flex-wrap gap-2">
                <Link
                  href={index().url}
                  class={`post-taxonomy-tag ${activeTag === null ? 'is-active' : ''}`}
                  aria-current={activeTag === null ? 'page' : undefined}
                >
                  {t('notes.all')}
                </Link>
                {#each tags as tag (tag)}
                  <Link
                    href={index({ query: { tag } }).url}
                    class={`post-taxonomy-tag ${activeTag === tag ? 'is-active' : ''}`}
                    aria-current={activeTag === tag ? 'page' : undefined}
                  >
                    {tag}
                  </Link>
                {/each}
              </div>
            </nav>
          {/if}

          {#if notes.data.length === 0}
            <EmptyState
              title={t('notes.empty_title')}
              text={t('notes.empty_text')}
            />
          {:else}
            <InfiniteFeed data="notes">
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
                      <h2 class="h5 card-title mb-0">{note.title}</h2>
                      <small
                        class="opacity-75 d-inline-flex align-items-center gap-1"
                        title={t('unit.views_label')}
                      >
                        <Eye size={14} />
                        {t('unit.views', { count: numberFormat.format(note.views) })}
                      </small>
                    </div>
                    {#if note.excerpt}
                      <p class="opacity-75 mt-2 mb-0">{note.excerpt}</p>
                    {/if}
                    {#if note.tags.length}
                      <div class="d-flex flex-wrap gap-2 mt-3">
                        {#each note.tags as tag (tag)}
                          <span class="post-taxonomy-tag">{tag}</span>
                        {/each}
                      </div>
                    {/if}
                  </div>
                </Link>
              {/each}
            </div>
            </InfiniteFeed>
          {/if}
        </div>
      </div>
    </div>
  </section>
</div>

<style>
  .notes-filter {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    color: var(--neo-secondary-color);
  }

  .notes-filter > :global(svg) {
    flex: 0 0 auto;
    margin-top: 0.4rem;
  }
</style>
