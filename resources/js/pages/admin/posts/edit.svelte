<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import { Field } from '@/components/ui/field';
  import FileDropzone from '@/components/ui/file-dropzone.svelte';
  import MultiCheck from '@/components/ui/multi-check.svelte';
  import { Form } from '@inertiajs/svelte';
  import { index, update } from '@/wayfinder/routes/admin/posts';

  type PostForm = {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    content: string;
    cover_image: string | null;
    is_published: boolean;
    published_at: string | null;
    tags: { id: number }[];
    categories: { id: number }[];
  };

  let {
    post,
    tags,
    categories,
  }: {
    post: PostForm;
    tags: { id: number; name: string }[];
    categories: { id: number; name: string }[];
  } = $props();

  const selectedTags = $derived(post.tags.map((t) => t.id));
  const selectedCategories = $derived(post.categories.map((c) => c.id));
</script>

<AppHead title="Ubah Artikel" />

<AdminPageHeader title="Ubah Artikel" subtitle={post.title} />

<div class="card" style="max-width: 720px;">
  <div class="card-body">
    <Form {...update.form(post.id)} class="d-flex flex-column gap-3" novalidate>
      {#snippet children({ errors, processing })}
        <Field.Group>
          <Field.Label for="title">Judul</Field.Label>
          <Field.Input id="title" name="title" required value={post.title} invalid={!!errors.title} />
          <Field.Feedback message={errors.title} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="slug">Slug</Field.Label>
          <Field.Input id="slug" name="slug" value={post.slug} invalid={!!errors.slug} />
          <Field.Feedback message={errors.slug} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="cover_image">Gambar Sampul</Field.Label>
          <FileDropzone
            name="cover_image"
            existingUrl={post.cover_image ? `/storage/${post.cover_image}` : null}
            invalid={!!errors.cover_image}
          />
          <Field.Feedback message={errors.cover_image} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="excerpt">Ringkasan</Field.Label>
          <Field.Input id="excerpt" name="excerpt" value={post.excerpt} invalid={!!errors.excerpt} />
          <Field.Feedback message={errors.excerpt} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="content">Konten</Field.Label>
          <textarea
            id="content"
            name="content"
            class="form-control"
            class:is-invalid={!!errors.content}
            rows="10"
            required>{post.content}</textarea
          >
          <Field.Feedback message={errors.content} />
        </Field.Group>

        <input type="hidden" name="is_published" value="0" />
        <Field.Input.Check
          id="is_published"
          name="is_published"
          value="1"
          checked={post.is_published}
        >
          Terbitkan artikel
        </Field.Input.Check>

        <Field.Group>
          <Field.Label for="published_at">Tanggal Terbit</Field.Label>
          <Field.Input
            id="published_at"
            name="published_at"
            type="datetime-local"
            value={post.published_at}
            invalid={!!errors.published_at}
          />
          <Field.Feedback message={errors.published_at} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="tags">Tag</Field.Label>
          <MultiCheck
            name="tags"
            options={tags.map((t) => ({ value: t.id, label: t.name }))}
            selected={selectedTags}
          />
        </Field.Group>

        <Field.Group>
          <Field.Label for="categories">Kategori</Field.Label>
          <MultiCheck
            name="categories"
            options={categories.map((c) => ({ value: c.id, label: c.name }))}
            selected={selectedCategories}
          />
        </Field.Group>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary" disabled={processing}>Simpan</button>
          <a href={index().url} class="btn btn-outline-secondary">Batal</a>
        </div>
      {/snippet}
    </Form>
  </div>
</div>
