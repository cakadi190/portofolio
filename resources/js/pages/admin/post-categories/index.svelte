<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import { Field } from '@/components/ui/field';
  import { Form } from '@inertiajs/svelte';
  import {
    destroy,
    store,
    update,
    index,
  } from '@/wayfinder/routes/admin/post-categories';
  import type { Paginated, TableFilters } from '@/types/pagination';

  type Category = {
    id: number;
    name: string;
    color: string | null;
    posts_count: number;
  };

  let {
    postCategories,
    filters,
  }: { filters: TableFilters; postCategories: Paginated<Category> } = $props();

  let createOpen = $state(false);
  let editOpen = $state(false);
  let editingCategory = $state<Category | null>(null);

  function openEdit(category: Category): void {
    editingCategory = category;
    editOpen = true;
  }
</script>

<AppHead title="Kategori Artikel" />

<AdminPageHeader
  title="Kategori Artikel"
  subtitle="Kelola kategori artikel blog."
  onCreate={() => (createOpen = true)}
/>

<DataTable
  data={postCategories}
  {filters}
  url={index().url}
  columns={[
    { label: 'Nama', key: 'name', sortable: true },
    { label: 'Warna', key: 'color', sortable: true },
    { label: 'Artikel', key: 'posts_count', sortable: true },
    { label: 'Aksi', align: 'end' },
  ]}
  emptyTitle="Belum ada kategori"
  emptyText="Tambahkan kategori artikel pertama Anda."
>
  {#snippet row(category)}
    <tr>
      <td>{category.name}</td>
      <td>
        {#if category.color}
          <span class="badge" style={`background-color:${category.color}`}
            >{category.color}</span
          >
        {:else}
          <span class="text-muted">&mdash;</span>
        {/if}
      </td>
      <td>{category.posts_count}</td>
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            onclick={() => openEdit(category)}
          >
            Ubah
          </button>
          <AdminDeleteButton
            href={destroy(category.id).url}
            label={`Hapus kategori "${category.name}"?`}
          />
        </div>
      </td>
    </tr>
  {/snippet}
</DataTable>

<FormModal
  bind:open={createOpen}
  title="Tambah Kategori Artikel"
  subtitle="Buat kategori artikel baru."
>
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
          <Field.Input
            id="create-name"
            name="name"
            required
            invalid={!!errors.name}
          />
          <Field.Feedback message={errors.name} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-color">Warna (opsional)</Field.Label>
          <Field.Input
            id="create-color"
            name="color"
            type="color"
            invalid={!!errors.color}
          />
          <Field.Feedback message={errors.color} />
        </Field.Group>

        <div class="d-flex justify-content-end gap-2">
          <button
            type="button"
            class="btn btn-outline-secondary"
            onclick={() => (createOpen = false)}
          >
            Batal
          </button>
          <button type="submit" class="btn btn-primary" disabled={processing}
            >Simpan</button
          >
        </div>
      {/snippet}
    </Form>
  {/snippet}
</FormModal>

<FormModal
  bind:open={editOpen}
  title="Ubah Kategori Artikel"
  subtitle={editingCategory?.name ?? ''}
>
  {#snippet children()}
    {#if editingCategory}
      <Form
        {...update.form(editingCategory.id)}
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
              value={editingCategory.name}
              invalid={!!errors.name}
            />
            <Field.Feedback message={errors.name} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-color">Warna (opsional)</Field.Label>
            <Field.Input
              id="edit-color"
              name="color"
              type="color"
              value={editingCategory.color ?? '#000000'}
              invalid={!!errors.color}
            />
            <Field.Feedback message={errors.color} />
          </Field.Group>

          <div class="d-flex justify-content-end gap-2">
            <button
              type="button"
              class="btn btn-outline-secondary"
              onclick={() => (editOpen = false)}
            >
              Batal
            </button>
            <button type="submit" class="btn btn-primary" disabled={processing}
              >Simpan</button
            >
          </div>
        {/snippet}
      </Form>
    {/if}
  {/snippet}
</FormModal>
