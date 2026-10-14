<script lang="ts">
  import ExternalLink from '@lucide/svelte/icons/external-link';
  import { Form, router } from '@inertiajs/svelte';
  import { flushSync } from 'svelte';
  import AppHead from '@/components/app-head.svelte';
  import EditorPanel from '@/components/admin/editor-panel.svelte';
  import EditorSaveMenu from '@/components/admin/editor-save-menu.svelte';
  import EditorTitle from '@/components/admin/editor-title.svelte';
  import DatePicker from '@/components/ui/date-picker.svelte';
  import { Field } from '@/components/ui/field';
  import MediaField from '@/components/media/media-field.svelte';
  import Select from '@/components/ui/select.svelte';
  import RichTextEditor from '@/components/ui/rich-text-editor.svelte';
  import TaxonomySelect from '@/components/ui/taxonomy-select.svelte';
  import {
    destroy,
    store,
    update,
  } from '@/wayfinder/routes/admin/posts';
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
  let title = $state(post?.title ?? '');
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
      <EditorTitle
        name="title"
        bind:value={title}
        ariaLabel="Judul artikel"
        error={errors.title}
      />
    {/snippet}

    {#snippet contentFeedback()}
      <Field.Feedback message={errors.content} />
    {/snippet}

    {#snippet postActions(editor: {
      mode: 'visual' | 'html';
      setMode: (next: 'visual' | 'html') => void;
    })}
      <EditorSaveMenu
        {processing}
        exists={!!post}
        {isPublished}
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
      <EditorPanel title="Terbitkan">
        <div>
          Status:
          <span
            class={`badge ${isPublished ? 'text-bg-success' : 'text-bg-secondary'}`}
          >
            {isPublished ? 'Terbit' : 'Draf'}
          </span>
        </div>
        <div>
          <label class="gb-label" for="post-published_at">Tanggal Terbit</label>
          <DatePicker
            id="post-published_at"
            name="published_at"
            value={post?.published_at ?? null}
            invalid={!!errors.published_at}
            withTime
          />
          <Field.Feedback message={errors.published_at} />
        </div>
      </EditorPanel>

      <EditorPanel title="Tautan">
        <div>
          <label class="gb-label" for="post-slug">Permalink</label>
          <input
            id="post-slug"
            name="slug"
            type="text"
            class="form-control form-control-sm"
            class:is-invalid={!!errors.slug}
            placeholder="Otomatis dari judul"
            value={post?.slug ?? ''}
          />
          <Field.Feedback message={errors.slug} />
        </div>
        {#if post?.is_published}
          <a
            href={show(post.slug).url}
            target="_blank"
            rel="noopener"
            class="d-inline-flex align-items-center gap-1"
          >
            Lihat artikel <ExternalLink size={14} />
          </a>
        {/if}
      </EditorPanel>

      <EditorPanel title="Penulis">
        <Select
          id="post-user_id"
          name="user_id"
          items={authors.map((a) => ({ value: a.id, label: a.name }))}
          value={post ? post.user_id : null}
          invalid={!!errors.user_id}
          placeholder="Saya sendiri"
        />
        <Field.Feedback message={errors.user_id} />
      </EditorPanel>

      <EditorPanel title="Gambar Sampul">
        <MediaField
          name="cover_image"
          value={post?.cover_image ?? null}
          invalid={!!errors.cover_image}
        />
        <Field.Feedback message={errors.cover_image} />
      </EditorPanel>

      <EditorPanel title="Ringkasan">
        <textarea
          name="excerpt"
          aria-label="Ringkasan artikel"
          class="form-control form-control-sm"
          class:is-invalid={!!errors.excerpt}
          rows="4"
          maxlength="255"
          placeholder="Ringkasan singkat yang tampil di daftar artikel"
          value={post?.excerpt ?? ''}></textarea>
        <Field.Feedback message={errors.excerpt} />
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
      name="content"
      value={post?.content ?? ''}
      invalid={!!errors.content}
      viewHref={post?.slug ? (post.is_published ? show(post.slug).url : previewUrl) : null}
      documentTitle={title}
      rootLabel="Artikel"
      header={postHeader}
      settings={postSettings}
      actions={postActions}
      after={contentFeedback}
    />
  {/snippet}
</Form>
