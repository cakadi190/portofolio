<script lang="ts">
  import ArrowLeft from '@lucide/svelte/icons/arrow-left';
  import ExternalLink from '@lucide/svelte/icons/external-link';
  import { Form, Link } from '@inertiajs/svelte';
  import { flushSync } from 'svelte';
  import AppHead from '@/components/app-head.svelte';
  import DatePicker from '@/components/ui/date-picker.svelte';
  import { Field } from '@/components/ui/field';
  import MultiCheck from '@/components/ui/multi-check.svelte';
  import RichTextEditor from '@/components/ui/rich-text-editor.svelte';
  import { index, store, update } from '@/wayfinder/routes/admin/notes';
  import { show } from '@/wayfinder/routes/notes';

  type Option = { id: number; name: string };

  type Note = {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    content: string;
    is_published: boolean;
    published_at: string | null;
    tags: { id: number }[];
  };

  let { note, tags }: { note: Note | null; tags: Option[] } = $props();

  // svelte-ignore state_referenced_locally
  let isPublished = $state(note?.is_published ?? false);

  const heading = $derived(note ? 'Ubah Catatan' : 'Tulis Catatan Baru');

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
    <div
      class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap"
    >
      <Link
        href={index().url}
        class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2"
      >
        <ArrowLeft size={14} />
        Semua Catatan
      </Link>
      <h1 class="h5 mb-0">{heading}</h1>
    </div>

    <div class="row g-4">
      <div class="col-lg-9 d-flex flex-column gap-3">
        <div>
          <input
            id="note-title"
            name="title"
            type="text"
            class="form-control form-control-lg fw-semibold"
            class:is-invalid={!!errors.title}
            placeholder="Tambahkan judul"
            value={note?.title ?? ''}
            required
          />
          <Field.Feedback message={errors.title} />
        </div>

        <div class="d-flex align-items-center gap-2 small">
          <label for="note-slug" class="text-muted text-nowrap"
            >Permalink:</label
          >
          <input
            id="note-slug"
            name="slug"
            type="text"
            class="form-control form-control-sm"
            class:is-invalid={!!errors.slug}
            placeholder="Otomatis dari judul"
            value={note?.slug ?? ''}
          />
          {#if note?.is_published}
            <a
              href={show(note.slug).url}
              target="_blank"
              rel="noopener"
              class="text-nowrap"
              title="Lihat catatan"
            >
              <ExternalLink size={14} />
            </a>
          {/if}
        </div>
        <Field.Feedback message={errors.slug} />

        <div>
          <RichTextEditor
            name="content"
            value={note?.content ?? ''}
            invalid={!!errors.content}
          />
          <Field.Feedback message={errors.content} />
        </div>

        <div class="card">
          <div class="card-header fw-semibold">Ringkasan</div>
          <div class="card-body">
            <textarea
              name="excerpt"
              aria-label="Ringkasan catatan"
              class="form-control"
              class:is-invalid={!!errors.excerpt}
              rows="3"
              maxlength="255"
              placeholder="Satu kalimat yang tampil di daftar catatan"
              value={note?.excerpt ?? ''}></textarea>
            <Field.Feedback message={errors.excerpt} />
          </div>
        </div>
      </div>

      <aside class="col-lg-3 d-flex flex-column gap-3">
        <div class="card">
          <div class="card-header fw-semibold">Terbitkan</div>
          <div class="card-body d-flex flex-column gap-3">
            <input
              type="hidden"
              name="is_published"
              value={isPublished ? '1' : '0'}
            />

            <div class="small">
              Status:
              <span
                class={`badge ${isPublished ? 'text-bg-success' : 'text-bg-secondary'}`}
              >
                {isPublished ? 'Terbit' : 'Draf'}
              </span>
            </div>

            <Field.Group>
              <Field.Label for="note-published_at">Tanggal Terbit</Field.Label>
              <DatePicker
                id="note-published_at"
                name="published_at"
                value={note?.published_at ?? null}
                invalid={!!errors.published_at}
                withTime
              />
              <Field.Feedback message={errors.published_at} />
            </Field.Group>

            <div class="d-flex flex-column gap-2">
              <button
                type="submit"
                class="btn btn-primary"
                disabled={processing}
                onclick={() => setStatus(true)}
              >
                {note?.is_published ? 'Perbarui' : 'Terbitkan'}
              </button>
              <button
                type="submit"
                class="btn btn-outline-secondary"
                disabled={processing}
                onclick={() => setStatus(false)}
              >
                {note?.is_published ? 'Kembalikan ke Draf' : 'Simpan Draf'}
              </button>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header fw-semibold">Tag</div>
          <div class="card-body">
            <MultiCheck
              name="tags"
              options={tags.map((t) => ({ value: t.id, label: t.name }))}
              selected={note?.tags.map((t) => t.id) ?? []}
            />
          </div>
        </div>
      </aside>
    </div>
  {/snippet}
</Form>
