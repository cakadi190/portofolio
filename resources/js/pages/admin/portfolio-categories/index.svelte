<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import { Field } from '@/components/ui/field';
  import { Form } from '@inertiajs/svelte';
  import { destroy, store, update } from '@/wayfinder/routes/admin/portfolio-categories';
  import type { Paginated } from '@/types/pagination';

  type Category = { id: number; name: string; color: string | null };

  let { portfolioCategories }: { portfolioCategories: Paginated<Category> } = $props();

  let createOpen = $state(false);
  let editOpen = $state(false);
  let editingCategory = $state<Category | null>(null);

  function openEdit(category: Category): void {
    editingCategory = category;
    editOpen = true;
  }
</script>

<AppHead title="Kategori Portofolio" />

<AdminPageHeader
  title="Kategori Portofolio"
  subtitle="Kelola kategori portofolio."
  onCreate={() => (createOpen = true)}
/>

{#if portfolioCategories.data.length === 0}
  <EmptyState title="Belum ada kategori" text="Tambahkan kategori portofolio pertama Anda." />
{:else}
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Nama</th>
          <th>Warna</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        {#each portfolioCategories.data as category (category.id)}
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
        {/each}
      </tbody>
    </table>
  </div>

  <SimplePaginator
    currentPage={portfolioCategories.current_page}
    lastPage={portfolioCategories.last_page}
    prevPageUrl={portfolioCategories.prev_page_url}
    nextPageUrl={portfolioCategories.next_page_url}
  />
{/if}

<FormModal bind:open={createOpen} title="Tambah Kategori Portofolio" subtitle="Buat kategori portofolio baru.">
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

        <Field.Group>
          <Field.Label for="create-color">Warna (opsional)</Field.Label>
          <Field.Input id="create-color" name="color" type="color" invalid={!!errors.color} />
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
          <button type="submit" class="btn btn-primary" disabled={processing}>Simpan</button>
        </div>
      {/snippet}
    </Form>
  {/snippet}
</FormModal>

<FormModal bind:open={editOpen} title="Ubah Kategori Portofolio" subtitle={editingCategory?.name ?? ''}>
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
            <button type="submit" class="btn btn-primary" disabled={processing}>Simpan</button>
          </div>
        {/snippet}
      </Form>
    {/if}
  {/snippet}
</FormModal>
