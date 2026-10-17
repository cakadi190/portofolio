<script lang="ts">
  import { t, intlLocale } from '@/lib/i18n';
  import '../../../css/content.scss';
  import Calendar from '@lucide/svelte/icons/calendar';
  import Clock from '@lucide/svelte/icons/clock';
  import Eye from '@lucide/svelte/icons/eye';
  import Pencil from '@lucide/svelte/icons/pencil';
  import Link from '@/components/link.svelte';
  import AppHead from '@/components/app-head.svelte';
  import HeaderPage from '@/components/header-page.svelte';
  import { highlightCode } from '@/lib/highlight/action';

  type Note = {
    slug: string;
    title: string;
    excerpt: string | null;
    content: string;
    publishedAt: string | null;
    readingMinutes: number;
    views: number;
    tags: string[];
    editUrl: string | null;
  };

  let { note }: { note: Note } = $props();

  const publishedLabel = $derived(
    note.publishedAt
      ? new Date(note.publishedAt).toLocaleDateString(intlLocale(), {
          day: 'numeric',
          month: 'long',
          year: 'numeric',
        })
      : t('post.unpublished'),
  );
</script>

<AppHead title={note.title} />

<div id="note-detail">
  <HeaderPage
    backTo="/notes"
    title={note.title}
    subtitle={note.excerpt ?? publishedLabel}
  />

  <section class="need-space pt-0">
    <div class="container">
      <div class="row">
        <div class="col-lg-8 mx-auto">
          <div class="py-4 border-bottom mb-5 d-flex flex-column gap-3">
            <div
              class="d-flex flex-wrap align-items-center justify-content-between gap-3"
            >
              <div class="d-flex flex-wrap align-items-center gap-3 opacity-75">
                <span class="d-inline-flex align-items-center gap-2">
                  <Calendar size={16} />
                  {publishedLabel}
                </span>
                <span class="d-inline-flex align-items-center gap-2">
                  <Clock size={16} />
                  {t('unit.reading_minutes', { count: note.readingMinutes })}
                </span>
                <span class="d-inline-flex align-items-center gap-2">
                  <Eye size={16} />
                  {t('unit.views', {
                    count: new Intl.NumberFormat(intlLocale()).format(
                      note.views,
                    ),
                  })}
                </span>
              </div>
              {#if note.editUrl}
                <Link
                  href={note.editUrl}
                  class="note-edit"
                  aria-label={t('note.edit')}
                  title={t('note.edit')}
                >
                  <Pencil size={16} aria-hidden="true" />
                </Link>
              {/if}
            </div>
            {#if note.tags.length}
              <div class="d-flex flex-wrap gap-2 border-top pt-3">
                {#each note.tags as tag (tag)}
                  <span class="badge tag-badge">{tag}</span>
                {/each}
              </div>
            {/if}
          </div>

          <div class="wysiwyg-content-wrapper" use:highlightCode={note.content}>
            <!-- eslint-disable-next-line svelte/no-at-html-tags -->
            {@html note.content}
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<style>
  :global(.note-edit) {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 0.625rem;
    color: var(--neo-body-color);
    text-decoration: none;
    background: transparent;
    border: 1px solid var(--neo-border-color);
    border-radius: 999px;
    transition:
      color 0.15s,
      border-color 0.15s,
      background-color 0.15s;
  }

  :global(.note-edit:hover),
  :global(.note-edit:focus-visible) {
    color: var(--neo-primary);
    border-color: var(--neo-primary);
    background: var(--neo-primary-bg-subtle);
  }
</style>
