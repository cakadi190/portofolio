<script lang="ts">
  import { t } from '@/lib/i18n';
  import DatePicker from '@/components/ui/date-picker.svelte';
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import { Field } from '@/components/ui/field';
  import { Form } from '@inertiajs/svelte';
  import { formatDate } from '@/lib/utils';
  import {
    destroy,
    index,
    store,
    update,
  } from '@/wayfinder/routes/admin/organizations';
  import type { InfiniteList, TableFilters } from '@/types/pagination';

  type Organization = {
    id: number;
    name: string;
    description: string | null;
    start_date: string;
    end_date: string | null;
  };

  let {
    organizations,
    filters,
  }: { filters: TableFilters; organizations: InfiniteList<Organization> } =
    $props();

  let createOpen = $state(false);
  let editOpen = $state(false);
  let editingOrganization = $state<Organization | null>(null);

  function openEdit(organization: Organization): void {
    editingOrganization = organization;
    editOpen = true;
  }
</script>

<AppHead title={t('admin-organizations.pengalaman_organisasi')} />

<AdminPageHeader
  title={t('admin-organizations.pengalaman_organisasi')}
  subtitle={t('admin-organizations.kelola_riwayat_pengalaman_organisasi_anda')}
  onCreate={() => (createOpen = true)}
/>

<DataTable
  data={organizations}
  scrollKey="organizations"
  {filters}
  url={index().url}
  columns={[
    { label: t('admin.nama'), key: 'name', sortable: true },
    { label: t('admin-careers.periode'), key: 'start_date', sortable: true },
    { label: t('admin-media.aksi'), align: 'end' },
  ]}
  emptyTitle={t('admin-organizations.belum_ada_organisasi')}
  emptyText={t('admin-organizations.tambahkan_pengalaman_organisasi_pertama_anda')}
>
  {#snippet row(organization)}
    <tr>
      <td>{organization.name}</td>
      <td
        >{formatDate(organization.start_date)} &ndash; {organization.end_date
          ? formatDate(organization.end_date)
          : t('admin-careers.sekarang')}</td
      >
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            onclick={() => openEdit(organization)}
          >
            {t('admin.ubah')}
          </button>
          <AdminDeleteButton
            href={destroy(organization.id).url}
            label={`Hapus organisasi "${organization.name}"?`}
          />
        </div>
      </td>
    </tr>
  {/snippet}
</DataTable>

<FormModal
  bind:open={createOpen}
  title={t('admin-organizations.tambah_organisasi')}
  subtitle={t('admin-organizations.catat_pengalaman_organisasi_baru')}
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
          <Field.Label for="create-name">{t('admin-organizations.nama_organisasi')}</Field.Label>
          <Field.Input placeholder={t('admin-organizations.masukkan_nama_organisasi')}
            id="create-name"
            name="name"
            required
            invalid={!!errors.name}
          />
          <Field.Feedback message={errors.name} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-description">{t('admin.deskripsi')}</Field.Label>
          <textarea placeholder={t('admin.masukkan_deskripsi')}
            id="create-description"
            name="description"
            class="form-control"
            class:is-invalid={!!errors.description}
            rows="3"></textarea>
          <Field.Feedback message={errors.description} />
        </Field.Group>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-start_date">{t('admin.mulai')}</Field.Label>
              <DatePicker
                id="create-start_date"
                name="start_date"
                invalid={!!errors.start_date}
              />
              <Field.Feedback message={errors.start_date} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-end_date">{t('admin.selesai')}</Field.Label>
              <DatePicker
                id="create-end_date"
                name="end_date"
                invalid={!!errors.end_date}
              />
              <Field.Feedback message={errors.end_date} />
            </Field.Group>
          </div>
        </div>

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
  title={t('admin-organizations.ubah_organisasi')}
  subtitle={editingOrganization?.name ?? ''}
>
  {#snippet children()}
    {#if editingOrganization}
      {@const current = editingOrganization}
      <Form
        {...update.form(current.id)}
        class="d-flex flex-column gap-3"
        novalidate
        onSuccess={() => (editOpen = false)}
      >
        {#snippet children({ errors, processing })}
          <Field.Group>
            <Field.Label for="edit-name">{t('admin-organizations.nama_organisasi')}</Field.Label>
            <Field.Input placeholder={t('admin-organizations.masukkan_nama_organisasi')}
              id="edit-name"
              name="name"
              required
              value={current.name}
              invalid={!!errors.name}
            />
            <Field.Feedback message={errors.name} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-description">{t('admin.deskripsi')}</Field.Label>
            <textarea placeholder={t('admin.masukkan_deskripsi')}
              id="edit-description"
              name="description"
              class="form-control"
              class:is-invalid={!!errors.description}
              rows="3">{current.description ?? ''}</textarea
            >
            <Field.Feedback message={errors.description} />
          </Field.Group>

          <div class="row g-3">
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-start_date">{t('admin.mulai')}</Field.Label>
                <DatePicker
                  id="edit-start_date"
                  name="start_date"
                  value={current.start_date}
                  invalid={!!errors.start_date}
                />
                <Field.Feedback message={errors.start_date} />
              </Field.Group>
            </div>
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-end_date">{t('admin.selesai')}</Field.Label>
                <DatePicker
                  id="edit-end_date"
                  name="end_date"
                  value={current.end_date}
                  invalid={!!errors.end_date}
                />
                <Field.Feedback message={errors.end_date} />
              </Field.Group>
            </div>
          </div>

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
