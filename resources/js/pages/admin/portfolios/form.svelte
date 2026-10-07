<script lang="ts">
  import ArrowLeft from '@lucide/svelte/icons/arrow-left';
  import { Form, Link } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import { Field } from '@/components/ui/field';
  import MediaField from '@/components/media/media-field.svelte';
  import MediaGalleryField from '@/components/media/media-gallery-field.svelte';
  import MultiCheck from '@/components/ui/multi-check.svelte';
  import RichTextEditor from '@/components/ui/rich-text-editor.svelte';
  import { index, store, update } from '@/wayfinder/routes/admin/portfolios';

  type Option = { id: number; name: string };

  type Portfolio = {
    id: number;
    name: string;
    slug: string;
    image: string;
    short_desc: string | null;
    description: string | null;
    demo_link: string | null;
    source_code: string | null;
    is_private: boolean;
    technologies: { id: number }[];
    services: { id: number }[];
    careers: { id: number }[];
    galleries: { image_url: string; description: string | null }[];
  };

  let {
    portfolio,
    technologies,
    services,
    careers,
  }: {
    portfolio: Portfolio | null;
    technologies: Option[];
    services: Option[];
    careers: { id: number; position: string; company: string }[];
  } = $props();

  const heading = $derived(portfolio ? 'Ubah Portofolio' : 'Tambah Portofolio Baru');
</script>

<AppHead title={heading} />

<Form
  {...portfolio ? update.form(portfolio.id) : store.form()}
  novalidate
  class="post-editor"
>
  {#snippet children({ errors, processing })}
    <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
      <Link
        href={index().url}
        class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2"
      >
        <ArrowLeft size={14} />
        Semua Portofolio
      </Link>
      <h1 class="h5 mb-0">{heading}</h1>
    </div>

    <div class="row g-4">
      <div class="col-lg-9 d-flex flex-column gap-3">
        <div>
          <input
            id="portfolio-name"
            name="name"
            type="text"
            class="form-control form-control-lg fw-semibold"
            class:is-invalid={!!errors.name}
            placeholder="Tambahkan nama portofolio"
            value={portfolio?.name ?? ''}
            required
          />
          <Field.Feedback message={errors.name} />
        </div>

        <div class="d-flex align-items-center gap-2 small">
          <label for="portfolio-slug" class="text-muted text-nowrap">Permalink:</label>
          <input
            id="portfolio-slug"
            name="slug"
            type="text"
            class="form-control form-control-sm"
            class:is-invalid={!!errors.slug}
            placeholder="Otomatis dari nama"
            value={portfolio?.slug ?? ''}
          />
        </div>
        <Field.Feedback message={errors.slug} />

        <div>
          <RichTextEditor
            name="description"
            value={portfolio?.description ?? ''}
            invalid={!!errors.description}
          />
          <Field.Feedback message={errors.description} />
        </div>

        <div class="card">
          <div class="card-header fw-semibold">Ringkasan</div>
          <div class="card-body">
            <textarea
              name="short_desc"
              aria-label="Deskripsi singkat"
              class="form-control"
              class:is-invalid={!!errors.short_desc}
              rows="3"
              maxlength="255"
              placeholder="Deskripsi singkat yang tampil di daftar portofolio"
              value={portfolio?.short_desc ?? ''}></textarea>
            <Field.Feedback message={errors.short_desc} />
          </div>
        </div>

        <div class="card">
          <div class="card-header fw-semibold">Galeri</div>
          <div class="card-body">
            <MediaGalleryField value={portfolio?.galleries ?? []} {errors} />
          </div>
        </div>

        <div class="card">
          <div class="card-header fw-semibold">Tautan</div>
          <div class="card-body row g-3">
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="portfolio-demo_link">Tautan Demo</Field.Label>
                <Field.Input
                  id="portfolio-demo_link"
                  name="demo_link"
                  type="url"
                  placeholder="https://contoh.com"
                  value={portfolio?.demo_link}
                  invalid={!!errors.demo_link}
                />
                <Field.Feedback message={errors.demo_link} />
              </Field.Group>
            </div>
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="portfolio-source_code">Kode Sumber</Field.Label>
                <Field.Input
                  id="portfolio-source_code"
                  name="source_code"
                  type="url"
                  placeholder="https://contoh.com"
                  value={portfolio?.source_code}
                  invalid={!!errors.source_code}
                />
                <Field.Feedback message={errors.source_code} />
              </Field.Group>
            </div>
          </div>
        </div>
      </div>

      <aside class="col-lg-3 d-flex flex-column gap-3">
        <div class="card">
          <div class="card-header fw-semibold">Simpan</div>
          <div class="card-body d-flex flex-column gap-3">
            <input type="hidden" name="is_private" value="0" />
            <Field.Input.Check
              id="portfolio-is_private"
              name="is_private"
              value="1"
              checked={portfolio?.is_private ?? false}
            >
              Portofolio privat
            </Field.Input.Check>

            <button type="submit" class="btn btn-primary" disabled={processing}>
              {portfolio ? 'Perbarui' : 'Simpan'}
            </button>
          </div>
        </div>

        <div class="card">
          <div class="card-header fw-semibold">Gambar Sampul</div>
          <div class="card-body">
            <MediaField
              name="image"
              required
              value={portfolio?.image ?? null}
              invalid={!!errors.image}
            />
            <Field.Feedback message={errors.image} />
          </div>
        </div>

        <div class="card">
          <div class="card-header fw-semibold">Layanan</div>
          <div class="card-body">
            <MultiCheck
              name="services"
              options={services.map((c) => ({ value: c.id, label: c.name }))}
              selected={portfolio?.services.map((c) => c.id) ?? []}
            />
          </div>
        </div>

        <div class="card">
          <div class="card-header fw-semibold">Teknologi</div>
          <div class="card-body">
            <MultiCheck
              name="technologies"
              options={technologies.map((t) => ({ value: t.id, label: t.name }))}
              selected={portfolio?.technologies.map((t) => t.id) ?? []}
            />
          </div>
        </div>

        <div class="card">
          <div class="card-header fw-semibold">Karier Terkait</div>
          <div class="card-body">
            <MultiCheck
              name="careers"
              options={careers.map((c) => ({
                value: c.id,
                label: `${c.position} — ${c.company}`,
              }))}
              selected={portfolio?.careers.map((c) => c.id) ?? []}
            />
          </div>
        </div>
      </aside>
    </div>
  {/snippet}
</Form>
