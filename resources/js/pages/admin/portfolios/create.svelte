<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import { Field } from '@/components/ui/field';
  import FileDropzone from '@/components/ui/file-dropzone.svelte';
  import MultiCheck from '@/components/ui/multi-check.svelte';
  import { Form } from '@inertiajs/svelte';
  import { index, store } from '@/wayfinder/routes/admin/portfolios';

  let {
    technologies,
    categories,
    careers,
  }: {
    technologies: { id: number; name: string }[];
    categories: { id: number; name: string }[];
    careers: { id: number; position: string; company: string }[];
  } = $props();
</script>

<AppHead title="Tambah Portofolio" />

<AdminPageHeader title="Tambah Portofolio" subtitle="Buat entri portofolio baru." />

<div class="card" style="max-width: 720px;">
  <div class="card-body">
    <Form {...store.form()} class="d-flex flex-column gap-3" novalidate>
      {#snippet children({ errors, processing })}
        <Field.Group>
          <Field.Label for="name">Nama</Field.Label>
          <Field.Input id="name" name="name" required invalid={!!errors.name} />
          <Field.Feedback message={errors.name} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="slug">Slug</Field.Label>
          <Field.Input id="slug" name="slug" placeholder="Otomatis dari nama" invalid={!!errors.slug} />
          <Field.Feedback message={errors.slug} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="image">Gambar Sampul</Field.Label>
          <FileDropzone name="image" required invalid={!!errors.image} />
          <Field.Feedback message={errors.image} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="short_desc">Deskripsi Singkat</Field.Label>
          <Field.Input id="short_desc" name="short_desc" invalid={!!errors.short_desc} />
          <Field.Feedback message={errors.short_desc} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="description">Deskripsi</Field.Label>
          <textarea
            id="description"
            name="description"
            class="form-control"
            class:is-invalid={!!errors.description}
            rows="5"
          ></textarea>
          <Field.Feedback message={errors.description} />
        </Field.Group>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="demo_link">Tautan Demo</Field.Label>
              <Field.Input
                id="demo_link"
                name="demo_link"
                type="url"
                invalid={!!errors.demo_link}
              />
              <Field.Feedback message={errors.demo_link} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="source_code">Kode Sumber</Field.Label>
              <Field.Input
                id="source_code"
                name="source_code"
                type="url"
                invalid={!!errors.source_code}
              />
              <Field.Feedback message={errors.source_code} />
            </Field.Group>
          </div>
        </div>

        <input type="hidden" name="is_private" value="0" />
        <Field.Input.Check id="is_private" name="is_private" value="1">
          Portofolio privat
        </Field.Input.Check>

        <Field.Group>
          <Field.Label for="technologies">Teknologi</Field.Label>
          <MultiCheck
            name="technologies"
            options={technologies.map((t) => ({ value: t.id, label: t.name }))}
          />
        </Field.Group>

        <Field.Group>
          <Field.Label for="categories">Kategori</Field.Label>
          <MultiCheck
            name="categories"
            options={categories.map((c) => ({ value: c.id, label: c.name }))}
          />
        </Field.Group>

        <Field.Group>
          <Field.Label for="careers">Karier terkait</Field.Label>
          <MultiCheck
            name="careers"
            options={careers.map((c) => ({ value: c.id, label: `${c.position} — ${c.company}` }))}
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
