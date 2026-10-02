<script lang="ts">
  import DatePicker from '@/components/ui/date-picker.svelte';
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import { Field } from '@/components/ui/field';
  import MediaField from '@/components/media/media-field.svelte';
  import { Form } from '@inertiajs/svelte';
  import { formatDate, storageUrl } from '@/lib/utils';
  import {
    destroy,
    store,
    update,
  } from '@/wayfinder/routes/admin/certifications';
  import type { Paginated, TableFilters } from '@/types/pagination';

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
  };

  let {
    certifications,
    filters,
  }: { filters: TableFilters; certifications: Paginated<Certification> } =
    $props();

  let createOpen = $state(false);
  let editOpen = $state(false);
  let editingCertification = $state<Certification | null>(null);

  function openEdit(certification: Certification): void {
    editingCertification = certification;
    editOpen = true;
  }
</script>

<AppHead title="Sertifikasi" />

<AdminPageHeader
  title="Sertifikasi"
  subtitle="Kelola sertifikat yang tampil di halaman Tentang Saya."
  onCreate={() => (createOpen = true)}
/>

<DataTable
  data={certifications}
  {filters}
  url={index().url}
  columns={[
    { label: 'Judul', key: 'title', sortable: true },
    { label: 'Penerbit', key: 'issuer', sortable: true },
    { label: 'Terbit', key: 'issued_at', sortable: true },
    { label: 'Berkas' },
    { label: 'Aksi', align: 'end' },
  ]}
  emptyTitle="Belum ada sertifikasi"
  emptyText="Unggah sertifikasi pertama Anda."
>
  {#snippet row(certification)}
    <tr>
      <td>{certification.title}</td>
      <td>{certification.issuer}</td>
      <td>{formatDate(certification.issued_at)}</td>
      <td>
        <a
          href={storageUrl(certification.file) ?? '#'}
          target="_blank"
          rel="noopener"
        >
          {certification.is_pdf ? 'PDF' : 'Gambar'}
        </a>
      </td>
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            onclick={() => openEdit(certification)}
          >
            Ubah
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
  title="Tambah Sertifikasi"
  subtitle="Unggah sertifikat baru."
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
          <Field.Label for="create-title">Judul Sertifikasi</Field.Label>
          <Field.Input
            id="create-title"
            name="title"
            required
            invalid={!!errors.title}
          />
          <Field.Feedback message={errors.title} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-issuer">Penerbit</Field.Label>
          <Field.Input
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
              <Field.Label for="create-issued_at">Tanggal Terbit</Field.Label>
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
              <Field.Label for="create-expires_at">Berlaku Hingga</Field.Label>
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
              <Field.Label for="create-credential_id">ID Kredensial</Field.Label
              >
              <Field.Input
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
                >URL Verifikasi</Field.Label
              >
              <Field.Input
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
            >Berkas Sertifikat (PDF atau gambar)</Field.Label
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
  title="Ubah Sertifikasi"
  subtitle={editingCertification?.title ?? ''}
  size="lg"
>
  {#snippet children()}
    {#if editingCertification}
      <Form
        {...update.form(editingCertification.id)}
        class="d-flex flex-column gap-3"
        novalidate
        onSuccess={() => (editOpen = false)}
      >
        {#snippet children({ errors, processing })}
          <Field.Group>
            <Field.Label for="edit-title">Judul Sertifikasi</Field.Label>
            <Field.Input
              id="edit-title"
              name="title"
              required
              value={editingCertification.title}
              invalid={!!errors.title}
            />
            <Field.Feedback message={errors.title} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-issuer">Penerbit</Field.Label>
            <Field.Input
              id="edit-issuer"
              name="issuer"
              required
              value={editingCertification.issuer}
              invalid={!!errors.issuer}
            />
            <Field.Feedback message={errors.issuer} />
          </Field.Group>

          <div class="row g-3">
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-issued_at">Tanggal Terbit</Field.Label>
                <DatePicker
                  id="edit-issued_at"
                  name="issued_at"
                  value={editingCertification.issued_at?.slice(0, 10)}
                  invalid={!!errors.issued_at}
                />
                <Field.Feedback message={errors.issued_at} />
              </Field.Group>
            </div>
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-expires_at">Berlaku Hingga</Field.Label>
                <DatePicker
                  id="edit-expires_at"
                  name="expires_at"
                  value={editingCertification.expires_at?.slice(0, 10)}
                  invalid={!!errors.expires_at}
                />
                <Field.Feedback message={errors.expires_at} />
              </Field.Group>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-credential_id">ID Kredensial</Field.Label
                >
                <Field.Input
                  id="edit-credential_id"
                  name="credential_id"
                  value={editingCertification.credential_id}
                  invalid={!!errors.credential_id}
                />
                <Field.Feedback message={errors.credential_id} />
              </Field.Group>
            </div>
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-credential_url"
                  >URL Verifikasi</Field.Label
                >
                <Field.Input
                  id="edit-credential_url"
                  name="credential_url"
                  type="url"
                  value={editingCertification.credential_url}
                  invalid={!!errors.credential_url}
                />
                <Field.Feedback message={errors.credential_url} />
              </Field.Group>
            </div>
          </div>

          <Field.Group>
            <Field.Label for="edit-file"
              >Berkas Sertifikat (PDF atau gambar)</Field.Label
            >
            <MediaField
              name="file"
              accept="all"
              required
              value={editingCertification.file}
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
