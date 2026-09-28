<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import { Field } from '@/components/ui/field';
  import FileDropzone from '@/components/ui/file-dropzone.svelte';
  import MultiCheck from '@/components/ui/multi-check.svelte';
  import { Form } from '@inertiajs/svelte';
  import { destroy, store, update } from '@/wayfinder/routes/admin/posts';
  import type { Paginated } from '@/types/pagination';

  type Post = {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    content: string;
    cover_image: string | null;
    is_published: boolean;
    published_at: string | null;
    tags_count: number;
    categories_count: number;
    tags: { id: number; name: string }[];
    categories: { id: number; name: string }[];
  };

  let {
    posts,
    tags,
    categories,
  }: {
    posts: Paginated<Post>;
    tags: { id: number; name: string }[];
    categories: { id: number; name: string }[];
  } = $props();

  let createOpen = $state(false);
  let editOpen = $state(false);
  let editingPost = $state<Post | null>(null);
  const editingSelectedTags = $derived(editingPost?.tags.map((t) => t.id) ?? []);
  const editingSelectedCategories = $derived(editingPost?.categories.map((c) => c.id) ?? []);

  function openEdit(post: Post): void {
    editingPost = post;
    editOpen = true;
  }
</script>

<AppHead title="Artikel Blog" />

<AdminPageHeader
  title="Artikel Blog"
  subtitle="Kelola artikel blog Anda."
  onCreate={() => (createOpen = true)}
/>

{#if posts.data.length === 0}
  <EmptyState title="Belum ada artikel" text="Tulis artikel pertama Anda." />
{:else}
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Judul</th>
          <th>Status</th>
          <th>Tag</th>
          <th>Kategori</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        {#each posts.data as post (post.id)}
          <tr>
            <td>{post.title}</td>
            <td>
              <span class={`badge ${post.is_published ? 'text-bg-success' : 'text-bg-secondary'}`}>
                {post.is_published ? 'Terbit' : 'Draf'}
              </span>
            </td>
            <td><div class="d-flex flex-wrap gap-1">
                {#each post.tags as item (item.id)}
                  <span class="badge text-bg-secondary fw-normal">{item.name}</span>
                {:else}
                  <span class="text-muted">&mdash;</span>
                {/each}
              </div></td>
            <td><div class="d-flex flex-wrap gap-1">
                {#each post.categories as item (item.id)}
                  <span class="badge text-bg-secondary fw-normal">{item.name}</span>
                {:else}
                  <span class="text-muted">&mdash;</span>
                {/each}
              </div></td>
            <td class="text-end">
              <div class="d-inline-flex gap-2">
                <button
                  type="button"
                  class="btn btn-sm btn-outline-secondary"
                  onclick={() => openEdit(post)}
                >
                  Ubah
                </button>
                <AdminDeleteButton
                  href={destroy(post.id).url}
                  label={`Hapus artikel "${post.title}"?`}
                />
              </div>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>

  <SimplePaginator
    currentPage={posts.current_page}
    lastPage={posts.last_page}
    prevPageUrl={posts.prev_page_url}
    nextPageUrl={posts.next_page_url}
  />
{/if}

<FormModal bind:open={createOpen} title="Tambah Artikel" subtitle="Tulis artikel baru." size="lg">
  {#snippet children()}
    <Form
      {...store.form()}
      class="d-flex flex-column gap-3"
      novalidate
      onSuccess={() => (createOpen = false)}
    >
      {#snippet children({ errors, processing })}
        <Field.Group>
          <Field.Label for="create-title">Judul</Field.Label>
          <Field.Input id="create-title" name="title" required invalid={!!errors.title} />
          <Field.Feedback message={errors.title} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-slug">Slug</Field.Label>
          <Field.Input
            id="create-slug"
            name="slug"
            placeholder="Otomatis dari judul"
            invalid={!!errors.slug}
          />
          <Field.Feedback message={errors.slug} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-cover_image">Gambar Sampul</Field.Label>
          <FileDropzone name="cover_image" invalid={!!errors.cover_image} />
          <Field.Feedback message={errors.cover_image} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-excerpt">Ringkasan</Field.Label>
          <Field.Input id="create-excerpt" name="excerpt" invalid={!!errors.excerpt} />
          <Field.Feedback message={errors.excerpt} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-content">Konten</Field.Label>
          <textarea
            id="create-content"
            name="content"
            class="form-control"
            class:is-invalid={!!errors.content}
            rows="10"
            required
          ></textarea>
          <Field.Feedback message={errors.content} />
        </Field.Group>

        <input type="hidden" name="is_published" value="0" />
        <Field.Input.Check id="create-is_published" name="is_published" value="1">
          Terbitkan artikel
        </Field.Input.Check>

        <Field.Group>
          <Field.Label for="create-published_at">Tanggal Terbit</Field.Label>
          <Field.Input
            id="create-published_at"
            name="published_at"
            type="datetime-local"
            invalid={!!errors.published_at}
          />
          <Field.Feedback message={errors.published_at} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-tags">Tag</Field.Label>
          <MultiCheck name="tags" options={tags.map((t) => ({ value: t.id, label: t.name }))} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-categories">Kategori</Field.Label>
          <MultiCheck
            name="categories"
            options={categories.map((c) => ({ value: c.id, label: c.name }))}
          />
        </Field.Group>

        <div class="d-flex justify-content-end gap-2">
          <button
            type="button"
            class="btn btn-outline-secondary"
            onclick={() => (createOpen = false)}
          >
            Batal
          </button>
          <button type="submit" class="btn btn-primary" disabled={processing}>Simpan</button>
        </div>
      {/snippet}
    </Form>
  {/snippet}
</FormModal>

<FormModal bind:open={editOpen} title="Ubah Artikel" subtitle={editingPost?.title ?? ''} size="lg">
  {#snippet children()}
    {#if editingPost}
      <Form
        {...update.form(editingPost.id)}
        class="d-flex flex-column gap-3"
        novalidate
        onSuccess={() => (editOpen = false)}
      >
        {#snippet children({ errors, processing })}
          <Field.Group>
            <Field.Label for="edit-title">Judul</Field.Label>
            <Field.Input
              id="edit-title"
              name="title"
              required
              value={editingPost.title}
              invalid={!!errors.title}
            />
            <Field.Feedback message={errors.title} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-slug">Slug</Field.Label>
            <Field.Input
              id="edit-slug"
              name="slug"
              value={editingPost.slug}
              invalid={!!errors.slug}
            />
            <Field.Feedback message={errors.slug} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-cover_image">Gambar Sampul</Field.Label>
            <FileDropzone
              name="cover_image"
              existingUrl={editingPost.cover_image}
              invalid={!!errors.cover_image}
            />
            <Field.Feedback message={errors.cover_image} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-excerpt">Ringkasan</Field.Label>
            <Field.Input
              id="edit-excerpt"
              name="excerpt"
              value={editingPost.excerpt}
              invalid={!!errors.excerpt}
            />
            <Field.Feedback message={errors.excerpt} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-content">Konten</Field.Label>
            <textarea
              id="edit-content"
              name="content"
              class="form-control"
              class:is-invalid={!!errors.content}
              rows="10"
              required>{editingPost.content}</textarea
            >
            <Field.Feedback message={errors.content} />
          </Field.Group>

          <input type="hidden" name="is_published" value="0" />
          <Field.Input.Check
            id="edit-is_published"
            name="is_published"
            value="1"
            checked={editingPost.is_published}
          >
            Terbitkan artikel
          </Field.Input.Check>

          <Field.Group>
            <Field.Label for="edit-published_at">Tanggal Terbit</Field.Label>
            <Field.Input
              id="edit-published_at"
              name="published_at"
              type="datetime-local"
              value={editingPost.published_at}
              invalid={!!errors.published_at}
            />
            <Field.Feedback message={errors.published_at} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-tags">Tag</Field.Label>
            <MultiCheck
              name="tags"
              options={tags.map((t) => ({ value: t.id, label: t.name }))}
              selected={editingSelectedTags}
            />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-categories">Kategori</Field.Label>
            <MultiCheck
              name="categories"
              options={categories.map((c) => ({ value: c.id, label: c.name }))}
              selected={editingSelectedCategories}
            />
          </Field.Group>

          <div class="d-flex justify-content-end gap-2">
            <button
              type="button"
              class="btn btn-outline-secondary"
              onclick={() => (editOpen = false)}
            >
              Batal
            </button>
            <button type="submit" class="btn btn-primary" disabled={processing}>Simpan</button>
          </div>
        {/snippet}
      </Form>
    {/if}
  {/snippet}
</FormModal>
