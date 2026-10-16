<script lang="ts">
  import { t } from '@/lib/i18n';
  import ExternalLink from '@lucide/svelte/icons/external-link';
  import { Form, router } from '@inertiajs/svelte';
  import { flushSync } from 'svelte';
  import AppHead from '@/components/app-head.svelte';
  import EditorPanel from '@/components/admin/editor-panel.svelte';
  import EditorStatusField from '@/components/admin/editor-status-field.svelte';
  import EditorSummaryRow from '@/components/admin/editor-summary-row.svelte';
  import EditorSaveMenu from '@/components/admin/editor-save-menu.svelte';
  import EditorTitle from '@/components/admin/editor-title.svelte';
  import TranslationHiddenInputs from '@/components/admin/translation-hidden-inputs.svelte';
  import TranslationBar from '@/components/admin/translation-bar.svelte';
  import { createTranslatable, fieldName } from '@/lib/translatable.svelte';
  import DatePicker from '@/components/ui/date-picker.svelte';
  import { Field } from '@/components/ui/field';
  import Select from '@/components/ui/select.svelte';
  import RichTextEditor from '@/components/ui/rich-text-editor.svelte';
  import TaxonomySelect from '@/components/ui/taxonomy-select.svelte';
  import { destroy, store, update } from '@/wayfinder/routes/admin/notes';
  import { quick as quickTag } from '@/wayfinder/routes/admin/tags';
  import { show } from '@/wayfinder/routes/notes';

  type Option = { id: number; name: string };

  type Note = {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    content: string;
    translations: Record<'title' | 'excerpt' | 'content', Record<string, string>>;
    user_id: number | null;
    is_published: boolean;
    published_at: string | null;
    tags: { id: number }[];
  };

  let {
    note,
    tags,
    authors,
  }: { note: Note | null; tags: Option[]; authors: Option[] } = $props();

  // svelte-ignore state_referenced_locally
  let isPublished = $state(note?.is_published ?? false);
  // svelte-ignore state_referenced_locally
  const translatable = createTranslatable(note?.translations ?? {}, {
    title: 'text',
    excerpt: 'text',
    content: 'html',
  });
  const title = $derived(translatable.values.title[translatable.locale] || translatable.values.title.id);
  // svelte-ignore state_referenced_locally
  let slug = $state(note?.slug ?? '');
  let publishedDisplay = $state('');
  // svelte-ignore state_referenced_locally
  let authorId = $state<number | string | null>(note ? note.user_id : null);
  const authorName = $derived(
    authors.find((a) => a.id === authorId)?.name ?? '',
  );

  const heading = $derived(note ? t('admin-notes.ubah_catatan') : t('admin-notes.tulis_catatan_baru'));

  let deleting = $state(false);

  function deleteNote(): void {
    if (!note) return;

    deleting = true;
    router.delete(destroy(note.id).url, {
      onFinish: () => {
        deleting = false;
      },
    });
  }

  function setStatus(published: boolean): void {
    isPublished = published;
    flushSync();
  }
</script>

<AppHead title={heading} />

<Form
  {...note ? update.form(note.id) : store.form()}
  novalidate
  class="post-editor"
>
  {#snippet children({ errors, processing })}
    {#snippet noteHeader()}
      <TranslationBar {translatable} />
      <EditorTitle
        name={fieldName('title', translatable.locale)}
        bind:value={translatable.values.title[translatable.locale]}
        ariaLabel={t('admin-notes.judul_catatan')}
        required={translatable.isDefault}
        error={errors[fieldName('title', translatable.locale)]}
      />
      <TranslationHiddenInputs {translatable} fields={['title', 'excerpt', 'content']} />
    {/snippet}

    {#snippet contentFeedback()}
      <Field.Feedback message={errors[fieldName('content', translatable.locale)]} />
    {/snippet}

    {#snippet noteActions(editor: {
      mode: 'visual' | 'html';
      setMode: (next: 'visual' | 'html') => void;
    })}
      <EditorSaveMenu
        {processing}
        exists={!!note}
        {isPublished}
        mode={editor.mode}
        setMode={editor.setMode}
        onStatus={setStatus}
        danger={{
          label: t('admin-notes.hapus_catatan'),
          title: t('admin-notes.hapus_catatan_ini'),
          description: t('admin-notes.catatan_dihapus_permanen_dan_tidak_dapat'),
          confirmLabel: t('security.hapus'),
          processing: deleting,
          run: deleteNote,
        }}
      />
    {/snippet}

    {#snippet noteSettings()}
      <EditorPanel title={t('admin.ringkasan')}>
        <div class="gb-sum">
          <EditorSummaryRow
            label={t('admin.status')}
            title="Status &amp; visibilitas"
            value={isPublished ? t('admin.telah_terbit') : t('admin.draf')}
          >
            <EditorStatusField {isPublished} onStatus={setStatus} />
          </EditorSummaryRow>
          <EditorSummaryRow
            label={t('admin.terbitkan')}
            title={t('admin.tanggal_terbit')}
            width="23rem"
            value={publishedDisplay}
            placeholder={t('admin.segera')}
            invalid={!!errors.published_at}
          >
            <DatePicker
              id="note-published_at"
              name="published_at"
              value={note?.published_at ?? null}
              invalid={!!errors.published_at}
              withTime
              inline
              bind:display={publishedDisplay}
            />
            <Field.Feedback message={errors.published_at} />
          </EditorSummaryRow>
          <EditorSummaryRow
            label={t('admin.slug')}
            title={t('admin.permalink')}
            value={slug}
            placeholder={t('admin.otomatis')}
            invalid={!!errors.slug}
          >
            <input
              id="note-slug"
              name="slug"
              type="text"
              class="form-control form-control-sm"
              class:is-invalid={!!errors.slug}
              placeholder={t('admin.otomatis_dari_judul')}
              aria-label={t('admin.permalink')}
              bind:value={slug}
            />
            <Field.Feedback message={errors.slug} />
            {#if note?.is_published}
              <a
                href={show(note.slug).url}
                target="_blank"
                rel="noopener"
                class="d-inline-flex align-items-center gap-1"
              >
                {t('admin-notes.lihat_catatan')} <ExternalLink size={14} />
              </a>
            {/if}
          </EditorSummaryRow>
          <EditorSummaryRow
            label={t('admin.penulis')}
            title={t('admin.penulis')}
            value={authorName}
            placeholder={t('admin.saya_sendiri')}
            invalid={!!errors.user_id}
          >
            <Select
              id="note-user_id"
              name="user_id"
              items={authors.map((a) => ({ value: a.id, label: a.name }))}
              bind:value={authorId}
              invalid={!!errors.user_id}
              placeholder={t('admin.saya_sendiri')}
            />
            <Field.Feedback message={errors.user_id} />
          </EditorSummaryRow>
        </div>
      </EditorPanel>

      <EditorPanel title={t('admin.kutipan')}>
        <textarea
          name={fieldName('excerpt', translatable.locale)}
          aria-label={t('admin-notes.ringkasan_catatan')}
          class="form-control form-control-sm"
          class:is-invalid={!!errors[fieldName('excerpt', translatable.locale)]}
          rows="4"
          maxlength="255"
          placeholder={t('admin-notes.satu_kalimat_yang_tampil_di_daftar')}
          bind:value={translatable.values.excerpt[translatable.locale]}></textarea>
        <Field.Feedback message={errors[fieldName('excerpt', translatable.locale)]} />
      </EditorPanel>

      <EditorPanel title={t('admin.tag')}>
        <TaxonomySelect
          name="tags"
          items={tags}
          selected={note?.tags.map((t) => t.id) ?? []}
          createUrl={quickTag().url}
        />
      </EditorPanel>
    {/snippet}

    <RichTextEditor
      shell
      name={fieldName('content', translatable.locale)}
      value={translatable.values.content[translatable.locale]}
      contentKey={translatable.contentKey}
      languages={translatable.languages}
      language={translatable.locale}
      onlanguage={(code) => (translatable.locale = code)}
      onchange={(html) => (translatable.values.content[translatable.locale] = html)}
      invalid={!!errors[fieldName('content', translatable.locale)]}
      viewHref={note?.is_published && note.slug ? show(note.slug).url : null}
      documentTitle={title}
      rootLabel={t('admin-notes.catatan')}
      header={noteHeader}
      settings={noteSettings}
      actions={noteActions}
      after={contentFeedback}
    />
  {/snippet}
</Form>
