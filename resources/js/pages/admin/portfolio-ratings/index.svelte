<script lang="ts">
  import { t } from '@/lib/i18n';
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import { Field } from '@/components/ui/field';
  import RichTextEditor from '@/components/ui/rich-text-editor.svelte';
  import Select from '@/components/ui/select.svelte';
  import { Form } from '@inertiajs/svelte';
  import {
    destroy,
    store,
    update,
    index,
  } from '@/wayfinder/routes/admin/portfolio-ratings';
  import type { InfiniteList, TableFilters } from '@/types/pagination';

  type PortfolioOption = { id: number; name: string };

  type Rating = {
    id: number;
    portfolio_id: number;
    reviewer_name: string;
    reviewer_email: string;
    reviewer_company: string | null;
    title: string;
    rating: number;
    comment: string;
    is_approved: boolean;
    portfolio: PortfolioOption;
  };

  let {
    portfolioRatings,
    portfolios,
    filters,
  }: {
    filters: TableFilters;
    portfolioRatings: InfiniteList<Rating>;
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

<AppHead title={t('admin-portfolio-ratings.ulasan_portofolio')} />

<AdminPageHeader
  title={t('admin-portfolio-ratings.ulasan_portofolio')}
  subtitle={t('admin-portfolio-ratings.kelola_ulasan_yang_masuk_untuk_portofolio')}
  onCreate={() => (createOpen = true)}
/>

<DataTable
  data={portfolioRatings}
  scrollKey="portfolioRatings"
  {filters}
  url={index().url}
  columns={[
    { label: t('admin.portofolio') },
    { label: t('admin-portfolio-ratings.pengulas') },
    { label: t('admin.status') },
    { label: t('admin-portfolio-ratings.rating'), key: 'rating', sortable: true },
    { label: t('ui.judul') },
    { label: t('admin-media.aksi'), align: 'end' },
  ]}
  emptyTitle={t('admin-portfolio-ratings.belum_ada_ulasan')}
  emptyText={t('admin-portfolio-ratings.ulasan_portofolio_akan_muncul_di_sini')}
>
  {#snippet row(rating)}
    <tr>
      <td>{rating.portfolio.name}</td>
      <td
        >{rating.reviewer_name}<br /><small class="opacity-75"
          >{rating.reviewer_email}</small
        ></td
      >
      <td>
        <span
          class="badge"
          class:text-bg-success={rating.is_approved}
          class:text-bg-warning={!rating.is_approved}
        >
          {rating.is_approved ? t('admin-portfolio-ratings.tampil') : t('admin-portfolio-ratings.menunggu')}
        </span>
      </td>
      <td>{rating.rating} / 5</td>
      <td>{rating.title}</td>
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            onclick={() => openEdit(rating)}
          >
            {t('admin.ubah')}
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
  title={t('admin-portfolio-ratings.tambah_ulasan_portofolio')}
  subtitle={t('admin-portfolio-ratings.catat_ulasan_baru')}
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
          <Field.Label for="create-portfolio_id">{t('admin.portofolio')}</Field.Label>
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
          <Field.Label for="create-reviewer_name">{t('admin-portfolio-ratings.nama_pengulas')}</Field.Label>
          <Field.Input
            id="create-reviewer_name"
            name="reviewer_name"
            required
            invalid={!!errors.reviewer_name}
          />
          <Field.Feedback message={errors.reviewer_name} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-reviewer_email">{t('admin-portfolio-ratings.email_pengulas')}</Field.Label>
          <Field.Input
            id="create-reviewer_email"
            name="reviewer_email"
            type="email"
            required
            invalid={!!errors.reviewer_email}
          />
          <Field.Feedback message={errors.reviewer_email} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-reviewer_company"
            >{t('admin-portfolio-ratings.perusahaan_jabatan')}</Field.Label
          >
          <Field.Input
            id="create-reviewer_company"
            name="reviewer_company"
            invalid={!!errors.reviewer_company}
          />
          <Field.Feedback message={errors.reviewer_company} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-title">{t('admin-portfolio-ratings.judul_ulasan')}</Field.Label>
          <Field.Input
            id="create-title"
            name="title"
            required
            invalid={!!errors.title}
          />
          <Field.Feedback message={errors.title} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-rating">{t('admin-portfolio-ratings.rating')}</Field.Label>
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
          <Field.Label>{t('admin-portfolio-ratings.isi_ulasan')}</Field.Label>
          <RichTextEditor name="comment" minimal invalid={!!errors.comment} />
          <Field.Feedback message={errors.comment} />
        </Field.Group>

        <input type="hidden" name="is_approved" value="0" />
        <Field.Input.Check id="create-is_approved" name="is_approved" value="1"
          >{t('admin.tampilkan_di_halaman_publik')}</Field.Input.Check
        >

        <div class="d-flex justify-content-end gap-2">
          <button
            type="button"
            class="btn btn-outline-secondary"
            onclick={() => (createOpen = false)}
          >
            {t('admin.batal')}
          </button>
          <button type="submit" class="btn btn-primary" disabled={processing}
            >{t('admin.simpan')}</button
          >
        </div>
      {/snippet}
    </Form>
  {/snippet}
</FormModal>

<FormModal
  bind:open={editOpen}
  title={t('admin-portfolio-ratings.ubah_ulasan_portofolio')}
  subtitle={editingRating?.portfolio.name ?? ''}
>
  {#snippet children()}
    {#if editingRating}
      {@const current = editingRating}
      <Form
        {...update.form(current.id)}
        class="d-flex flex-column gap-3"
        novalidate
        onSuccess={() => (editOpen = false)}
      >
        {#snippet children({ errors, processing })}
          <Field.Group>
            <Field.Label for="edit-portfolio_id">{t('admin.portofolio')}</Field.Label>
            <Select
              id="edit-portfolio_id"
              name="portfolio_id"
              items={portfolios.map((option) => ({
                value: option.id,
                label: option.name,
              }))}
              value={current.portfolio_id}
              required
              invalid={!!errors.portfolio_id}
            />
            <Field.Feedback message={errors.portfolio_id} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-reviewer_name">{t('admin-portfolio-ratings.nama_pengulas')}</Field.Label>
            <Field.Input
              id="edit-reviewer_name"
              name="reviewer_name"
              value={current.reviewer_name}
              required
              invalid={!!errors.reviewer_name}
            />
            <Field.Feedback message={errors.reviewer_name} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-reviewer_email">{t('admin-portfolio-ratings.email_pengulas')}</Field.Label>
            <Field.Input
              id="edit-reviewer_email"
              name="reviewer_email"
              type="email"
              value={current.reviewer_email}
              required
              invalid={!!errors.reviewer_email}
            />
            <Field.Feedback message={errors.reviewer_email} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-reviewer_company"
              >{t('admin-portfolio-ratings.perusahaan_jabatan')}</Field.Label
            >
            <Field.Input
              id="edit-reviewer_company"
              name="reviewer_company"
              value={current.reviewer_company}
              invalid={!!errors.reviewer_company}
            />
            <Field.Feedback message={errors.reviewer_company} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-title">{t('admin-portfolio-ratings.judul_ulasan')}</Field.Label>
            <Field.Input
              id="edit-title"
              name="title"
              value={current.title}
              required
              invalid={!!errors.title}
            />
            <Field.Feedback message={errors.title} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-rating">{t('admin-portfolio-ratings.rating')}</Field.Label>
            <Select
              id="edit-rating"
              name="rating"
              items={[1, 2, 3, 4, 5].map((value) => ({
                value,
                label: String(value),
              }))}
              value={current.rating}
              required
              invalid={!!errors.rating}
              searchable={false}
            />
            <Field.Feedback message={errors.rating} />
          </Field.Group>

          <Field.Group>
            <Field.Label>{t('admin-portfolio-ratings.isi_ulasan')}</Field.Label>
            <RichTextEditor
              name="comment"
              minimal
              value={current.comment}
              invalid={!!errors.comment}
            />
            <Field.Feedback message={errors.comment} />
          </Field.Group>

          <input type="hidden" name="is_approved" value="0" />
          <Field.Input.Check
            id="edit-is_approved"
            name="is_approved"
            value="1"
            checked={current.is_approved}
            >{t('admin.tampilkan_di_halaman_publik')}</Field.Input.Check
          >

          <div class="d-flex justify-content-end gap-2">
            <button
              type="button"
              class="btn btn-outline-secondary"
              onclick={() => (editOpen = false)}
            >
              {t('admin.batal')}
            </button>
            <button type="submit" class="btn btn-primary" disabled={processing}
              >{t('admin.simpan')}</button
            >
          </div>
        {/snippet}
      </Form>
    {/if}
  {/snippet}
</FormModal>
