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
  import TranslationHiddenInputs from '@/components/admin/translation-hidden-inputs.svelte';
  import TranslationBar from '@/components/admin/translation-bar.svelte';
  import { createTranslatable, fieldName } from '@/lib/translatable.svelte';
  import DatePicker from '@/components/ui/date-picker.svelte';
  import { Field } from '@/components/ui/field';
  import MediaField from '@/components/media/media-field.svelte';
  import Select from '@/components/ui/select.svelte';
  import RichTextEditor from '@/components/ui/rich-text-editor.svelte';
  import TaxonomySelect from '@/components/ui/taxonomy-select.svelte';
  import { destroy, store, update } from '@/wayfinder/routes/admin/posts';
  import { quick as quickCategory } from '@/wayfinder/routes/admin/post-categories';
  import { quick as quickTag } from '@/wayfinder/routes/admin/tags';
  import { show } from '@/wayfinder/routes/blog';

  type Option = { id: number; name: string };

  type Post = {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    content: string;
    translations: Record<'title' | 'excerpt' | 'content', Record<string, string>>;
    cover_image: string | null;
    user_id: number | null;
    is_published: boolean;
    published_at: string | null;
    tags: { id: number }[];
    categories: { id: number }[];
  };

  let {
    post,
    tags,
    categories,
    authors,
    previewUrl = null,
  }: {
    post: Post | null;
    previewUrl?: string | null;
    tags: Option[];
    categories: Option[];
    authors: Option[];
  } = $props();

  // svelte-ignore state_referenced_locally
  let isPublished = $state(post?.is_published ?? false);
  // svelte-ignore state_referenced_locally
  const translatable = createTranslatable(post?.translations ?? {}, {
    title: 'text',
    excerpt: 'text',
    content: 'html',
  });
  const title = $derived(translatable.values.title[translatable.locale] || translatable.values.title.id);
  // svelte-ignore state_referenced_locally
  let slug = $state(post?.slug ?? '');
  let publishedDisplay = $state('');
  // svelte-ignore state_referenced_locally
  let publishedValue = $state(post?.published_at ?? '');
  const scheduled = $derived(
    publishedValue !== '' && new Date(publishedValue).getTime() > Date.now(),
  );
  // svelte-ignore state_referenced_locally
  let authorId = $state<number | string | null>(post ? post.user_id : null);
  const authorName = $derived(
    authors.find((a) => a.id === authorId)?.name ?? '',
  );
  let trashing = $state(false);

  const heading = $derived(post ? 'Ubah Artikel' : 'Tulis Artikel Baru');

  function setStatus(published: boolean): void {
    isPublished = published;
    flushSync();
  }

  function moveToTrash(): void {
    if (!post) return;

    trashing = true;
    router.delete(destroy(post.id).url, {
      onFinish: () => {
        trashing = false;
      },
    });
  }
</script>

<AppHead title={heading} />

<Form
  {...post ? update.form(post.id) : store.form()}
  novalidate
  class="post-editor"
>
  {#snippet children({ errors, processing })}
    {#snippet postHeader()}
      <TranslationBar {translatable} />
      <EditorTitle
        name={fieldName('title', translatable.locale)}
        bind:value={translatable.values.title[translatable.locale]}
        ariaLabel="Judul artikel"
        required={translatable.isDefault}
        error={errors[fieldName('title', translatable.locale)]}
      />
      <TranslationHiddenInputs {translatable} fields={['title', 'excerpt', 'content']} />
    {/snippet}

    {#snippet contentFeedback()}
      <Field.Feedback message={errors[fieldName('content', translatable.locale)]} />
    {/snippet}

    {#snippet postActions(editor: {
      mode: 'visual' | 'html';
      setMode: (next: 'visual' | 'html') => void;
    })}
      <EditorSaveMenu
        {processing}
        exists={!!post}
        {isPublished}
        {scheduled}
        mode={editor.mode}
        setMode={editor.setMode}
        onStatus={setStatus}
        danger={{
          label: 'Pindahkan ke sampah',
          title: 'Pindahkan artikel ke sampah?',
          description:
            'Artikel tidak lagi tampil di situs dan bisa dipulihkan dari tab Sampah.',
          confirmLabel: 'Pindahkan',
          processing: trashing,
          run: moveToTrash,
        }}
      />
    {/snippet}

    {#snippet postSettings()}
      <EditorPanel title="Ringkasan">
        <div class="gb-sum">
          <EditorSummaryRow
            label="Status"
            title="Status &amp; visibilitas"
            value={isPublished
              ? scheduled
                ? 'Terjadwal'
                : 'Telah terbit'
              : 'Draf'}
          >
            <EditorStatusField {isPublished} {scheduled} onStatus={setStatus} />
          </EditorSummaryRow>
          <EditorSummaryRow
            label="Terbitkan"
            title="Tanggal terbit (jadwalkan dengan tanggal mendatang)"
            width="23rem"
            value={publishedDisplay}
            placeholder="Segera"
            invalid={!!errors.published_at}
          >
            <DatePicker
              id="post-published_at"
              name="published_at"
              value={post?.published_at ?? null}
              invalid={!!errors.published_at}
              withTime
              inline
              bind:display={publishedDisplay}
              bind:current={publishedValue}
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
              id="post-slug"
              name="slug"
              type="text"
              class="form-control form-control-sm"
              class:is-invalid={!!errors.slug}
              placeholder="Otomatis dari judul"
              aria-label="Permalink"
              bind:value={slug}
            />
            <Field.Feedback message={errors.slug} />
            {#if post?.is_published && !scheduled}
              <a
                href={show(post.slug).url}
                target="_blank"
                rel="noopener"
                class="d-inline-flex align-items-center gap-1"
              >
                Lihat artikel <ExternalLink size={14} />
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
              id="post-user_id"
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

      <EditorPanel title="Gambar Sampul">
        <MediaField
          name="cover_image"
          value={post?.cover_image ?? null}
          invalid={!!errors.cover_image}
        />
        <Field.Feedback message={errors.cover_image} />
      </EditorPanel>

      <EditorPanel title="Kutipan">
        <textarea
          name={fieldName('excerpt', translatable.locale)}
          aria-label="Ringkasan artikel"
          class="form-control form-control-sm"
          class:is-invalid={!!errors[fieldName('excerpt', translatable.locale)]}
          rows="4"
          maxlength="255"
          placeholder="Ringkasan singkat yang tampil di daftar artikel"
          bind:value={translatable.values.excerpt[translatable.locale]}></textarea>
        <Field.Feedback message={errors[fieldName('excerpt', translatable.locale)]} />
      </EditorPanel>

      <EditorPanel title="Kategori">
        <TaxonomySelect
          name="categories"
          items={categories}
          selected={post?.categories.map((c) => c.id) ?? []}
          createUrl={quickCategory().url}
        />
      </EditorPanel>

      <EditorPanel title="Tag">
        <TaxonomySelect
          name="tags"
          items={tags}
          selected={post?.tags.map((t) => t.id) ?? []}
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
      viewHref={post?.slug
        ? post.is_published
          ? show(post.slug).url
          : previewUrl
        : null}
      documentTitle={title}
      rootLabel="Artikel"
      header={postHeader}
      settings={postSettings}
      actions={postActions}
      after={contentFeedback}
    />
  {/snippet}
</Form>
