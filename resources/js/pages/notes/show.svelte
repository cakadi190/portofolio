<script lang="ts">
  import { onMount } from 'svelte';
  import { t, intlLocale } from '@/lib/i18n';
  import '../../../css/content.scss';
  import Calendar from '@lucide/svelte/icons/calendar';
  import Clock from '@lucide/svelte/icons/clock';
  import Eye from '@lucide/svelte/icons/eye';
  import Minus from '@lucide/svelte/icons/minus';
  import Plus from '@lucide/svelte/icons/plus';
  import Pencil from '@lucide/svelte/icons/pencil';
  import Tags from '@lucide/svelte/icons/tags';
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

  const TEXT_SCALES = [0.9, 1, 1.15, 1.3, 1.5];
  const TEXT_SCALE_KEY = 'catatancakadi:note-text-scale';

  let scaleIndex = $state(1);

  onMount(() => {
    try {
      const stored = Number(window.localStorage.getItem(TEXT_SCALE_KEY));
      const index = TEXT_SCALES.indexOf(stored);

      if (index !== -1) {
        scaleIndex = index;
      }
    } catch {
      // Storage can be blocked; the default size still works.
    }
  });

  function stepTextScale(direction: -1 | 1): void {
    const next = TEXT_SCALES[scaleIndex + direction];

    if (next === undefined) {
      return;
    }

    scaleIndex += direction;

    try {
      window.localStorage.setItem(TEXT_SCALE_KEY, String(next));
    } catch {
      // Preference just isn't remembered.
    }
  }

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
          <div class="py-4 border-bottom mb-5">
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
              <div
                class="note-text-size"
                role="group"
                aria-label={t('ui.ukuran_teks_bacaan')}
              >
                <button
                  type="button"
                  aria-label={t('ui.perkecil_teks_bacaan')}
                  title={t('ui.perkecil_teks_bacaan')}
                  disabled={scaleIndex === 0}
                  onclick={() => stepTextScale(-1)}
                >
                  <Minus size={14} aria-hidden="true" />
                </button>
                <span aria-live="polite"
                  >{Math.round(TEXT_SCALES[scaleIndex] * 100)}%</span
                >
                <button
                  type="button"
                  aria-label={t('ui.perbesar_teks_bacaan')}
                  title={t('ui.perbesar_teks_bacaan')}
                  disabled={scaleIndex === TEXT_SCALES.length - 1}
                  onclick={() => stepTextScale(1)}
                >
                  <Plus size={14} aria-hidden="true" />
                </button>
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
          </div>

          <div
            class="wysiwyg-content-wrapper"
            style:zoom={TEXT_SCALES[scaleIndex]}
            use:highlightCode={note.content}
          >
            <!-- eslint-disable-next-line svelte/no-at-html-tags -->
            {@html note.content}
          </div>

          {#if note.tags.length}
            <div class="note-tags border-top mt-5 pt-4">
              <Tags size={18} aria-label={t('blog.show.tag_icon')} role="img" />
              <div class="d-flex flex-wrap gap-2">
                {#each note.tags as tag (tag)}
                  <span class="post-taxonomy-tag">{tag}</span>
                {/each}
              </div>
            </div>
          {/if}
        </div>
      </div>
    </div>
  </section>
</div>

<style>
  .note-text-size {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin-left: auto;
    font-size: 0.875rem;
    color: var(--neo-body-color);
  }

  .note-text-size button {
    display: inline-flex;
    align-items: center;
    padding: 0.4rem;
    color: inherit;
    background: transparent;
    border: 1px solid var(--neo-border-color);
    border-radius: 999px;
  }

  .note-text-size button:hover:not(:disabled),
  .note-text-size button:focus-visible {
    color: var(--neo-primary);
    border-color: var(--neo-primary);
  }

  .note-text-size button:disabled {
    opacity: 0.4;
  }

  .note-tags {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    color: var(--neo-secondary-color);
  }

  .note-tags > :global(svg) {
    flex: 0 0 auto;
    margin-top: 0.4rem;
  }

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
