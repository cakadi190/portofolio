<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import { Field } from '@/components/ui/field';
  import Select from '@/components/ui/select.svelte';
  import { Form } from '@inertiajs/svelte';
  import {
    destroy,
    store,
    update,
    index,
  } from '@/wayfinder/routes/admin/portfolio-ratings';
  import type { Paginated, TableFilters } from '@/types/pagination';

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
    filters,
  }: {
    filters: TableFilters;
    portfolioRatings: Paginated<Rating>;
    portfolios: PortfolioOption[];
  } = $props();

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

<DataTable
  data={portfolioRatings}
  {filters}
  url={index().url}
  columns={[
    { label: 'Portofolio' },
    { label: 'Rating', key: 'rating', sortable: true },
    { label: 'Komentar' },
    { label: 'Aksi', align: 'end' },
  ]}
  emptyTitle="Belum ada ulasan"
  emptyText="Ulasan portofolio akan muncul di sini."
>
  {#snippet row(rating)}
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
  {/snippet}
</DataTable>

<FormModal
  bind:open={createOpen}
  title="Tambah Ulasan Portofolio"
  subtitle="Catat ulasan baru."
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
          <Field.Label for="create-portfolio_id">Portofolio</Field.Label>
          <Select
            id="create-portfolio_id"
            name="portfolio_id"
            items={portfolios.map((option) => ({
              value: option.id,
              label: option.name,
            }))}
            required
            invalid={!!errors.portfolio_id}
          />
          <Field.Feedback message={errors.portfolio_id} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-rating">Rating</Field.Label>
          <Select
            id="create-rating"
            name="rating"
            items={[1, 2, 3, 4, 5].map((value) => ({
              value,
              label: String(value),
            }))}
            required
            invalid={!!errors.rating}
            searchable={false}
          />
          <Field.Feedback message={errors.rating} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-comment">Komentar</Field.Label>
          <textarea
            id="create-comment"
            name="comment"
            class="form-control"
            class:is-invalid={!!errors.comment}
            rows="3"></textarea>
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
            <Select
              id="edit-portfolio_id"
              name="portfolio_id"
              items={portfolios.map((option) => ({
                value: option.id,
                label: option.name,
              }))}
              value={editingRating.portfolio_id}
              required
              invalid={!!errors.portfolio_id}
            />
            <Field.Feedback message={errors.portfolio_id} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-rating">Rating</Field.Label>
            <Select
              id="edit-rating"
              name="rating"
              items={[1, 2, 3, 4, 5].map((value) => ({
                value,
                label: String(value),
              }))}
              value={editingRating.rating}
              required
              invalid={!!errors.rating}
              searchable={false}
            />
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
            <button type="submit" class="btn btn-primary" disabled={processing}
              >Simpan</button
            >
          </div>
        {/snippet}
      </Form>
    {/if}
  {/snippet}
</FormModal>
