<script lang="ts">
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
  } from '@/wayfinder/routes/admin/technologies';
  import type { InfiniteList, TableFilters } from '@/types/pagination';

  type Technology = { id: number; name: string };

  let {
    technologies,
    filters,
  }: { filters: TableFilters; technologies: InfiniteList<Technology> } = $props();

  let createOpen = $state(false);
  let editOpen = $state(false);
  let editingTechnology = $state<Technology | null>(null);

  function openEdit(technology: Technology): void {
    editingTechnology = technology;
    editOpen = true;
  }
</script>

<AppHead title={t('admin.teknologi')} />

<AdminPageHeader
  title={t('admin.teknologi')}
  subtitle={t('admin-technologies.kelola_daftar_teknologi_yang_digunakan_pada')}
  onCreate={() => (createOpen = true)}
/>

<DataTable
  data={technologies}
  scrollKey="technologies"
  {filters}
  url={index().url}
  columns={[
    { label: t('admin.nama'), key: 'name', sortable: true },
    { label: t('admin-media.aksi'), align: 'end' },
  ]}
  emptyTitle={t('admin-technologies.belum_ada_teknologi')}
  emptyText={t('admin-technologies.tambahkan_teknologi_pertama_untuk_portofolio_and')}
>
  {#snippet row(technology)}
    <tr>
      <td>{technology.name}</td>
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            onclick={() => openEdit(technology)}
          >
            {t('admin.ubah')}
          </button>
          <AdminDeleteButton
            href={destroy(technology.id).url}
            label={`Hapus teknologi "${technology.name}"?`}
          />
        </div>
      </td>
    </tr>
  {/snippet}
</DataTable>

<FormModal
  bind:open={createOpen}
  title={t('admin-technologies.tambah_teknologi')}
  subtitle={t('admin-technologies.tambahkan_teknologi_baru_untuk_portofolio')}
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
          <Field.Label for="create-name">{t('admin.nama')}</Field.Label>
          <Field.Input placeholder={t('admin.masukkan_nama')}
            id="create-name"
            name="name"
            required
            invalid={!!errors.name}
          />
          <Field.Feedback message={errors.name} />
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
  title={t('admin-technologies.ubah_teknologi')}
  subtitle={editingTechnology?.name ?? ''}
>
  {#snippet children()}
    {#if editingTechnology}
      {@const current = editingTechnology}
      <Form
        {...update.form(current.id)}
        class="d-flex flex-column gap-3"
        novalidate
        onSuccess={() => (editOpen = false)}
      >
        {#snippet children({ errors, processing })}
          <Field.Group>
            <Field.Label for="edit-name">{t('admin.nama')}</Field.Label>
            <Field.Input placeholder={t('admin.masukkan_nama')}
              id="edit-name"
              name="name"
              required
              value={current.name}
              invalid={!!errors.name}
            />
            <Field.Feedback message={errors.name} />
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
