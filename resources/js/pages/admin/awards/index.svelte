<script lang="ts">
  import { t } from '@/lib/i18n';
  import DatePicker from '@/components/ui/date-picker.svelte';
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import { Field } from '@/components/ui/field';
  import Select from '@/components/ui/select.svelte';
  import Icon from '@iconify/svelte';
  import { Form } from '@inertiajs/svelte';
  import {
    destroy,
    store,
    update,
    index,
  } from '@/wayfinder/routes/admin/awards';
  import type { InfiniteList, TableFilters } from '@/types/pagination';

  type Award = {
    id: number;
    event_name: string;
    title: string;
    type: string;
    icon: string;
    year: number;
    rank: number | null;
    awarded_at: string | null;
  };

  let {
    awards,
    types,
    filters,
  }: {
    filters: TableFilters;
    awards: InfiniteList<Award>;
    types: { value: string; label: string }[];
  } = $props();

  const typeLabels = $derived(
    Object.fromEntries(types.map((type) => [type.value, type.label])),
  );

  let createOpen = $state(false);
  let editOpen = $state(false);
  let editingAward = $state<Award | null>(null);

  function openEdit(award: Award): void {
    editingAward = award;
    editOpen = true;
  }
</script>

<AppHead title={t('admin-awards.penghargaan')} />

<AdminPageHeader
  title={t('admin-awards.penghargaan')}
  subtitle={t('admin-awards.kelola_penghargaan_dan_sertifikat_anda')}
  onCreate={() => (createOpen = true)}
/>

<DataTable
  data={awards}
  scrollKey="awards"
  {filters}
  url={index().url}
  columns={[
    { label: t('admin-awards.ikon') },
    { label: t('admin-awards.judul'), key: 'title', sortable: true },
    { label: t('ui.jenis'), key: 'type', sortable: true },
    { label: t('admin-awards.acara'), key: 'event_name', sortable: true },
    { label: t('admin-awards.tahun'), key: 'year', sortable: true },
    { label: t('admin-awards.peringkat'), key: 'rank', sortable: true },
    { label: t('admin-media.aksi'), align: 'end' },
  ]}
  emptyTitle={t('admin-awards.belum_ada_penghargaan')}
  emptyText={t('admin-awards.tambahkan_penghargaan_pertama_anda')}
>
  {#snippet row(award)}
    <tr>
      <td>
        <Icon icon={award.icon} width={28} height={28} />
      </td>
      <td>{award.title}</td>
      <td class="text-nowrap">{typeLabels[award.type] ?? award.type}</td>
      <td>{award.event_name}</td>
      <td>{award.year}</td>
      <td>{award.rank ?? '—'}</td>
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            onclick={() => openEdit(award)}
          >
            {t('admin.ubah')}
          </button>
          <AdminDeleteButton
            href={destroy(award.id).url}
            label={`Hapus penghargaan "${award.title}"?`}
          />
        </div>
      </td>
    </tr>
  {/snippet}
</DataTable>

<FormModal
  bind:open={createOpen}
  title={t('admin-awards.tambah_penghargaan')}
  subtitle={t('admin-awards.catat_penghargaan_atau_sertifikat_baru')}
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
          <Field.Label for="create-title">{t('admin-awards.judul')}</Field.Label>
          <Field.Input placeholder={t('admin-awards.masukkan_judul')}
            id="create-title"
            name="title"
            required
            invalid={!!errors.title}
          />
          <Field.Feedback message={errors.title} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-type">{t('admin-awards.jenis_penghargaan')}</Field.Label>
          <Select
            id="create-type"
            name="type"
            items={types}
            required
            invalid={!!errors.type}
          />
          <Field.Feedback message={errors.type} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-event_name">{t('admin-awards.nama_acara')}</Field.Label>
          <Field.Input placeholder={t('admin-awards.masukkan_nama_acara')}
            id="create-event_name"
            name="event_name"
            required
            invalid={!!errors.event_name}
          />
          <Field.Feedback message={errors.event_name} />
        </Field.Group>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-year">{t('admin-awards.tahun')}</Field.Label>
              <Field.Input placeholder="0"
                id="create-year"
                name="year"
                type="number"
                required
                invalid={!!errors.year}
              />
              <Field.Feedback message={errors.year} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-rank">{t('admin-awards.peringkat')}</Field.Label>
              <Field.Input placeholder="0"
                id="create-rank"
                name="rank"
                type="number"
                invalid={!!errors.rank}
              />
              <Field.Feedback message={errors.rank} />
            </Field.Group>
          </div>
        </div>

        <Field.Group>
          <Field.Label for="create-awarded_at">{t('admin-awards.tanggal_diberikan')}</Field.Label>
          <DatePicker
            id="create-awarded_at"
            name="awarded_at"
            invalid={!!errors.awarded_at}
          />
          <Field.Feedback message={errors.awarded_at} />
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
  title={t('admin-awards.ubah_penghargaan')}
  subtitle={editingAward?.title ?? ''}
>
  {#snippet children()}
    {#if editingAward}
      {@const current = editingAward}
      <Form
        {...update.form(current.id)}
        class="d-flex flex-column gap-3"
        novalidate
        onSuccess={() => (editOpen = false)}
      >
        {#snippet children({ errors, processing })}
          <Field.Group>
            <Field.Label for="edit-title">{t('admin-awards.judul')}</Field.Label>
            <Field.Input placeholder={t('admin-awards.masukkan_judul')}
              id="edit-title"
              name="title"
              required
              value={current.title}
              invalid={!!errors.title}
            />
            <Field.Feedback message={errors.title} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-type">{t('admin-awards.jenis_penghargaan')}</Field.Label>
            <Select
              id="edit-type"
              name="type"
              items={types}
              required
              value={current.type}
              invalid={!!errors.type}
            />
            <Field.Feedback message={errors.type} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-event_name">{t('admin-awards.nama_acara')}</Field.Label>
            <Field.Input placeholder={t('admin-awards.masukkan_nama_acara')}
              id="edit-event_name"
              name="event_name"
              required
              value={current.event_name}
              invalid={!!errors.event_name}
            />
            <Field.Feedback message={errors.event_name} />
          </Field.Group>

          <div class="row g-3">
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-year">{t('admin-awards.tahun')}</Field.Label>
                <Field.Input placeholder="0"
                  id="edit-year"
                  name="year"
                  type="number"
                  required
                  value={current.year}
                  invalid={!!errors.year}
                />
                <Field.Feedback message={errors.year} />
              </Field.Group>
            </div>
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-rank">{t('admin-awards.peringkat')}</Field.Label>
                <Field.Input placeholder="0"
                  id="edit-rank"
                  name="rank"
                  type="number"
                  value={current.rank}
                  invalid={!!errors.rank}
                />
                <Field.Feedback message={errors.rank} />
              </Field.Group>
            </div>
          </div>

          <Field.Group>
            <Field.Label for="edit-awarded_at">{t('admin-awards.tanggal_diberikan')}</Field.Label>
            <DatePicker
              id="edit-awarded_at"
              name="awarded_at"
              value={current.awarded_at}
              invalid={!!errors.awarded_at}
            />
            <Field.Feedback message={errors.awarded_at} />
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
