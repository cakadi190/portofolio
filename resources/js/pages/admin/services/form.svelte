<script lang="ts">
  import ArrowLeft from '@lucide/svelte/icons/arrow-left';
  import ExternalLink from '@lucide/svelte/icons/external-link';
  import { Form, Link } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import { Field } from '@/components/ui/field';
  import MediaField from '@/components/media/media-field.svelte';
  import RichTextEditor from '@/components/ui/rich-text-editor.svelte';
  import { index, update } from '@/wayfinder/routes/admin/services';
  import { show } from '@/wayfinder/routes/services';

  type Service = {
    id: number;
    name: string;
    slug: string;
    color: string | null;
    image: string | null;
    description: string | null;
  };

  let { service }: { service: Service } = $props();
</script>

<AppHead title="Ubah Layanan" />

<Form {...update.form(service.id)} novalidate class="post-editor">
  {#snippet children({ errors, processing })}
    <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
      <Link
        href={index().url}
        class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2"
      >
        <ArrowLeft size={14} />
        Semua Layanan
      </Link>
      <h1 class="h5 mb-0">Ubah Layanan</h1>
    </div>

    <div class="row g-4">
      <div class="col-lg-9 d-flex flex-column gap-3">
        <div>
          <input
            id="service-name"
            name="name"
            type="text"
            class="form-control form-control-lg fw-semibold"
            class:is-invalid={!!errors.name}
            placeholder="Nama layanan"
            value={service.name}
            required
          />
          <Field.Feedback message={errors.name} />
        </div>

        <div class="d-flex align-items-center gap-2 small">
          <label for="service-slug" class="text-muted text-nowrap">Permalink:</label>
          <input
            id="service-slug"
            name="slug"
            type="text"
            class="form-control form-control-sm"
            class:is-invalid={!!errors.slug}
            value={service.slug}
            required
          />
          <a
            href={show(service.slug).url}
            target="_blank"
            rel="noopener"
            class="text-nowrap"
            title="Lihat layanan"
          >
            <ExternalLink size={14} />
          </a>
        </div>
        <Field.Feedback message={errors.slug} />

        <div>
          <RichTextEditor
            name="description"
            value={service.description ?? ''}
            invalid={!!errors.description}
          />
          <Field.Feedback message={errors.description} />
        </div>
      </div>

      <aside class="col-lg-3 d-flex flex-column gap-3">
        <div class="card">
          <div class="card-header fw-semibold">Simpan</div>
          <div class="card-body">
            <button type="submit" class="btn btn-primary w-100" disabled={processing}>
              Perbarui
            </button>
          </div>
        </div>

        <div class="card">
          <div class="card-header fw-semibold">Gambar</div>
          <div class="card-body">
            <MediaField name="image" value={service.image} invalid={!!errors.image} />
            <Field.Feedback message={errors.image} />
          </div>
        </div>

        <div class="card">
          <div class="card-header fw-semibold">Warna</div>
          <div class="card-body">
            <input
              name="color"
              type="color"
              aria-label="Warna layanan"
              class="form-control form-control-color"
              class:is-invalid={!!errors.color}
              value={service.color ?? '#0ea5e9'}
            />
            <Field.Feedback message={errors.color} />
          </div>
        </div>
      </aside>
    </div>
  {/snippet}
</Form>
