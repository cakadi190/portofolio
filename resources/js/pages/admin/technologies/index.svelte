<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import { Field } from '@/components/ui/field';
  import { Form } from '@inertiajs/svelte';
  import { destroy, store, update } from '@/wayfinder/routes/admin/technologies';
  import type { Paginated } from '@/types/pagination';

  type Technology = { id: number; name: string };

  let { technologies }: { technologies: Paginated<Technology> } = $props();

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

{#if technologies.data.length === 0}
  <EmptyState
    title="Belum ada teknologi"
    text="Tambahkan teknologi pertama untuk portofolio Anda."
  />
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
        {#each technologies.data as technology (technology.id)}
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
        {/each}
      </tbody>
    </table>
  </div>

  <SimplePaginator
    currentPage={technologies.current_page}
    lastPage={technologies.last_page}
    prevPageUrl={technologies.prev_page_url}
    nextPageUrl={technologies.next_page_url}
  />
{/if}

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

<FormModal bind:open={editOpen} title="Ubah Teknologi" subtitle={editingTechnology?.name ?? ''}>
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
            <button type="submit" class="btn btn-primary" disabled={processing}>Simpan</button>
          </div>
        {/snippet}
      </Form>
    {/if}
  {/snippet}
</FormModal>
