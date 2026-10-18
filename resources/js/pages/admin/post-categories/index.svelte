<script lang="ts">
  import TranslatedField from '@/components/admin/translated-field.svelte';
  import { t } from '@/lib/i18n';
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
    translations?: Record<string, Record<string, string>>;
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

<AppHead title={t('admin-post-categories.kategori_artikel')} />

<AdminPageHeader
  title={t('admin-post-categories.kategori_artikel')}
  subtitle={t('admin-post-categories.kelola_kategori_artikel_blog')}
  onCreate={() => (createOpen = true)}
/>

<DataTable
  data={postCategories}
  {filters}
  url={index().url}
  columns={[
    { label: t('admin.nama'), key: 'name', sortable: true },
    { label: t('admin-services.warna'), key: 'color', sortable: true },
    { label: t('admin-posts.artikel'), key: 'posts_count', sortable: true },
    { label: t('admin-media.aksi'), align: 'end' },
  ]}
  emptyTitle={t('admin-post-categories.belum_ada_kategori')}
  emptyText={t('admin-post-categories.tambahkan_kategori_artikel_pertama_anda')}
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
            {t('admin.ubah')}
          </button>
          <AdminDeleteButton
            href={destroy(category.id).url}
            label={t('admin-post-categories.delete_confirm', { name: category.name })}
          />
        </div>
      </td>
    </tr>
  {/snippet}
</DataTable>

<FormModal
  bind:open={createOpen}
  title={t('admin-post-categories.tambah_kategori_artikel')}
  subtitle={t('admin-post-categories.buat_kategori_artikel_baru')}
>
  {#snippet children()}
    <Form
      {...store.form()}
      class="d-flex flex-column gap-3"
      novalidate
      onSuccess={() => (createOpen = false)}
    >
      {#snippet children({ errors, processing })}
        <TranslatedField field="name" label={t('admin.nama')} id="create-name" placeholder={t('admin.masukkan_nama')} required {errors} />

        <Field.Group>
          <Field.Label for="create-color">{t('admin-post-categories.warna_opsional')}</Field.Label>
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
            {t('admin.batal')}
          </button>
          <button type="submit" class="btn btn-primary" disabled={processing}
            >{t('admin.simpan')}</button
          >
        </div>
      {/snippet}
    </Form>
  {/snippet}
</FormModal>

<FormModal
  bind:open={editOpen}
  title={t('admin-post-categories.ubah_kategori_artikel')}
  subtitle={editingCategory?.name ?? ''}
>
  {#snippet children()}
    {#if editingCategory}
      {@const current = editingCategory}
      <Form
        {...update.form(current.id)}
        class="d-flex flex-column gap-3"
        novalidate
        onSuccess={() => (editOpen = false)}
      >
        {#snippet children({ errors, processing })}
          <TranslatedField field="name" label={t('admin.nama')} id="edit-name" placeholder={t('admin.masukkan_nama')} required values={current.translations?.name} {errors} />

          <Field.Group>
            <Field.Label for="edit-color">{t('admin-post-categories.warna_opsional')}</Field.Label>
            <Field.Input
              id="edit-color"
              name="color"
              type="color"
              value={current.color ?? '#000000'}
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
              {t('admin.batal')}
            </button>
            <button type="submit" class="btn btn-primary" disabled={processing}
              >{t('admin.simpan')}</button
            >
          </div>
        {/snippet}
      </Form>
    {/if}
  {/snippet}
</FormModal>
