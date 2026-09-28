<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import { Field } from '@/components/ui/field';
  import { Form } from '@inertiajs/svelte';
  import { destroy, store, update } from '@/wayfinder/routes/admin/organizations';
  import type { Paginated } from '@/types/pagination';

  type Organization = {
    id: number;
    name: string;
    description: string | null;
    start_date: string;
    end_date: string | null;
  };

  let { organizations }: { organizations: Paginated<Organization> } = $props();

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

{#if organizations.data.length === 0}
  <EmptyState title="Belum ada organisasi" text="Tambahkan pengalaman organisasi pertama Anda." />
{:else}
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Nama</th>
          <th>Periode</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        {#each organizations.data as organization (organization.id)}
          <tr>
            <td>{organization.name}</td>
            <td>{organization.start_date} &ndash; {organization.end_date ?? 'Sekarang'}</td>
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
        {/each}
      </tbody>
    </table>
  </div>

  <SimplePaginator
    currentPage={organizations.current_page}
    lastPage={organizations.last_page}
    prevPageUrl={organizations.prev_page_url}
    nextPageUrl={organizations.next_page_url}
  />
{/if}

<FormModal bind:open={createOpen} title="Tambah Organisasi" subtitle="Catat pengalaman organisasi baru.">
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
          <Field.Input id="create-name" name="name" required invalid={!!errors.name} />
          <Field.Feedback message={errors.name} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-description">Deskripsi</Field.Label>
          <textarea
            id="create-description"
            name="description"
            class="form-control"
            class:is-invalid={!!errors.description}
            rows="3"
          ></textarea>
          <Field.Feedback message={errors.description} />
        </Field.Group>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-start_date">Mulai</Field.Label>
              <Field.Input
                id="create-start_date"
                name="start_date"
                type="date"
                required
                invalid={!!errors.start_date}
              />
              <Field.Feedback message={errors.start_date} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-end_date">Selesai</Field.Label>
              <Field.Input
                id="create-end_date"
                name="end_date"
                type="date"
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
          <button type="submit" class="btn btn-primary" disabled={processing}>Simpan</button>
        </div>
      {/snippet}
    </Form>
  {/snippet}
</FormModal>

<FormModal bind:open={editOpen} title="Ubah Organisasi" subtitle={editingOrganization?.name ?? ''}>
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
                <Field.Input
                  id="edit-start_date"
                  name="start_date"
                  type="date"
                  required
                  value={editingOrganization.start_date}
                  invalid={!!errors.start_date}
                />
                <Field.Feedback message={errors.start_date} />
              </Field.Group>
            </div>
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-end_date">Selesai</Field.Label>
                <Field.Input
                  id="edit-end_date"
                  name="end_date"
                  type="date"
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
            <button type="submit" class="btn btn-primary" disabled={processing}>Simpan</button>
          </div>
        {/snippet}
      </Form>
    {/if}
  {/snippet}
</FormModal>
