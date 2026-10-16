<script lang="ts">
  import TranslatedField from '@/components/admin/translated-field.svelte';
  import { t } from '@/lib/i18n';
  import DatePicker from '@/components/ui/date-picker.svelte';
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import { Field } from '@/components/ui/field';
  import MultiCheck from '@/components/ui/multi-check.svelte';
  import { Form } from '@inertiajs/svelte';
  import { formatDate } from '@/lib/utils';
  import {
    destroy,
    store,
    update,
    index,
  } from '@/wayfinder/routes/admin/careers';
  import type { InfiniteList, TableFilters } from '@/types/pagination';

  type PortfolioOption = { id: number; name: string };

  type Career = {
    translations?: Record<string, Record<string, string>>;
    id: number;
    position: string;
    company: string;
    location: string;
    start_date: string;
    end_date: string | null;
    portfolios: { id: number }[];
  };

  let {
    careers,
    portfolios,
    filters,
  }: {
    filters: TableFilters;
    careers: InfiniteList<Career>;
    portfolios: PortfolioOption[];
  } = $props();

  let createOpen = $state(false);
  let editOpen = $state(false);
  let editingCareer = $state<Career | null>(null);
  const editingSelectedPortfolios = $derived(
    editingCareer?.portfolios.map((p) => p.id) ?? [],
  );

  function openEdit(career: Career): void {
    editingCareer = career;
    editOpen = true;
  }
</script>

<AppHead title={t('admin-careers.riwayat_karier')} />

<AdminPageHeader
  title={t('admin-careers.riwayat_karier')}
  subtitle={t('admin-careers.kelola_riwayat_pekerjaan_anda')}
  onCreate={() => (createOpen = true)}
/>

<DataTable
  data={careers}
  scrollKey="careers"
  {filters}
  url={index().url}
  columns={[
    { label: t('admin-careers.posisi'), key: 'position', sortable: true },
    { label: t('admin-careers.perusahaan'), key: 'company', sortable: true },
    { label: t('admin-careers.lokasi'), key: 'location', sortable: true },
    { label: t('admin-careers.periode'), key: 'start_date', sortable: true },
    { label: t('admin-media.aksi'), align: 'end' },
  ]}
  emptyTitle={t('admin-careers.belum_ada_karier')}
  emptyText={t('admin-careers.tambahkan_riwayat_karier_pertama_anda')}
>
  {#snippet row(career)}
    <tr>
      <td>{career.position}</td>
      <td>{career.company}</td>
      <td>{career.location}</td>
      <td
        >{formatDate(career.start_date)} &ndash; {career.end_date
          ? formatDate(career.end_date)
          : t('admin-careers.sekarang')}</td
      >
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            onclick={() => openEdit(career)}
          >
            {t('admin.ubah')}
          </button>
          <AdminDeleteButton
            href={destroy(career.id).url}
            label={t('admin-careers.delete_confirm', { name: career.position })}
          />
        </div>
      </td>
    </tr>
  {/snippet}
</DataTable>

<FormModal
  bind:open={createOpen}
  title={t('admin-careers.tambah_karier')}
  subtitle={t('admin-careers.catat_riwayat_pekerjaan_baru')}
  size="lg"
>
  {#snippet children()}
    <Form
      {...store.form()}
      class="d-flex flex-column gap-3"
      novalidate
      onSuccess={() => (createOpen = false)}
    >
      {#snippet children({ errors, processing })}
        <TranslatedField field="position" label={t('admin-careers.posisi')} id="create-position" placeholder={t('admin-careers.masukkan_posisi')} required {errors} />

        <Field.Group>
          <Field.Label for="create-company">{t('admin-careers.perusahaan')}</Field.Label>
          <Field.Input placeholder={t('admin-careers.masukkan_perusahaan')}
            id="create-company"
            name="company"
            required
            invalid={!!errors.company}
          />
          <Field.Feedback message={errors.company} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-location">{t('admin-careers.lokasi')}</Field.Label>
          <Field.Input placeholder={t('admin-careers.masukkan_lokasi')}
            id="create-location"
            name="location"
            required
            invalid={!!errors.location}
          />
          <Field.Feedback message={errors.location} />
        </Field.Group>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-start_date">{t('admin.mulai')}</Field.Label>
              <DatePicker
                id="create-start_date"
                name="start_date"
                invalid={!!errors.start_date}
              />
              <Field.Feedback message={errors.start_date} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-end_date">{t('admin.selesai')}</Field.Label>
              <DatePicker
                id="create-end_date"
                name="end_date"
                invalid={!!errors.end_date}
              />
              <Field.Feedback message={errors.end_date} />
            </Field.Group>
          </div>
        </div>

        <Field.Group>
          <Field.Label for="create-portfolios">{t('admin-careers.portofolio_terkait')}</Field.Label>
          <MultiCheck
            name="portfolios"
            options={portfolios.map((p) => ({ value: p.id, label: p.name }))}
          />
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
  title={t('admin-careers.ubah_karier')}
  subtitle={editingCareer?.position ?? ''}
  size="lg"
>
  {#snippet children()}
    {#if editingCareer}
      {@const current = editingCareer}
      <Form
        {...update.form(current.id)}
        class="d-flex flex-column gap-3"
        novalidate
        onSuccess={() => (editOpen = false)}
      >
        {#snippet children({ errors, processing })}
          <TranslatedField field="position" label={t('admin-careers.posisi')} id="edit-position" placeholder={t('admin-careers.masukkan_posisi')} required values={current.translations?.position} {errors} />

          <Field.Group>
            <Field.Label for="edit-company">{t('admin-careers.perusahaan')}</Field.Label>
            <Field.Input placeholder={t('admin-careers.masukkan_perusahaan')}
              id="edit-company"
              name="company"
              required
              value={current.company}
              invalid={!!errors.company}
            />
            <Field.Feedback message={errors.company} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-location">{t('admin-careers.lokasi')}</Field.Label>
            <Field.Input placeholder={t('admin-careers.masukkan_lokasi')}
              id="edit-location"
              name="location"
              required
              value={current.location}
              invalid={!!errors.location}
            />
            <Field.Feedback message={errors.location} />
          </Field.Group>

          <div class="row g-3">
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-start_date">{t('admin.mulai')}</Field.Label>
                <DatePicker
                  id="edit-start_date"
                  name="start_date"
                  value={current.start_date}
                  invalid={!!errors.start_date}
                />
                <Field.Feedback message={errors.start_date} />
              </Field.Group>
            </div>
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-end_date">{t('admin.selesai')}</Field.Label>
                <DatePicker
                  id="edit-end_date"
                  name="end_date"
                  value={current.end_date}
                  invalid={!!errors.end_date}
                />
                <Field.Feedback message={errors.end_date} />
              </Field.Group>
            </div>
          </div>

          <Field.Group>
            <Field.Label for="edit-portfolios">{t('admin-careers.portofolio_terkait')}</Field.Label>
            <MultiCheck
              name="portfolios"
              options={portfolios.map((p) => ({ value: p.id, label: p.name }))}
              selected={editingSelectedPortfolios}
            />
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
