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
  import { destroy, store, update, index } from '@/wayfinder/routes/admin/tags';
  import type { InfiniteList, TableFilters } from '@/types/pagination';

  type Tag = {
    id: number;
    name: string;
    translations?: Record<string, Record<string, string>>;
  };

  let { tags, filters }: { filters: TableFilters; tags: InfiniteList<Tag> } =
    $props();

  let createOpen = $state(false);
  let editOpen = $state(false);
  let editingTag = $state<Tag | null>(null);

  function openEdit(tag: Tag): void {
    editingTag = tag;
    editOpen = true;
  }
</script>

<AppHead title={t('admin.tag')} />

<AdminPageHeader
  title={t('admin.tag')}
  subtitle={t('admin-tags.kelola_tag_artikel_blog')}
  onCreate={() => (createOpen = true)}
/>

<DataTable
  data={tags}
  scrollKey="tags"
  {filters}
  url={index().url}
  columns={[
    { label: t('admin.nama'), key: 'name', sortable: true },
    { label: t('admin-media.aksi'), align: 'end' },
  ]}
  emptyTitle={t('admin-tags.belum_ada_tag')}
  emptyText={t('admin-tags.tambahkan_tag_pertama_untuk_artikel_blog')}
>
  {#snippet row(tag)}
    <tr>
      <td>{tag.name}</td>
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            onclick={() => openEdit(tag)}
          >
            {t('admin.ubah')}
          </button>
          <AdminDeleteButton
            href={destroy(tag.id).url}
            label={t('admin-tags.delete_confirm', { name: tag.name })}
          />
        </div>
      </td>
    </tr>
  {/snippet}
</DataTable>

<FormModal
  bind:open={createOpen}
  title={t('admin-tags.tambah_tag')}
  subtitle={t('admin-tags.buat_tag_baru_untuk_artikel_blog')}
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
  title={t('admin-tags.ubah_tag')}
  subtitle={editingTag?.name ?? ''}
>
  {#snippet children()}
    {#if editingTag}
      {@const current = editingTag}
      <Form
        {...update.form(current.id)}
        class="d-flex flex-column gap-3"
        novalidate
        onSuccess={() => (editOpen = false)}
      >
        {#snippet children({ errors, processing })}
          <TranslatedField field="name" label={t('admin.nama')} id="edit-name" placeholder={t('admin.masukkan_nama')} required values={current.translations?.name} {errors} />

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
