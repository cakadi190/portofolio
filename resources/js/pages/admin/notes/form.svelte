<script lang="ts">
  import ExternalLink from '@lucide/svelte/icons/external-link';
  import { Form, router } from '@inertiajs/svelte';
  import { flushSync } from 'svelte';
  import AppHead from '@/components/app-head.svelte';
  import EditorPanel from '@/components/admin/editor-panel.svelte';
  import EditorStatusField from '@/components/admin/editor-status-field.svelte';
  import EditorSummaryRow from '@/components/admin/editor-summary-row.svelte';
  import EditorSaveMenu from '@/components/admin/editor-save-menu.svelte';
  import EditorTitle from '@/components/admin/editor-title.svelte';
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
  let title = $state(note?.title ?? '');
  // svelte-ignore state_referenced_locally
  let slug = $state(note?.slug ?? '');
  let publishedDisplay = $state('');
  // svelte-ignore state_referenced_locally
  let authorId = $state<number | string | null>(note ? note.user_id : null);
  const authorName = $derived(
    authors.find((a) => a.id === authorId)?.name ?? '',
  );

  const heading = $derived(note ? 'Ubah Catatan' : 'Tulis Catatan Baru');

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
      <EditorTitle
        name="title"
        bind:value={title}
        ariaLabel="Judul catatan"
        error={errors.title}
      />
    {/snippet}

    {#snippet contentFeedback()}
      <Field.Feedback message={errors.content} />
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
          label: 'Hapus catatan',
          title: 'Hapus catatan ini?',
          description: 'Catatan dihapus permanen dan tidak dapat dikembalikan.',
          confirmLabel: 'Hapus',
          processing: deleting,
          run: deleteNote,
        }}
      />
    {/snippet}

    {#snippet noteSettings()}
      <EditorPanel title="Ringkasan">
        <div class="gb-sum">
          <EditorSummaryRow
            label="Status"
            title="Status &amp; visibilitas"
            value={isPublished ? 'Telah terbit' : 'Draf'}
          >
            <EditorStatusField {isPublished} onStatus={setStatus} />
          </EditorSummaryRow>
          <EditorSummaryRow
            label="Terbitkan"
            title="Tanggal terbit"
            width="23rem"
            value={publishedDisplay}
            placeholder="Segera"
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
            label="Slug"
            title="Permalink"
            value={slug}
            placeholder="Otomatis"
            invalid={!!errors.slug}
          >
            <input
              id="note-slug"
              name="slug"
              type="text"
              class="form-control form-control-sm"
              class:is-invalid={!!errors.slug}
              placeholder="Otomatis dari judul"
              aria-label="Permalink"
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
                Lihat catatan <ExternalLink size={14} />
              </a>
            {/if}
          </EditorSummaryRow>
          <EditorSummaryRow
            label="Penulis"
            title="Penulis"
            value={authorName}
            placeholder="Saya sendiri"
            invalid={!!errors.user_id}
          >
            <Select
              id="note-user_id"
              name="user_id"
              items={authors.map((a) => ({ value: a.id, label: a.name }))}
              bind:value={authorId}
              invalid={!!errors.user_id}
              placeholder="Saya sendiri"
            />
            <Field.Feedback message={errors.user_id} />
          </EditorSummaryRow>
        </div>
      </EditorPanel>

      <EditorPanel title="Kutipan">
        <textarea
          name="excerpt"
          aria-label="Ringkasan catatan"
          class="form-control form-control-sm"
          class:is-invalid={!!errors.excerpt}
          rows="4"
          maxlength="255"
          placeholder="Satu kalimat yang tampil di daftar catatan"
          value={note?.excerpt ?? ''}></textarea>
        <Field.Feedback message={errors.excerpt} />
      </EditorPanel>

      <EditorPanel title="Tag">
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
      name="content"
      value={note?.content ?? ''}
      invalid={!!errors.content}
      viewHref={note?.is_published && note.slug ? show(note.slug).url : null}
      documentTitle={title}
      rootLabel="Catatan"
      header={noteHeader}
      settings={noteSettings}
      actions={noteActions}
      after={contentFeedback}
    />
  {/snippet}
</Form>
