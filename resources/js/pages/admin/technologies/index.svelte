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
  } from '@/wayfinder/routes/admin/technologies';
  import type { Paginated, TableFilters } from '@/types/pagination';

  type Technology = { id: number; name: string };

  let {
    technologies,
    filters,
  }: { filters: TableFilters; technologies: Paginated<Technology> } = $props();

  let createOpen = $state(false);
  let editOpen = $state(false);
  let editingTechnology = $state<Technology | null>(null);

  function openEdit(technology: Technology): void {
    editingTechnology = technology;
    editOpen = true;
  }
</script>

<AppHead title="Teknologi" />

<AdminPageHeader
  title="Teknologi"
  subtitle="Kelola daftar teknologi yang digunakan pada portofolio."
  onCreate={() => (createOpen = true)}
/>

<DataTable
  data={technologies}
  {filters}
  url={index().url}
  columns={[
    { label: 'Nama', key: 'name', sortable: true },
    { label: 'Aksi', align: 'end' },
  ]}
  emptyTitle="Belum ada teknologi"
  emptyText="Tambahkan teknologi pertama untuk portofolio Anda."
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
            Ubah
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
  title="Tambah Teknologi"
  subtitle="Tambahkan teknologi baru untuk portofolio."
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
  title="Ubah Teknologi"
  subtitle={editingTechnology?.name ?? ''}
>
  {#snippet children()}
    {#if editingTechnology}
      <Form
        {...update.form(editingTechnology.id)}
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
              value={editingTechnology.name}
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
