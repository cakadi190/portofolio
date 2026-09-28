<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import { Field } from '@/components/ui/field';
  import FileDropzone from '@/components/ui/file-dropzone.svelte';
  import { Form } from '@inertiajs/svelte';
  import { index, update } from '@/wayfinder/routes/admin/awards';

  type AwardForm = {
    id: number;
    event_name: string;
    title: string;
    icon: string | null;
    year: number;
    rank: number | null;
    awarded_at: string | null;
  };

  let { award }: { award: AwardForm } = $props();
</script>

<AppHead title="Ubah Penghargaan" />

<AdminPageHeader title="Ubah Penghargaan" subtitle={award.title} />

<div class="card" style="max-width: 560px;">
  <div class="card-body">
    <Form {...update.form(award.id)} class="d-flex flex-column gap-3" novalidate>
      {#snippet children({ errors, processing })}
        <Field.Group>
          <Field.Label for="title">Judul</Field.Label>
          <Field.Input
            id="title"
            name="title"
            required
            value={award.title}
            invalid={!!errors.title}
          />
          <Field.Feedback message={errors.title} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="event_name">Nama Acara</Field.Label>
          <Field.Input
            id="event_name"
            name="event_name"
            required
            value={award.event_name}
            invalid={!!errors.event_name}
          />
          <Field.Feedback message={errors.event_name} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="icon">Ikon</Field.Label>
          <FileDropzone
            name="icon"
            existingUrl={award.icon ? `/storage/${award.icon}` : null}
            invalid={!!errors.icon}
          />
          <Field.Feedback message={errors.icon} />
        </Field.Group>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="year">Tahun</Field.Label>
              <Field.Input
                id="year"
                name="year"
                type="number"
                required
                value={award.year}
                invalid={!!errors.year}
              />
              <Field.Feedback message={errors.year} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="rank">Peringkat</Field.Label>
              <Field.Input
                id="rank"
                name="rank"
                type="number"
                value={award.rank}
                invalid={!!errors.rank}
              />
              <Field.Feedback message={errors.rank} />
            </Field.Group>
          </div>
        </div>

        <Field.Group>
          <Field.Label for="awarded_at">Tanggal Diberikan</Field.Label>
          <Field.Input
            id="awarded_at"
            name="awarded_at"
            type="date"
            value={award.awarded_at}
            invalid={!!errors.awarded_at}
          />
          <Field.Feedback message={errors.awarded_at} />
        </Field.Group>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary" disabled={processing}>Simpan</button>
          <a href={index().url} class="btn btn-outline-secondary">Batal</a>
        </div>
      {/snippet}
    </Form>
  </div>
</div>
