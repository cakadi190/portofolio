<script lang="ts">
  import { t, intlLocale } from '@/lib/i18n';
  import '../../../css/content.scss';
  import Calendar from '@lucide/svelte/icons/calendar';
  import Clock from '@lucide/svelte/icons/clock';
  import Eye from '@lucide/svelte/icons/eye';
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
          <div
            class="py-4 border-bottom mb-5 d-flex flex-column align-items-start gap-3"
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
                {t('unit.views', { count: new Intl.NumberFormat(intlLocale()).format(note.views) })}
              </span>
            </div>
            {#if note.tags.length}
              <div class="d-flex flex-wrap gap-2">
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
