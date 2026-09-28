<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import { Field } from '@/components/ui/field';
  import FileDropzone from '@/components/ui/file-dropzone.svelte';
  import { Form } from '@inertiajs/svelte';
  import { select2 } from '@/lib/select2';
  import { destroy, store, update } from '@/wayfinder/routes/admin/portfolio-galleries';
  import type { Paginated } from '@/types/pagination';

  type PortfolioOption = { id: number; name: string };

  type Gallery = {
    id: number;
    portfolio_id: number;
    image_url: string;
    description: string | null;
    portfolio: PortfolioOption;
  };

  let {
    portfolioGalleries,
    portfolios,
  }: { portfolioGalleries: Paginated<Gallery>; portfolios: PortfolioOption[] } = $props();

  let createOpen = $state(false);
  let editOpen = $state(false);
  let editingGallery = $state<Gallery | null>(null);

  function openEdit(gallery: Gallery): void {
    editingGallery = gallery;
    editOpen = true;
  }
</script>

<AppHead title="Galeri Portofolio" />

<AdminPageHeader
  title="Galeri Portofolio"
  subtitle="Kelola foto galeri untuk tiap portofolio."
  onCreate={() => (createOpen = true)}
/>

{#if portfolioGalleries.data.length === 0}
  <EmptyState title="Belum ada galeri" text="Tambahkan foto galeri pertama Anda." />
{:else}
  <div class="row g-3">
    {#each portfolioGalleries.data as gallery (gallery.id)}
      <div class="col-sm-6 col-lg-4">
        <div class="card h-100">
          <img
            src={`/storage/${gallery.image_url}`}
            alt={gallery.description ?? gallery.portfolio.name}
            class="card-img-top"
            style="height: 10rem; object-fit: cover;"
          />
          <div class="card-body">
            <p class="fw-semibold mb-1">{gallery.portfolio.name}</p>
            <p class="text-muted small mb-3">{gallery.description ?? '—'}</p>
            <div class="d-flex gap-2">
              <button
                type="button"
                class="btn btn-sm btn-outline-secondary"
                onclick={() => openEdit(gallery)}
              >
                Ubah
              </button>
              <AdminDeleteButton
                href={destroy(gallery.id).url}
                label={`Hapus foto galeri "${gallery.portfolio.name}"?`}
              />
            </div>
          </div>
        </div>
      </div>
    {/each}
  </div>

  <div class="mt-3">
    <SimplePaginator
      currentPage={portfolioGalleries.current_page}
      lastPage={portfolioGalleries.last_page}
      prevPageUrl={portfolioGalleries.prev_page_url}
      nextPageUrl={portfolioGalleries.next_page_url}
    />
  </div>
{/if}

<FormModal bind:open={createOpen} title="Tambah Galeri Portofolio" subtitle="Unggah foto galeri baru.">
  {#snippet children()}
    <Form
      {...store.form()}
      class="d-flex flex-column gap-3"
      novalidate
      onSuccess={() => (createOpen = false)}
    >
      {#snippet children({ errors, processing })}
        <Field.Group>
          <Field.Label for="create-portfolio_id">Portofolio</Field.Label>
          <select
            id="create-portfolio_id"
            name="portfolio_id"
            class="form-select"
            class:is-invalid={!!errors.portfolio_id}
            required
            use:select2
          >
            {#each portfolios as option (option.id)}
              <option value={option.id}>{option.name}</option>
            {/each}
          </select>
          <Field.Feedback message={errors.portfolio_id} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-image">Foto</Field.Label>
          <FileDropzone name="image" required invalid={!!errors.image} />
          <Field.Feedback message={errors.image} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-description">Deskripsi</Field.Label>
          <Field.Input
            id="create-description"
            name="description"
            invalid={!!errors.description}
          />
          <Field.Feedback message={errors.description} />
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

<FormModal
  bind:open={editOpen}
  title="Ubah Galeri Portofolio"
  subtitle={editingGallery?.portfolio.name ?? ''}
>
  {#snippet children()}
    {#if editingGallery}
      <Form
        {...update.form(editingGallery.id)}
        class="d-flex flex-column gap-3"
        novalidate
        onSuccess={() => (editOpen = false)}
      >
        {#snippet children({ errors, processing })}
          <Field.Group>
            <Field.Label for="edit-portfolio_id">Portofolio</Field.Label>
            <select
              id="edit-portfolio_id"
              name="portfolio_id"
              class="form-select"
              class:is-invalid={!!errors.portfolio_id}
              required
              value={editingGallery.portfolio_id}
              use:select2
            >
              {#each portfolios as option (option.id)}
                <option value={option.id}>{option.name}</option>
              {/each}
            </select>
            <Field.Feedback message={errors.portfolio_id} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-image">Foto</Field.Label>
            <FileDropzone
              name="image"
              existingUrl={`/storage/${editingGallery.image_url}`}
              invalid={!!errors.image}
            />
            <Field.Feedback message={errors.image} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-description">Deskripsi</Field.Label>
            <Field.Input
              id="edit-description"
              name="description"
              value={editingGallery.description}
              invalid={!!errors.description}
            />
            <Field.Feedback message={errors.description} />
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
