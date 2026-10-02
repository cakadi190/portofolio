<script lang="ts">
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
    store,
    update,
  } from '@/wayfinder/routes/admin/organizations';
  import type { Paginated, TableFilters } from '@/types/pagination';

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
  }: { filters: TableFilters; organizations: Paginated<Organization> } =
    $props();

  let createOpen = $state(false);
  let editOpen = $state(false);
  let editingOrganization = $state<Organization | null>(null);

  function openEdit(organization: Organization): void {
    editingOrganization = organization;
    editOpen = true;
  }
</script>

<AppHead title="Pengalaman Organisasi" />

<AdminPageHeader
  title="Pengalaman Organisasi"
  subtitle="Kelola riwayat pengalaman organisasi Anda."
  onCreate={() => (createOpen = true)}
/>

<DataTable
  data={organizations}
  {filters}
  url={index().url}
  columns={[
    { label: 'Nama', key: 'name', sortable: true },
    { label: 'Periode', key: 'start_date', sortable: true },
    { label: 'Aksi', align: 'end' },
  ]}
  emptyTitle="Belum ada organisasi"
  emptyText="Tambahkan pengalaman organisasi pertama Anda."
>
  {#snippet row(organization)}
    <tr>
      <td>{organization.name}</td>
      <td
        >{formatDate(organization.start_date)} &ndash; {organization.end_date
          ? formatDate(organization.end_date)
          : 'Sekarang'}</td
      >
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            onclick={() => openEdit(organization)}
          >
            Ubah
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
  title="Tambah Organisasi"
  subtitle="Catat pengalaman organisasi baru."
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
          <Field.Label for="create-name">Nama Organisasi</Field.Label>
          <Field.Input
            id="create-name"
            name="name"
            required
            invalid={!!errors.name}
          />
          <Field.Feedback message={errors.name} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-description">Deskripsi</Field.Label>
          <textarea
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
              <Field.Label for="create-start_date">Mulai</Field.Label>
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
              <Field.Label for="create-end_date">Selesai</Field.Label>
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
  title="Ubah Organisasi"
  subtitle={editingOrganization?.name ?? ''}
>
  {#snippet children()}
    {#if editingOrganization}
      <Form
        {...update.form(editingOrganization.id)}
        class="d-flex flex-column gap-3"
        novalidate
        onSuccess={() => (editOpen = false)}
      >
        {#snippet children({ errors, processing })}
          <Field.Group>
            <Field.Label for="edit-name">Nama Organisasi</Field.Label>
            <Field.Input
              id="edit-name"
              name="name"
              required
              value={editingOrganization.name}
              invalid={!!errors.name}
            />
            <Field.Feedback message={errors.name} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-description">Deskripsi</Field.Label>
            <textarea
              id="edit-description"
              name="description"
              class="form-control"
              class:is-invalid={!!errors.description}
              rows="3">{editingOrganization.description ?? ''}</textarea
            >
            <Field.Feedback message={errors.description} />
          </Field.Group>

          <div class="row g-3">
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-start_date">Mulai</Field.Label>
                <DatePicker
                  id="edit-start_date"
                  name="start_date"
                  value={editingOrganization.start_date}
                  invalid={!!errors.start_date}
                />
                <Field.Feedback message={errors.start_date} />
              </Field.Group>
            </div>
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-end_date">Selesai</Field.Label>
                <DatePicker
                  id="edit-end_date"
                  name="end_date"
                  value={editingOrganization.end_date}
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
