<script lang="ts">
  import { t } from '@/lib/i18n';
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import MediaField from '@/components/media/media-field.svelte';
  import DatePicker from '@/components/ui/date-picker.svelte';
  import { Field } from '@/components/ui/field';
  import Select from '@/components/ui/select.svelte';
  import { Form } from '@inertiajs/svelte';
  import { formatDate, storageUrl } from '@/lib/utils';
  import {
    destroy,
    index,
    store,
    update,
  } from '@/wayfinder/routes/admin/speaking-engagements';
  import type { InfiniteList, TableFilters } from '@/types/pagination';

  type Option = { value: string; label: string };

  type SpeakingEngagement = {
    id: number;
    title: string;
    organizer: string;
    role: string;
    format: string;
    location: string | null;
    starts_at: string;
    ends_at: string | null;
    registration_url: string | null;
    description: string | null;
    poster: string | null;
    is_published: boolean;
  };

  let {
    speakingEngagements,
    roles,
    formats,
    filters,
  }: {
    speakingEngagements: InfiniteList<SpeakingEngagement>;
    roles: Option[];
    formats: Option[];
    filters: TableFilters;
  } = $props();

  const roleLabels = $derived(
    Object.fromEntries(roles.map((role) => [role.value, role.label])),
  );

  let createOpen = $state(false);
  let editOpen = $state(false);
  let editingEngagement = $state<SpeakingEngagement | null>(null);

  function openEdit(engagement: SpeakingEngagement): void {
    editingEngagement = engagement;
    editOpen = true;
  }
</script>

<AppHead title={t('admin-speaking-engagements.pembicara_mentoring')} />

<AdminPageHeader
  title={t('admin-speaking-engagements.pembicara_mentoring')}
  subtitle={t('admin-speaking-engagements.kelola_acara_tempat_anda_menjadi_pembicara')}
  onCreate={() => (createOpen = true)}
/>

<DataTable
  data={speakingEngagements}
  scrollKey="speakingEngagements"
  {filters}
  url={index().url}
  columns={[
    { label: t('admin-speaking-engagements.poster') },
    { label: t('admin-awards.acara'), key: 'title', sortable: true },
    { label: t('admin.peran'), key: 'role', sortable: true },
    { label: t('admin-posts.tanggal'), key: 'starts_at', sortable: true },
    { label: t('admin.status'), key: 'is_published', sortable: true },
    { label: t('admin-media.aksi'), align: 'end' },
  ]}
  emptyTitle={t('admin-speaking-engagements.belum_ada_acara')}
  emptyText={t('admin-speaking-engagements.tambahkan_acara_pertama_anda')}
>
  {#snippet row(engagement)}
    <tr>
      <td>
        {#if engagement.poster}
          <img
            src={storageUrl(engagement.poster)}
            alt={engagement.title}
            width="48"
            height="60"
            class="rounded object-fit-cover"
          />
        {:else}
          <span class="text-muted">&mdash;</span>
        {/if}
      </td>
      <td>
        <div class="fw-semibold">{engagement.title}</div>
        <div class="text-muted small">{engagement.organizer}</div>
      </td>
      <td class="text-nowrap">{roleLabels[engagement.role] ?? engagement.role}</td>
      <td class="text-nowrap">{formatDate(engagement.starts_at)}</td>
      <td>
        <span
          class={`badge ${engagement.is_published ? 'text-bg-success' : 'text-bg-secondary'}`}
        >
          {engagement.is_published ? t('admin-portfolio-ratings.tampil') : t('admin-speaking-engagements.disembunyikan')}
        </span>
      </td>
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            onclick={() => openEdit(engagement)}
          >
            {t('admin.ubah')}
          </button>
          <AdminDeleteButton
            href={destroy(engagement.id).url}
            label={`Hapus acara "${engagement.title}"?`}
          />
        </div>
      </td>
    </tr>
  {/snippet}
</DataTable>

{#snippet fields(
  prefix: string,
  errors: Record<string, string>,
  item: SpeakingEngagement | null,
)}
  <Field.Group>
    <Field.Label for={`${prefix}-title`}>{t('admin-speaking-engagements.judul_acara')}</Field.Label>
    <Field.Input
      placeholder={t('admin-speaking-engagements.masukkan_judul_acara')}
      id={`${prefix}-title`}
      name="title"
      required
      value={item?.title}
      invalid={!!errors.title}
    />
    <Field.Feedback message={errors.title} />
  </Field.Group>

  <Field.Group>
    <Field.Label for={`${prefix}-organizer`}>{t('admin-speaking-engagements.penyelenggara')}</Field.Label>
    <Field.Input
      placeholder={t('admin-speaking-engagements.masukkan_penyelenggara')}
      id={`${prefix}-organizer`}
      name="organizer"
      required
      value={item?.organizer}
      invalid={!!errors.organizer}
    />
    <Field.Feedback message={errors.organizer} />
  </Field.Group>

  <div class="row g-3">
    <div class="col-sm-6">
      <Field.Group>
        <Field.Label for={`${prefix}-role`}>{t('admin.peran')}</Field.Label>
        <Select
          id={`${prefix}-role`}
          name="role"
          items={roles}
          required
          value={item?.role}
          invalid={!!errors.role}
        />
        <Field.Feedback message={errors.role} />
      </Field.Group>
    </div>
    <div class="col-sm-6">
      <Field.Group>
        <Field.Label for={`${prefix}-format`}>{t('admin-speaking-engagements.format')}</Field.Label>
        <Select
          id={`${prefix}-format`}
          name="format"
          items={formats}
          required
          value={item?.format}
          invalid={!!errors.format}
        />
        <Field.Feedback message={errors.format} />
      </Field.Group>
    </div>
  </div>

  <Field.Group>
    <Field.Label for={`${prefix}-location`}>{t('admin-speaking-engagements.lokasi_platform')}</Field.Label>
    <Field.Input
      placeholder={t('admin-speaking-engagements.mis_zoom_youtube_atau_gd_baru')}
      id={`${prefix}-location`}
      name="location"
      value={item?.location}
      invalid={!!errors.location}
    />
    <Field.Feedback message={errors.location} />
  </Field.Group>

  <div class="row g-3">
    <div class="col-sm-6">
      <Field.Group>
        <Field.Label for={`${prefix}-starts_at`}>{t('admin-speaking-engagements.mulai_wib')}</Field.Label>
        <DatePicker
          id={`${prefix}-starts_at`}
          name="starts_at"
          withTime
          value={item?.starts_at}
          invalid={!!errors.starts_at}
        />
        <Field.Feedback message={errors.starts_at} />
      </Field.Group>
    </div>
    <div class="col-sm-6">
      <Field.Group>
        <Field.Label for={`${prefix}-ends_at`}>{t('admin-speaking-engagements.selesai_wib')}</Field.Label>
        <DatePicker
          id={`${prefix}-ends_at`}
          name="ends_at"
          withTime
          value={item?.ends_at}
          invalid={!!errors.ends_at}
        />
        <Field.Feedback message={errors.ends_at} />
      </Field.Group>
    </div>
  </div>

  <Field.Group>
    <Field.Label for={`${prefix}-registration_url`}>{t('admin-speaking-engagements.tautan_pendaftaran')}</Field.Label>
    <Field.Input
      placeholder="https://contoh.com"
      id={`${prefix}-registration_url`}
      name="registration_url"
      type="url"
      value={item?.registration_url}
      invalid={!!errors.registration_url}
    />
    <Field.Feedback message={errors.registration_url} />
  </Field.Group>

  <Field.Group>
    <Field.Label for={`${prefix}-description`}>{t('admin.deskripsi')}</Field.Label>
    <textarea
      placeholder={t('admin-speaking-engagements.ringkasan_acara_dan_materi_yang_dibawakan')}
      id={`${prefix}-description`}
      name="description"
      class="form-control"
      class:is-invalid={!!errors.description}
      rows="3"
      value={item?.description ?? ''}></textarea>
    <Field.Feedback message={errors.description} />
  </Field.Group>

  <Field.Group>
    <Field.Label for={`${prefix}-poster`}>{t('admin-speaking-engagements.poster')}</Field.Label>
    <MediaField
      name="poster"
      accept="image"
      value={item?.poster}
      invalid={!!errors.poster}
    />
    <Field.Feedback message={errors.poster} />
  </Field.Group>

  <input type="hidden" name="is_published" value="0" />
  <Field.Input.Check
    id={`${prefix}-is_published`}
    name="is_published"
    value="1"
    checked={item?.is_published ?? true}
  >
    {t('admin.tampilkan_di_halaman_publik')}
  </Field.Input.Check>
{/snippet}

<FormModal
  bind:open={createOpen}
  title={t('admin-speaking-engagements.tambah_acara')}
  subtitle={t('admin-speaking-engagements.catat_acara_baru')}
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
        {@render fields('create', errors, null)}

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
  title={t('admin-speaking-engagements.ubah_acara')}
  subtitle={editingEngagement?.title ?? ''}
  size="lg"
>
  {#snippet children()}
    {#if editingEngagement}
      <Form
        {...update.form(editingEngagement.id)}
        class="d-flex flex-column gap-3"
        novalidate
        onSuccess={() => (editOpen = false)}
      >
        {#snippet children({ errors, processing })}
          {@render fields('edit', errors, editingEngagement)}

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
