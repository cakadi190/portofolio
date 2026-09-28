<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import { Field } from '@/components/ui/field';
  import FileDropzone from '@/components/ui/file-dropzone.svelte';
  import { Form } from '@inertiajs/svelte';
  import { index, store } from '@/wayfinder/routes/admin/educations';
</script>

<AppHead title="Tambah Riwayat Pendidikan" />

<AdminPageHeader title="Tambah Riwayat Pendidikan" subtitle="Catat riwayat pendidikan baru." />

<div class="card" style="max-width: 640px;">
  <div class="card-body">
    <Form {...store.form()} class="d-flex flex-column gap-3" novalidate>
      {#snippet children({ errors, processing })}
        <Field.Group>
          <Field.Label for="name">Nama Institusi</Field.Label>
          <Field.Input id="name" name="name" required invalid={!!errors.name} />
          <Field.Feedback message={errors.name} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="logo">Logo</Field.Label>
          <FileDropzone name="logo" invalid={!!errors.logo} />
          <Field.Feedback message={errors.logo} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="website">Website</Field.Label>
          <Field.Input id="website" name="website" type="url" invalid={!!errors.website} />
          <Field.Feedback message={errors.website} />
        </Field.Group>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="level">Jenjang</Field.Label>
              <Field.Input id="level" name="level" required invalid={!!errors.level} />
              <Field.Feedback message={errors.level} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="grade">Kelas/Angkatan</Field.Label>
              <Field.Input id="grade" name="grade" invalid={!!errors.grade} />
              <Field.Feedback message={errors.grade} />
            </Field.Group>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="department">Jurusan</Field.Label>
              <Field.Input id="department" name="department" invalid={!!errors.department} />
              <Field.Feedback message={errors.department} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="study_program">Program Studi</Field.Label>
              <Field.Input
                id="study_program"
                name="study_program"
                invalid={!!errors.study_program}
              />
              <Field.Feedback message={errors.study_program} />
            </Field.Group>
          </div>
        </div>

        <Field.Group>
          <Field.Label for="place">Tempat</Field.Label>
          <Field.Input id="place" name="place" required invalid={!!errors.place} />
          <Field.Feedback message={errors.place} />
        </Field.Group>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="start_date">Mulai</Field.Label>
              <Field.Input
                id="start_date"
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
              <Field.Label for="end_date">Selesai</Field.Label>
              <Field.Input id="end_date" name="end_date" type="date" invalid={!!errors.end_date} />
              <Field.Feedback message={errors.end_date} />
            </Field.Group>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="academic_score_label">Label Nilai</Field.Label>
              <Field.Input
                id="academic_score_label"
                name="academic_score_label"
                placeholder="mis. IPK"
                invalid={!!errors.academic_score_label}
              />
              <Field.Feedback message={errors.academic_score_label} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="academic_score_type">Tipe Nilai</Field.Label>
              <Field.Input
                id="academic_score_type"
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
              <Field.Label for="academic_score_value">Nilai</Field.Label>
              <Field.Input
                id="academic_score_value"
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
              <Field.Label for="academic_score_scale">Skala</Field.Label>
              <Field.Input
                id="academic_score_scale"
                name="academic_score_scale"
                type="number"
                step="0.01"
                invalid={!!errors.academic_score_scale}
              />
              <Field.Feedback message={errors.academic_score_scale} />
            </Field.Group>
          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary" disabled={processing}>Simpan</button>
          <a href={index().url} class="btn btn-outline-secondary">Batal</a>
        </div>
      {/snippet}
    </Form>
  </div>
</div>
