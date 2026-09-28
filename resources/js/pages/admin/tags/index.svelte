<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import { Field } from '@/components/ui/field';
  import { Form } from '@inertiajs/svelte';
  import { destroy, store, update } from '@/wayfinder/routes/admin/tags';
  import type { Paginated } from '@/types/pagination';

  type Tag = { id: number; name: string };

  let { tags }: { tags: Paginated<Tag> } = $props();

  let createOpen = $state(false);
  let editOpen = $state(false);
  let editingTag = $state<Tag | null>(null);

  function openEdit(tag: Tag): void {
    editingTag = tag;
    editOpen = true;
  }
</script>

<AppHead title="Tag" />

<AdminPageHeader
  title="Tag"
  subtitle="Kelola tag artikel blog."
  onCreate={() => (createOpen = true)}
/>

{#if tags.data.length === 0}
  <EmptyState title="Belum ada tag" text="Tambahkan tag pertama untuk artikel blog Anda." />
{:else}
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Nama</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        {#each tags.data as tag (tag.id)}
          <tr>
            <td>{tag.name}</td>
            <td class="text-end">
              <div class="d-inline-flex gap-2">
                <button
                  type="button"
                  class="btn btn-sm btn-outline-secondary"
                  onclick={() => openEdit(tag)}
                >
                  Ubah
                </button>
                <AdminDeleteButton href={destroy(tag.id).url} label={`Hapus tag "${tag.name}"?`} />
              </div>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>

  <SimplePaginator
    currentPage={tags.current_page}
    lastPage={tags.last_page}
    prevPageUrl={tags.prev_page_url}
    nextPageUrl={tags.next_page_url}
  />
{/if}

<FormModal bind:open={createOpen} title="Tambah Tag" subtitle="Buat tag baru untuk artikel blog.">
  {#snippet children()}
    <Form
      {...store.form()}
      class="d-flex flex-column gap-3"
      novalidate
      onSuccess={() => (createOpen = false)}
    >
      {#snippet children({ errors, processing })}
        <Field.Group>
          <Field.Label for="create-name">Nama</Field.Label>
          <Field.Input id="create-name" name="name" required invalid={!!errors.name} />
          <Field.Feedback message={errors.name} />
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

<FormModal bind:open={editOpen} title="Ubah Tag" subtitle={editingTag?.name ?? ''}>
  {#snippet children()}
    {#if editingTag}
      <Form
        {...update.form(editingTag.id)}
        class="d-flex flex-column gap-3"
        novalidate
        onSuccess={() => (editOpen = false)}
      >
        {#snippet children({ errors, processing })}
          <Field.Group>
            <Field.Label for="edit-name">Nama</Field.Label>
            <Field.Input
              id="edit-name"
              name="name"
              required
              value={editingTag.name}
              invalid={!!errors.name}
            />
            <Field.Feedback message={errors.name} />
          </Field.Group>

          <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary" onclick={() => (editOpen = false)}>
              Batal
            </button>
            <button type="submit" class="btn btn-primary" disabled={processing}>Simpan</button>
          </div>
        {/snippet}
      </Form>
    {/if}
  {/snippet}
</FormModal>
