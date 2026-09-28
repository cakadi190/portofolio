<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import { Field } from '@/components/ui/field';
  import MultiCheck from '@/components/ui/multi-check.svelte';
  import { Form } from '@inertiajs/svelte';
  import { index, store } from '@/wayfinder/routes/admin/careers';

  let { portfolios }: { portfolios: { id: number; name: string }[] } = $props();
</script>

<AppHead title="Tambah Karier" />

<AdminPageHeader title="Tambah Karier" subtitle="Catat riwayat pekerjaan baru." />

<div class="card" style="max-width: 640px;">
  <div class="card-body">
    <Form {...store.form()} class="d-flex flex-column gap-3" novalidate>
      {#snippet children({ errors, processing })}
        <Field.Group>
          <Field.Label for="position">Posisi</Field.Label>
          <Field.Input id="position" name="position" required invalid={!!errors.position} />
          <Field.Feedback message={errors.position} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="company">Perusahaan</Field.Label>
          <Field.Input id="company" name="company" required invalid={!!errors.company} />
          <Field.Feedback message={errors.company} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="location">Lokasi</Field.Label>
          <Field.Input id="location" name="location" required invalid={!!errors.location} />
          <Field.Feedback message={errors.location} />
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

        <Field.Group>
          <Field.Label for="portfolios">Portofolio terkait</Field.Label>
          <MultiCheck
            name="portfolios"
            options={portfolios.map((p) => ({ value: p.id, label: p.name }))}
          />
        </Field.Group>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary" disabled={processing}>Simpan</button>
          <a href={index().url} class="btn btn-outline-secondary">Batal</a>
        </div>
      {/snippet}
    </Form>
  </div>
</div>
