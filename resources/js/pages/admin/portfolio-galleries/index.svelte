<script lang="ts">
  import { t } from '@/lib/i18n';
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import { Field } from '@/components/ui/field';
  import Select from '@/components/ui/select.svelte';
  import MediaField from '@/components/media/media-field.svelte';
  import { Form } from '@inertiajs/svelte';
  import { storageUrl } from '@/lib/utils';
  import {
    destroy,
    index,
    store,
    update,
  } from '@/wayfinder/routes/admin/portfolio-galleries';
  import type { Paginated, TableFilters } from '@/types/pagination';

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
    filters,
  }: {
    portfolioGalleries: Paginated<Gallery>;
    portfolios: PortfolioOption[];
    filters: TableFilters;
  } = $props();

  let createOpen = $state(false);
  let editOpen = $state(false);
  let editingGallery = $state<Gallery | null>(null);

  function openEdit(gallery: Gallery): void {
    editingGallery = gallery;
    editOpen = true;
  }
</script>

<AppHead title={t('admin-portfolio-galleries.galeri_portofolio')} />

<AdminPageHeader
  title={t('admin-portfolio-galleries.galeri_portofolio')}
  subtitle={t('admin-portfolio-galleries.kelola_foto_galeri_untuk_tiap_portofolio')}
  onCreate={() => (createOpen = true)}
/>

<DataTable
  data={portfolioGalleries}
  {filters}
  url={index().url}
  variant="grid"
  emptyTitle={t('admin-portfolio-galleries.belum_ada_galeri')}
  emptyText={t('admin-portfolio-galleries.tambahkan_foto_galeri_pertama_anda')}
>
  {#snippet row(gallery)}
    <div class="col-sm-6 col-lg-4">
      <div class="card h-100">
        <img
          src={storageUrl(gallery.image_url)}
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
              {t('admin.ubah')}
            </button>
            <AdminDeleteButton
              href={destroy(gallery.id).url}
              label={t('admin-portfolio-galleries.delete_confirm', { name: gallery.portfolio.name })}
            />
          </div>
        </div>
      </div>
    </div>
  {/snippet}
</DataTable>

<FormModal
  bind:open={createOpen}
  title={t('admin-portfolio-galleries.tambah_galeri_portofolio')}
  subtitle={t('admin-portfolio-galleries.unggah_foto_galeri_baru')}
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
          <Field.Label for="create-image">{t('admin.foto')}</Field.Label>
          <MediaField name="image_url" required invalid={!!errors.image_url} />
          <Field.Feedback message={errors.image_url} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-description">{t('admin.deskripsi')}</Field.Label>
          <Field.Input placeholder={t('admin.masukkan_deskripsi')}
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
  title={t('admin-portfolio-galleries.ubah_galeri_portofolio')}
  subtitle={editingGallery?.portfolio.name ?? ''}
>
  {#snippet children()}
    {#if editingGallery}
      {@const current = editingGallery}
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
            <Field.Label for="edit-image">{t('admin.foto')}</Field.Label>
            <MediaField
              name="image_url"
              required
              value={current.image_url}
              invalid={!!errors.image_url}
            />
            <Field.Feedback message={errors.image_url} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-description">{t('admin.deskripsi')}</Field.Label>
            <Field.Input placeholder={t('admin.masukkan_deskripsi')}
              id="edit-description"
              name="description"
              value={current.description}
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
