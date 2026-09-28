<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import { Field } from '@/components/ui/field';
  import FileDropzone from '@/components/ui/file-dropzone.svelte';
  import { Form } from '@inertiajs/svelte';
  import { destroy, store, update } from '@/wayfinder/routes/admin/educations';
  import type { Paginated } from '@/types/pagination';

  type Education = {
    id: number;
    name: string;
    logo: string | null;
    website: string | null;
    level: string;
    grade: string | null;
    department: string | null;
    study_program: string | null;
    start_date: string;
    end_date: string | null;
    place: string;
    academic_score_type: string | null;
    academic_score_label: string | null;
    academic_score_value: string | null;
    academic_score_scale: string | null;
  };

  let { educations }: { educations: Paginated<Education> } = $props();

  let createOpen = $state(false);
  let editOpen = $state(false);
  let editingEducation = $state<Education | null>(null);

  function openEdit(education: Education): void {
    editingEducation = education;
    editOpen = true;
  }
</script>

<AppHead title="Riwayat Pendidikan" />

<AdminPageHeader
  title="Riwayat Pendidikan"
  subtitle="Kelola riwayat pendidikan Anda."
  onCreate={() => (createOpen = true)}
/>

{#if educations.data.length === 0}
  <EmptyState
    title="Belum ada riwayat pendidikan"
    text="Tambahkan riwayat pendidikan pertama Anda."
  />
{:else}
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Nama</th>
          <th>Jenjang</th>
          <th>Tempat</th>
          <th>Periode</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        {#each educations.data as education (education.id)}
          <tr>
            <td>{education.name}</td>
            <td>{education.level}</td>
            <td>{education.place}</td>
            <td>{education.start_date} &ndash; {education.end_date ?? 'Sekarang'}</td>
            <td class="text-end">
              <div class="d-inline-flex gap-2">
                <button
                  type="button"
                  class="btn btn-sm btn-outline-secondary"
                  onclick={() => openEdit(education)}
                >
                  Ubah
                </button>
                <AdminDeleteButton
                  href={destroy(education.id).url}
                  label={`Hapus riwayat pendidikan "${education.name}"?`}
                />
              </div>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>

  <SimplePaginator
    currentPage={educations.current_page}
    lastPage={educations.last_page}
    prevPageUrl={educations.prev_page_url}
    nextPageUrl={educations.next_page_url}
  />
{/if}

<FormModal
  bind:open={createOpen}
  title="Tambah Riwayat Pendidikan"
  subtitle="Catat riwayat pendidikan baru."
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
          <Field.Label for="create-name">Nama Institusi</Field.Label>
          <Field.Input id="create-name" name="name" required invalid={!!errors.name} />
          <Field.Feedback message={errors.name} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-logo">Logo</Field.Label>
          <FileDropzone name="logo" invalid={!!errors.logo} />
          <Field.Feedback message={errors.logo} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-website">Website</Field.Label>
          <Field.Input id="create-website" name="website" type="url" invalid={!!errors.website} />
          <Field.Feedback message={errors.website} />
        </Field.Group>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-level">Jenjang</Field.Label>
              <Field.Input id="create-level" name="level" required invalid={!!errors.level} />
              <Field.Feedback message={errors.level} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-grade">Kelas/Angkatan</Field.Label>
              <Field.Input id="create-grade" name="grade" invalid={!!errors.grade} />
              <Field.Feedback message={errors.grade} />
            </Field.Group>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-department">Jurusan</Field.Label>
              <Field.Input id="create-department" name="department" invalid={!!errors.department} />
              <Field.Feedback message={errors.department} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-study_program">Program Studi</Field.Label>
              <Field.Input
                id="create-study_program"
                name="study_program"
                invalid={!!errors.study_program}
              />
              <Field.Feedback message={errors.study_program} />
            </Field.Group>
          </div>
        </div>

        <Field.Group>
          <Field.Label for="create-place">Tempat</Field.Label>
          <Field.Input id="create-place" name="place" required invalid={!!errors.place} />
          <Field.Feedback message={errors.place} />
        </Field.Group>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-start_date">Mulai</Field.Label>
              <Field.Input
                id="create-start_date"
                name="start_date"
                type="date"
                required
                invalid={!!errors.start_date}
              />
              <Field.Feedback message={errors.start_date} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-end_date">Selesai</Field.Label>
              <Field.Input
                id="create-end_date"
                name="end_date"
                type="date"
                invalid={!!errors.end_date}
              />
              <Field.Feedback message={errors.end_date} />
            </Field.Group>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-academic_score_label">Label Nilai</Field.Label>
              <Field.Input
                id="create-academic_score_label"
                name="academic_score_label"
                placeholder="mis. IPK"
                invalid={!!errors.academic_score_label}
              />
              <Field.Feedback message={errors.academic_score_label} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-academic_score_type">Tipe Nilai</Field.Label>
              <Field.Input
                id="create-academic_score_type"
                name="academic_score_type"
                invalid={!!errors.academic_score_type}
              />
              <Field.Feedback message={errors.academic_score_type} />
            </Field.Group>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-academic_score_value">Nilai</Field.Label>
              <Field.Input
                id="create-academic_score_value"
                name="academic_score_value"
                type="number"
                step="0.01"
                invalid={!!errors.academic_score_value}
              />
              <Field.Feedback message={errors.academic_score_value} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-academic_score_scale">Skala</Field.Label>
              <Field.Input
                id="create-academic_score_scale"
                name="academic_score_scale"
                type="number"
                step="0.01"
                invalid={!!errors.academic_score_scale}
              />
              <Field.Feedback message={errors.academic_score_scale} />
            </Field.Group>
          </div>
        </div>

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
  title="Ubah Riwayat Pendidikan"
  subtitle={editingEducation?.name ?? ''}
  size="lg"
>
  {#snippet children()}
    {#if editingEducation}
      <Form
        {...update.form(editingEducation.id)}
        class="d-flex flex-column gap-3"
        novalidate
        onSuccess={() => (editOpen = false)}
      >
        {#snippet children({ errors, processing })}
          <Field.Group>
            <Field.Label for="edit-name">Nama Institusi</Field.Label>
            <Field.Input
              id="edit-name"
              name="name"
              required
              value={editingEducation.name}
              invalid={!!errors.name}
            />
            <Field.Feedback message={errors.name} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-logo">Logo</Field.Label>
            <FileDropzone
              name="logo"
              existingUrl={editingEducation.logo ? `/storage/${editingEducation.logo}` : null}
              invalid={!!errors.logo}
            />
            <Field.Feedback message={errors.logo} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-website">Website</Field.Label>
            <Field.Input
              id="edit-website"
              name="website"
              type="url"
              value={editingEducation.website}
              invalid={!!errors.website}
            />
            <Field.Feedback message={errors.website} />
          </Field.Group>

          <div class="row g-3">
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-level">Jenjang</Field.Label>
                <Field.Input
                  id="edit-level"
                  name="level"
                  required
                  value={editingEducation.level}
                  invalid={!!errors.level}
                />
                <Field.Feedback message={errors.level} />
              </Field.Group>
            </div>
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-grade">Kelas/Angkatan</Field.Label>
                <Field.Input
                  id="edit-grade"
                  name="grade"
                  value={editingEducation.grade}
                  invalid={!!errors.grade}
                />
                <Field.Feedback message={errors.grade} />
              </Field.Group>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-department">Jurusan</Field.Label>
                <Field.Input
                  id="edit-department"
                  name="department"
                  value={editingEducation.department}
                  invalid={!!errors.department}
                />
                <Field.Feedback message={errors.department} />
              </Field.Group>
            </div>
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-study_program">Program Studi</Field.Label>
                <Field.Input
                  id="edit-study_program"
                  name="study_program"
                  value={editingEducation.study_program}
                  invalid={!!errors.study_program}
                />
                <Field.Feedback message={errors.study_program} />
              </Field.Group>
            </div>
          </div>

          <Field.Group>
            <Field.Label for="edit-place">Tempat</Field.Label>
            <Field.Input
              id="edit-place"
              name="place"
              required
              value={editingEducation.place}
              invalid={!!errors.place}
            />
            <Field.Feedback message={errors.place} />
          </Field.Group>

          <div class="row g-3">
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-start_date">Mulai</Field.Label>
                <Field.Input
                  id="edit-start_date"
                  name="start_date"
                  type="date"
                  required
                  value={editingEducation.start_date}
                  invalid={!!errors.start_date}
                />
                <Field.Feedback message={errors.start_date} />
              </Field.Group>
            </div>
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-end_date">Selesai</Field.Label>
                <Field.Input
                  id="edit-end_date"
                  name="end_date"
                  type="date"
                  value={editingEducation.end_date}
                  invalid={!!errors.end_date}
                />
                <Field.Feedback message={errors.end_date} />
              </Field.Group>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-academic_score_label">Label Nilai</Field.Label>
                <Field.Input
                  id="edit-academic_score_label"
                  name="academic_score_label"
                  value={editingEducation.academic_score_label}
                  invalid={!!errors.academic_score_label}
                />
                <Field.Feedback message={errors.academic_score_label} />
              </Field.Group>
            </div>
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-academic_score_type">Tipe Nilai</Field.Label>
                <Field.Input
                  id="edit-academic_score_type"
                  name="academic_score_type"
                  value={editingEducation.academic_score_type}
                  invalid={!!errors.academic_score_type}
                />
                <Field.Feedback message={errors.academic_score_type} />
              </Field.Group>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-academic_score_value">Nilai</Field.Label>
                <Field.Input
                  id="edit-academic_score_value"
                  name="academic_score_value"
                  type="number"
                  step="0.01"
                  value={editingEducation.academic_score_value}
                  invalid={!!errors.academic_score_value}
                />
                <Field.Feedback message={errors.academic_score_value} />
              </Field.Group>
            </div>
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-academic_score_scale">Skala</Field.Label>
                <Field.Input
                  id="edit-academic_score_scale"
                  name="academic_score_scale"
                  type="number"
                  step="0.01"
                  value={editingEducation.academic_score_scale}
                  invalid={!!errors.academic_score_scale}
                />
                <Field.Feedback message={errors.academic_score_scale} />
              </Field.Group>
            </div>
          </div>

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
