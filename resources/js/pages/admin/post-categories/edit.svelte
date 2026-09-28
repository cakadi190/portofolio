<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import { Field } from '@/components/ui/field';
  import { Form } from '@inertiajs/svelte';
  import { index, update } from '@/wayfinder/routes/admin/post-categories';

  let {
    postCategory,
  }: { postCategory: { id: number; name: string; color: string | null } } = $props();
</script>

<AppHead title="Ubah Kategori Artikel" />

<AdminPageHeader title="Ubah Kategori Artikel" subtitle={postCategory.name} />

<div class="card" style="max-width: 480px;">
  <div class="card-body">
    <Form {...update.form(postCategory.id)} class="d-flex flex-column gap-3" novalidate>
      {#snippet children({ errors, processing })}
        <Field.Group>
          <Field.Label for="name">Nama</Field.Label>
          <Field.Input
            id="name"
            name="name"
            required
            value={postCategory.name}
            invalid={!!errors.name}
          />
          <Field.Feedback message={errors.name} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="color">Warna (opsional)</Field.Label>
          <Field.Input
            id="color"
            name="color"
            type="color"
            value={postCategory.color ?? '#000000'}
            invalid={!!errors.color}
          />
          <Field.Feedback message={errors.color} />
        </Field.Group>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary" disabled={processing}>Simpan</button>
          <a href={index().url} class="btn btn-outline-secondary">Batal</a>
        </div>
      {/snippet}
    </Form>
  </div>
</div>
