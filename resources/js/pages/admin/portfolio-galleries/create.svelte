<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import { Field } from '@/components/ui/field';
  import FileDropzone from '@/components/ui/file-dropzone.svelte';
  import { Form } from '@inertiajs/svelte';
  import { index, store } from '@/wayfinder/routes/admin/portfolio-galleries';

  let { portfolios }: { portfolios: { id: number; name: string }[] } = $props();
</script>

<AppHead title="Tambah Galeri" />

<AdminPageHeader title="Tambah Foto Galeri" subtitle="Unggah foto baru untuk sebuah portofolio." />

<div class="card" style="max-width: 560px;">
  <div class="card-body">
    <Form {...store.form()} class="d-flex flex-column gap-3" novalidate>
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
            <option value="">Pilih portofolio</option>
            {#each portfolios as portfolio (portfolio.id)}
              <option value={portfolio.id}>{portfolio.name}</option>
            {/each}
          </select>
          <Field.Feedback message={errors.portfolio_id} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="image">Gambar</Field.Label>
          <FileDropzone name="image" required invalid={!!errors.image} />
          <Field.Feedback message={errors.image} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="description">Deskripsi</Field.Label>
          <Field.Input id="description" name="description" invalid={!!errors.description} />
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
