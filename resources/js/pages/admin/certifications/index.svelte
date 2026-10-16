<script lang="ts">
  import { t } from '@/lib/i18n';
  import DatePicker from '@/components/ui/date-picker.svelte';
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import { Field } from '@/components/ui/field';
  import Lightbox from '@/components/ui/lightbox.svelte';
  import MediaField from '@/components/media/media-field.svelte';
  import { Form } from '@inertiajs/svelte';
  import { formatDate, storageUrl } from '@/lib/utils';
  import { show as showCertification } from '@/wayfinder/routes/certifications';
  import {
    destroy,
    index,
    store,
    update,
  } from '@/wayfinder/routes/admin/certifications';
  import type { InfiniteList, TableFilters } from '@/types/pagination';

  type Certification = {
    id: number;
    title: string;
    issuer: string;
    issued_at: string;
    expires_at: string | null;
    credential_id: string | null;
    credential_url: string | null;
    file: string;
    is_pdf: boolean;
    slug: string;
  };

  let {
    certifications,
    filters,
  }: { filters: TableFilters; certifications: InfiniteList<Certification> } =
    $props();

  let createOpen = $state(false);
  let editOpen = $state(false);
  let editingCertification = $state<Certification | null>(null);

  let lightboxOpen = $state(false);
  let lightboxIndex = $state(0);

  const lightboxImages = $derived(
    certifications.data
      .filter((certification) => !certification.is_pdf)
      .map((certification) => ({
        url: storageUrl(certification.file) ?? '',
        title: certification.title,
      })),
  );

  function openLightbox(certification: Certification): void {
    lightboxIndex = lightboxImages.findIndex(
      (image) => image.url === storageUrl(certification.file),
    );
    lightboxOpen = true;
  }

  function openEdit(certification: Certification): void {
    editingCertification = certification;
    editOpen = true;
  }
</script>

<AppHead title={t('admin-certifications.sertifikasi')} />

<AdminPageHeader
  title={t('admin-certifications.sertifikasi')}
  subtitle={t('admin-certifications.kelola_sertifikat_yang_tampil_di_halaman')}
  onCreate={() => (createOpen = true)}
/>

<DataTable
  data={certifications}
  scrollKey="certifications"
  {filters}
  url={index().url}
  columns={[
    { label: t('ui.judul'), key: 'title', sortable: true },
    { label: t('admin-certifications.penerbit'), key: 'issuer', sortable: true },
    { label: t('admin-certifications.terbit'), key: 'issued_at', sortable: true },
    { label: t('admin-media.berkas') },
    { label: t('admin-media.aksi'), align: 'end' },
  ]}
  emptyTitle={t('admin-certifications.belum_ada_sertifikasi')}
  emptyText={t('admin-certifications.unggah_sertifikasi_pertama_anda')}
>
  {#snippet row(certification)}
    <tr>
      <td>{certification.title}</td>
      <td>{certification.issuer}</td>
      <td>{formatDate(certification.issued_at)}</td>
      <td>
        {#if certification.is_pdf}
          <a href={showCertification(certification.slug).url} target="_blank" rel="noopener">{t('admin-certifications.pdf')}</a>
        {:else}
          <a
            href={storageUrl(certification.file) ?? '#'}
            target="_blank"
            rel="noopener"
            onclick={(event) => {
              event.preventDefault();
              openLightbox(certification);
            }}
          >
            {t('admin.gambar')}
          </a>
        {/if}
      </td>
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            onclick={() => openEdit(certification)}
          >
            {t('admin.ubah')}
          </button>
          <AdminDeleteButton
            href={destroy(certification.id).url}
            label={`Hapus sertifikasi "${certification.title}"?`}
          />
        </div>
      </td>
    </tr>
  {/snippet}
</DataTable>

<FormModal
  bind:open={createOpen}
  title={t('admin-certifications.tambah_sertifikasi')}
  subtitle={t('admin-certifications.unggah_sertifikat_baru')}
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
        <Field.Group>
          <Field.Label for="create-title">{t('admin-certifications.judul_sertifikasi')}</Field.Label>
          <Field.Input placeholder={t('admin-certifications.masukkan_judul_sertifikasi')}
            id="create-title"
            name="title"
            required
            invalid={!!errors.title}
          />
          <Field.Feedback message={errors.title} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-issuer">{t('admin-certifications.penerbit')}</Field.Label>
          <Field.Input placeholder={t('admin-certifications.masukkan_penerbit')}
            id="create-issuer"
            name="issuer"
            required
            invalid={!!errors.issuer}
          />
          <Field.Feedback message={errors.issuer} />
        </Field.Group>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-issued_at">{t('admin-certifications.tanggal_terbit')}</Field.Label>
              <DatePicker
                id="create-issued_at"
                name="issued_at"
                invalid={!!errors.issued_at}
              />
              <Field.Feedback message={errors.issued_at} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-expires_at">{t('admin-certifications.berlaku_hingga')}</Field.Label>
              <DatePicker
                id="create-expires_at"
                name="expires_at"
                invalid={!!errors.expires_at}
              />
              <Field.Feedback message={errors.expires_at} />
            </Field.Group>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-credential_id">{t('admin-certifications.id_kredensial')}</Field.Label
              >
              <Field.Input placeholder={t('admin-certifications.masukkan_id_kredensial')}
                id="create-credential_id"
                name="credential_id"
                invalid={!!errors.credential_id}
              />
              <Field.Feedback message={errors.credential_id} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-credential_url"
                >{t('admin-certifications.url_verifikasi')}</Field.Label
              >
              <Field.Input placeholder="https://contoh.com"
                id="create-credential_url"
                name="credential_url"
                type="url"
                invalid={!!errors.credential_url}
              />
              <Field.Feedback message={errors.credential_url} />
            </Field.Group>
          </div>
        </div>

        <Field.Group>
          <Field.Label for="create-file"
            >{t('admin-certifications.berkas_sertifikat_pdf_atau_gambar')}</Field.Label
          >
          <MediaField
            name="file"
            accept="all"
            required
            invalid={!!errors.file}
          />
          <Field.Feedback message={errors.file} />
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
  title={t('admin-certifications.ubah_sertifikasi')}
  subtitle={editingCertification?.title ?? ''}
  size="lg"
>
  {#snippet children()}
    {#if editingCertification}
      {@const current = editingCertification}
      <Form
        {...update.form(current.id)}
        class="d-flex flex-column gap-3"
        novalidate
        onSuccess={() => (editOpen = false)}
      >
        {#snippet children({ errors, processing })}
          <Field.Group>
            <Field.Label for="edit-title">{t('admin-certifications.judul_sertifikasi')}</Field.Label>
            <Field.Input placeholder={t('admin-certifications.masukkan_judul_sertifikasi')}
              id="edit-title"
              name="title"
              required
              value={current.title}
              invalid={!!errors.title}
            />
            <Field.Feedback message={errors.title} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-issuer">{t('admin-certifications.penerbit')}</Field.Label>
            <Field.Input placeholder={t('admin-certifications.masukkan_penerbit')}
              id="edit-issuer"
              name="issuer"
              required
              value={current.issuer}
              invalid={!!errors.issuer}
            />
            <Field.Feedback message={errors.issuer} />
          </Field.Group>

          <div class="row g-3">
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-issued_at">{t('admin-certifications.tanggal_terbit')}</Field.Label>
                <DatePicker
                  id="edit-issued_at"
                  name="issued_at"
                  value={current.issued_at?.slice(0, 10)}
                  invalid={!!errors.issued_at}
                />
                <Field.Feedback message={errors.issued_at} />
              </Field.Group>
            </div>
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-expires_at">{t('admin-certifications.berlaku_hingga')}</Field.Label>
                <DatePicker
                  id="edit-expires_at"
                  name="expires_at"
                  value={current.expires_at?.slice(0, 10)}
                  invalid={!!errors.expires_at}
                />
                <Field.Feedback message={errors.expires_at} />
              </Field.Group>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-credential_id">{t('admin-certifications.id_kredensial')}</Field.Label
                >
                <Field.Input placeholder={t('admin-certifications.masukkan_id_kredensial')}
                  id="edit-credential_id"
                  name="credential_id"
                  value={current.credential_id}
                  invalid={!!errors.credential_id}
                />
                <Field.Feedback message={errors.credential_id} />
              </Field.Group>
            </div>
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-credential_url"
                  >{t('admin-certifications.url_verifikasi')}</Field.Label
                >
                <Field.Input placeholder="https://contoh.com"
                  id="edit-credential_url"
                  name="credential_url"
                  type="url"
                  value={current.credential_url}
                  invalid={!!errors.credential_url}
                />
                <Field.Feedback message={errors.credential_url} />
              </Field.Group>
            </div>
          </div>

          <Field.Group>
            <Field.Label for="edit-file"
              >{t('admin-certifications.berkas_sertifikat_pdf_atau_gambar')}</Field.Label
            >
            <MediaField
              name="file"
              accept="all"
              required
              value={current.file}
              invalid={!!errors.file}
            />
            <Field.Feedback message={errors.file} />
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

<Lightbox bind:open={lightboxOpen} bind:index={lightboxIndex} images={lightboxImages} />
