<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import { Field } from '@/components/ui/field';
  import { Form } from '@inertiajs/svelte';
  import { index, update } from '@/wayfinder/routes/admin/organizations';

  type OrganizationForm = {
    id: number;
    name: string;
    description: string | null;
    start_date: string;
    end_date: string | null;
  };

  let { organization }: { organization: OrganizationForm } = $props();
</script>

<AppHead title="Ubah Organisasi" />

<AdminPageHeader title="Ubah Organisasi" subtitle={organization.name} />

<div class="card" style="max-width: 640px;">
  <div class="card-body">
    <Form {...update.form(organization.id)} class="d-flex flex-column gap-3" novalidate>
      {#snippet children({ errors, processing })}
        <Field.Group>
          <Field.Label for="name">Nama Organisasi</Field.Label>
          <Field.Input
            id="name"
            name="name"
            required
            value={organization.name}
            invalid={!!errors.name}
          />
          <Field.Feedback message={errors.name} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="description">Deskripsi</Field.Label>
          <textarea
            id="description"
            name="description"
            class="form-control"
            class:is-invalid={!!errors.description}
            rows="3">{organization.description ?? ''}</textarea
          >
          <Field.Feedback message={errors.description} />
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
                value={organization.start_date}
                invalid={!!errors.start_date}
              />
              <Field.Feedback message={errors.start_date} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="end_date">Selesai</Field.Label>
              <Field.Input
                id="end_date"
                name="end_date"
                type="date"
                value={organization.end_date}
                invalid={!!errors.end_date}
              />
              <Field.Feedback message={errors.end_date} />
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
