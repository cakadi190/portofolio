<script lang="ts">
  import Code from '@lucide/svelte/icons/code';
  import ExternalLink from '@lucide/svelte/icons/external-link';
  import FileText from '@lucide/svelte/icons/file-text';
  import PenLine from '@lucide/svelte/icons/pen-line';
  import Trash2 from '@lucide/svelte/icons/trash-2';
  import Undo2 from '@lucide/svelte/icons/undo-2';
  import { Form, router } from '@inertiajs/svelte';
  import { flushSync } from 'svelte';
  import ModalConfirmation from '@/components/modal-confirmation.svelte';
  import AppHead from '@/components/app-head.svelte';
  import DatePicker from '@/components/ui/date-picker.svelte';
  import { Field } from '@/components/ui/field';
  import MediaField from '@/components/media/media-field.svelte';
  import Select from '@/components/ui/select.svelte';
  import MultiCheck from '@/components/ui/multi-check.svelte';
  import RichTextEditor from '@/components/ui/rich-text-editor.svelte';
  import {
    destroy,
    index,
    store,
    update,
  } from '@/wayfinder/routes/admin/posts';
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
  }: {
    post: Post | null;
    tags: Option[];
    categories: Option[];
    authors: Option[];
  } = $props();

  // svelte-ignore state_referenced_locally
  let isPublished = $state(post?.is_published ?? false);

  // svelte-ignore state_referenced_locally
  let title = $state(post?.title ?? '');

  let actionsOpen = $state(false);
  let trashOpen = $state(false);
  let trashing = $state(false);

  function moveToTrash(): void {
    if (!post) return;

    trashing = true;
    router.delete(destroy(post.id).url, {
      onFinish: () => {
        trashing = false;
      },
    });
  }

  const heading = $derived(post ? 'Ubah Artikel' : 'Tulis Artikel Baru');

  function setStatus(published: boolean): void {
    isPublished = published;
    flushSync();
  }
</script>

<svelte:window
  onpointerdown={(event) => {
    if (!(event.target as HTMLElement).closest('[data-actions-trigger]'))
      actionsOpen = false;
  }}
  onkeydown={(event) => {
    if (event.key === 'Escape') actionsOpen = false;
  }}
/>

<AppHead title={heading} />

<Form
  {...post ? update.form(post.id) : store.form()}
  novalidate
  class="post-editor"
>
  {#snippet children({ errors, processing })}
    {#snippet postHeader()}
      <textarea
        id="post-title"
        name="title"
        class="gb-title"
        class:is-invalid={!!errors.title}
        aria-label="Judul artikel"
        placeholder="Tambahkan judul"
        rows="1"
        required
        bind:value={title}
        onkeydown={(event) => {
          if (event.key === 'Enter') event.preventDefault();
        }}></textarea>
      <Field.Feedback message={errors.title} />
    {/snippet}

    {#snippet contentFeedback()}
      <Field.Feedback message={errors.content} />
    {/snippet}

    {#snippet postActions(editor: {
      mode: 'visual' | 'html';
      setMode: (next: 'visual' | 'html') => void;
    })}
      <input
        type="hidden"
        name="is_published"
        value={isPublished ? '1' : '0'}
      />
      <div class="btn-group dropdown">
        <button
          type="submit"
          class="btn btn-sm btn-primary"
          disabled={processing}
          onclick={() => setStatus(true)}
        >
          {post?.is_published ? 'Perbarui' : 'Terbitkan'}
        </button>
        <button
          type="button"
          class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split"
          aria-label="Pilihan simpan"
          aria-haspopup="menu"
          aria-expanded={actionsOpen}
          data-actions-trigger
          disabled={processing}
          onclick={() => (actionsOpen = !actionsOpen)}
        ></button>
        {#if actionsOpen}
          <ul
            class="dropdown-menu dropdown-menu-end show"
            role="menu"
            data-actions-trigger
            style="top: 100%; right: 0; z-index: 1060; min-width: 15rem"
          >
            <li>
              <div
                class="d-flex align-items-center gap-3 px-3 py-2 mb-2 rounded-3"
                style="background: var(--neo-surface-inset)"
              >
                <span
                  class="d-flex align-items-center justify-content-center rounded-circle"
                  style="width: 40px; height: 40px; background: var(--neo-surface-raised)"
                >
                  <FileText size={18} aria-hidden="true" />
                </span>
                <div>
                  <strong class="d-block">Artikel</strong>
                  <span
                    class={`badge ${isPublished ? 'text-bg-success' : 'text-bg-secondary'}`}
                    >{isPublished ? 'Terbit' : 'Draf'}</span
                  >
                </div>
              </div>
            </li>
            <li class="d-lg-none">
              <button
                type="button"
                role="menuitem"
                class="dropdown-item"
                onclick={() => {
                  editor.setMode(editor.mode === 'visual' ? 'html' : 'visual');
                  actionsOpen = false;
                }}
              >
                {#if editor.mode === 'visual'}
                  <Code size={16} aria-hidden="true" /> Editor HTML
                {:else}
                  <PenLine size={16} aria-hidden="true" /> Editor Visual
                {/if}
              </button>
            </li>
            <li>
              <button
                type="submit"
                role="menuitem"
                class="dropdown-item"
                onclick={() => {
                  setStatus(false);
                  actionsOpen = false;
                }}
              >
                <Undo2 size={16} aria-hidden="true" />
                {post?.is_published ? 'Kembalikan ke Draf' : 'Simpan Draf'}
              </button>
            </li>
            {#if post}
              <li><hr class="dropdown-divider" /></li>
              <li>
                <button
                  type="button"
                  role="menuitem"
                  class="dropdown-item dropdown-item-danger"
                  onclick={() => {
                    trashOpen = true;
                    actionsOpen = false;
                  }}
                >
                  <Trash2 size={16} aria-hidden="true" />
                  Pindahkan ke sampah
                </button>
              </li>
            {/if}
          </ul>
        {/if}
      </div>

      <ModalConfirmation
        bind:open={trashOpen}
        title="Pindahkan artikel ke sampah?"
        description="Artikel tidak lagi tampil di situs dan bisa dipulihkan dari tab Sampah."
        processing={trashing}
        confirmLabel="Pindahkan"
        actions={{ confirm: moveToTrash }}
      />
    {/snippet}

    {#snippet postSettings()}
      <details class="gb-panel" open>
        <summary>Terbitkan</summary>
        <div class="gb-panel-body">
          <div>
            Status:
            <span
              class={`badge ${isPublished ? 'text-bg-success' : 'text-bg-secondary'}`}
            >
              {isPublished ? 'Terbit' : 'Draf'}
            </span>
          </div>
          <div>
            <label class="gb-label" for="post-published_at"
              >Tanggal Terbit</label
            >
            <DatePicker
              id="post-published_at"
              name="published_at"
              value={post?.published_at ?? null}
              invalid={!!errors.published_at}
              withTime
            />
            <Field.Feedback message={errors.published_at} />
          </div>
        </div>
      </details>

      <details class="gb-panel" open>
        <summary>Tautan</summary>
        <div class="gb-panel-body">
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
        </div>
      </details>

      <details class="gb-panel" open>
        <summary>Penulis</summary>
        <div class="gb-panel-body">
          <Select
            id="post-user_id"
            name="user_id"
            items={authors.map((a) => ({ value: a.id, label: a.name }))}
            value={post ? post.user_id : null}
            invalid={!!errors.user_id}
            placeholder="Saya sendiri"
          />
          <Field.Feedback message={errors.user_id} />
        </div>
      </details>

      <details class="gb-panel" open>
        <summary>Gambar Sampul</summary>
        <div class="gb-panel-body">
          <MediaField
            name="cover_image"
            value={post?.cover_image ?? null}
            invalid={!!errors.cover_image}
          />
          <Field.Feedback message={errors.cover_image} />
        </div>
      </details>

      <details class="gb-panel" open>
        <summary>Ringkasan</summary>
        <div class="gb-panel-body">
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
        </div>
      </details>

      <details class="gb-panel" open>
        <summary>Kategori</summary>
        <div class="gb-panel-body">
          <MultiCheck
            name="categories"
            options={categories.map((c) => ({ value: c.id, label: c.name }))}
            selected={post?.categories.map((c) => c.id) ?? []}
          />
        </div>
      </details>

      <details class="gb-panel" open>
        <summary>Tag</summary>
        <div class="gb-panel-body">
          <MultiCheck
            name="tags"
            options={tags.map((t) => ({ value: t.id, label: t.name }))}
            selected={post?.tags.map((t) => t.id) ?? []}
          />
        </div>
      </details>
    {/snippet}

    <RichTextEditor
      shell
      name="content"
      value={post?.content ?? ''}
      invalid={!!errors.content}
      backHref={index().url}
      documentTitle={title}
      rootLabel="Artikel"
      header={postHeader}
      settings={postSettings}
      actions={postActions}
      after={contentFeedback}
    />
  {/snippet}
</Form>
