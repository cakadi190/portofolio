<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import { Field } from '@/components/ui/field';
  import { Form } from '@inertiajs/svelte';
  import { index, store } from '@/wayfinder/routes/admin/portfolio-ratings';

  let { portfolios }: { portfolios: { id: number; name: string }[] } = $props();
</script>

<AppHead title="Tambah Ulasan" />

<AdminPageHeader title="Tambah Ulasan Portofolio" subtitle="Catat ulasan baru." />

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
          <Field.Label for="rating">Rating (1-5)</Field.Label>
          <Field.Input
            id="rating"
            name="rating"
            type="number"
            min="1"
            max="5"
            required
            invalid={!!errors.rating}
          />
          <Field.Feedback message={errors.rating} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="comment">Komentar</Field.Label>
          <textarea
            id="comment"
            name="comment"
            class="form-control"
            class:is-invalid={!!errors.comment}
            rows="3"
          ></textarea>
          <Field.Feedback message={errors.comment} />
        </Field.Group>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary" disabled={processing}>Simpan</button>
          <a href={index().url} class="btn btn-outline-secondary">Batal</a>
        </div>
      {/snippet}
    </Form>
  </div>
</div>
