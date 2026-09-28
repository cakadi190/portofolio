<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import { Field } from '@/components/ui/field';
  import FileDropzone from '@/components/ui/file-dropzone.svelte';
  import { Form } from '@inertiajs/svelte';
  import { index, update } from '@/wayfinder/routes/admin/portfolio-galleries';

  type GalleryForm = {
    id: number;
    portfolio_id: number;
    image_url: string;
    description: string | null;
  };

  let {
    portfolioGallery,
    portfolios,
  }: { portfolioGallery: GalleryForm; portfolios: { id: number; name: string }[] } = $props();
</script>

<AppHead title="Ubah Galeri" />

<AdminPageHeader title="Ubah Foto Galeri" />

<div class="card" style="max-width: 560px;">
  <div class="card-body">
    <Form {...update.form(portfolioGallery.id)} class="d-flex flex-column gap-3" novalidate>
      {#snippet children({ errors, processing })}
        <Field.Group>
          <Field.Label for="portfolio_id">Portofolio</Field.Label>
          <select
            id="portfolio_id"
            name="portfolio_id"
            class="form-select"
            class:is-invalid={!!errors.portfolio_id}
            required
          >
            {#each portfolios as portfolio (portfolio.id)}
              <option
                value={portfolio.id}
                selected={portfolio.id === portfolioGallery.portfolio_id}
              >
                {portfolio.name}
              </option>
            {/each}
          </select>
          <Field.Feedback message={errors.portfolio_id} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="image">Gambar</Field.Label>
          <FileDropzone
            name="image"
            existingUrl={`/storage/${portfolioGallery.image_url}`}
            invalid={!!errors.image}
          />
          <Field.Feedback message={errors.image} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="description">Deskripsi</Field.Label>
          <Field.Input
            id="description"
            name="description"
            value={portfolioGallery.description}
            invalid={!!errors.description}
          />
          <Field.Feedback message={errors.description} />
        </Field.Group>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary" disabled={processing}>Simpan</button>
          <a href={index().url} class="btn btn-outline-secondary">Batal</a>
        </div>
      {/snippet}
    </Form>
  </div>
</div>
