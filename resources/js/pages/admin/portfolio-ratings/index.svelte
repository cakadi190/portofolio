<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import { Field } from '@/components/ui/field';
  import { Form } from '@inertiajs/svelte';
  import { select2 } from '@/lib/select2';
  import { destroy, store, update } from '@/wayfinder/routes/admin/portfolio-ratings';
  import type { Paginated } from '@/types/pagination';

  type PortfolioOption = { id: number; name: string };

  type Rating = {
    id: number;
    portfolio_id: number;
    rating: number;
    comment: string | null;
    portfolio: PortfolioOption;
  };

  let {
    portfolioRatings,
    portfolios,
  }: { portfolioRatings: Paginated<Rating>; portfolios: PortfolioOption[] } = $props();

  let createOpen = $state(false);
  let editOpen = $state(false);
  let editingRating = $state<Rating | null>(null);

  function openEdit(rating: Rating): void {
    editingRating = rating;
    editOpen = true;
  }
</script>

<AppHead title="Ulasan Portofolio" />

<AdminPageHeader
  title="Ulasan Portofolio"
  subtitle="Kelola ulasan yang masuk untuk portofolio Anda."
  onCreate={() => (createOpen = true)}
/>

{#if portfolioRatings.data.length === 0}
  <EmptyState title="Belum ada ulasan" text="Ulasan portofolio akan muncul di sini." />
{:else}
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Portofolio</th>
          <th>Rating</th>
          <th>Komentar</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        {#each portfolioRatings.data as rating (rating.id)}
          <tr>
            <td>{rating.portfolio.name}</td>
            <td>{rating.rating} / 5</td>
            <td>{rating.comment ?? '—'}</td>
            <td class="text-end">
              <div class="d-inline-flex gap-2">
                <button
                  type="button"
                  class="btn btn-sm btn-outline-secondary"
                  onclick={() => openEdit(rating)}
                >
                  Ubah
                </button>
                <AdminDeleteButton
                  href={destroy(rating.id).url}
                  label={`Hapus ulasan untuk "${rating.portfolio.name}"?`}
                />
              </div>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>

  <SimplePaginator
    currentPage={portfolioRatings.current_page}
    lastPage={portfolioRatings.last_page}
    prevPageUrl={portfolioRatings.prev_page_url}
    nextPageUrl={portfolioRatings.next_page_url}
  />
{/if}

<FormModal bind:open={createOpen} title="Tambah Ulasan Portofolio" subtitle="Catat ulasan baru.">
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
          <Field.Label for="create-rating">Rating</Field.Label>
          <select
            id="create-rating"
            name="rating"
            class="form-select"
            class:is-invalid={!!errors.rating}
            required
            use:select2={{ minimumResultsForSearch: Infinity }}
          >
            {#each [1, 2, 3, 4, 5] as value (value)}
              <option {value}>{value}</option>
            {/each}
          </select>
          <Field.Feedback message={errors.rating} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-comment">Komentar</Field.Label>
          <textarea
            id="create-comment"
            name="comment"
            class="form-control"
            class:is-invalid={!!errors.comment}
            rows="3"
          ></textarea>
          <Field.Feedback message={errors.comment} />
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
  title="Ubah Ulasan Portofolio"
  subtitle={editingRating?.portfolio.name ?? ''}
>
  {#snippet children()}
    {#if editingRating}
      <Form
        {...update.form(editingRating.id)}
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
              value={editingRating.portfolio_id}
              use:select2
            >
              {#each portfolios as option (option.id)}
                <option value={option.id}>{option.name}</option>
              {/each}
            </select>
            <Field.Feedback message={errors.portfolio_id} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-rating">Rating</Field.Label>
            <select
              id="edit-rating"
              name="rating"
              class="form-select"
              class:is-invalid={!!errors.rating}
              required
              value={editingRating.rating}
              use:select2={{ minimumResultsForSearch: Infinity }}
            >
              {#each [1, 2, 3, 4, 5] as value (value)}
                <option {value}>{value}</option>
              {/each}
            </select>
            <Field.Feedback message={errors.rating} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-comment">Komentar</Field.Label>
            <textarea
              id="edit-comment"
              name="comment"
              class="form-control"
              class:is-invalid={!!errors.comment}
              rows="3">{editingRating.comment ?? ''}</textarea
            >
            <Field.Feedback message={errors.comment} />
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
