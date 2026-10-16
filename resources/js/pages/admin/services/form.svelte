<script lang="ts">
  import { t } from '@/lib/i18n';
  import ArrowLeft from '@lucide/svelte/icons/arrow-left';
  import ExternalLink from '@lucide/svelte/icons/external-link';
  import { Form, Link } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import { Field } from '@/components/ui/field';
  import MediaField from '@/components/media/media-field.svelte';
  import RichTextEditor from '@/components/ui/rich-text-editor.svelte';
  import TranslationBar from '@/components/admin/translation-bar.svelte';
  import TranslationHiddenInputs from '@/components/admin/translation-hidden-inputs.svelte';
  import { createTranslatable, fieldName } from '@/lib/translatable.svelte';
  import { index, update } from '@/wayfinder/routes/admin/services';
  import { show } from '@/wayfinder/routes/services';

  type Service = {
    id: number;
    name: string;
    slug: string;
    color: string | null;
    image: string | null;
    description: string | null;
    translations: Record<string, Record<string, string>>;
  };

  let { service }: { service: Service } = $props();

  // svelte-ignore state_referenced_locally
  const translatable = createTranslatable(service.translations ?? {}, {
    name: 'text',
    description: 'html',
  });
  const field = (key: string): string => fieldName(key, translatable.locale);
</script>

<AppHead title={t('admin-services.ubah_layanan')} />

<Form {...update.form(service.id)} novalidate class="post-editor">
  {#snippet children({ errors, processing })}
    <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
      <Link
        href={index().url}
        class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2"
      >
        <ArrowLeft size={14} />
        {t('admin-services.semua_layanan')}
      </Link>
      <h1 class="h5 mb-0">{t('admin-services.ubah_layanan')}</h1>
    </div>

    <div class="row g-4">
      <div class="col-lg-9 d-flex flex-column gap-3">
        <TranslationBar {translatable} picker />
        <TranslationHiddenInputs {translatable} fields={['name', 'description']} />

        <div>
          <input
            id="service-name"
            name={field('name')}
            type="text"
            class="form-control form-control-lg fw-semibold"
            class:is-invalid={!!errors[field('name')]}
            placeholder={t('admin-services.nama_layanan')}
            bind:value={translatable.values.name[translatable.locale]}
            required={translatable.isDefault}
          />
          <Field.Feedback message={errors[field('name')]} />
        </div>

        <div class="d-flex align-items-center gap-2 small">
          <label for="service-slug" class="text-muted text-nowrap">{t('admin-services.permalink')}</label>
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
            title={t('admin-services.lihat_layanan')}
          >
            <ExternalLink size={14} />
          </a>
        </div>
        <Field.Feedback message={errors.slug} />

        <div>
          <RichTextEditor
            name={field('description')}
            value={translatable.values.description[translatable.locale]}
            contentKey={translatable.contentKey}
            onchange={(html) => (translatable.values.description[translatable.locale] = html)}
            invalid={!!errors[field('description')]}
          />
          <Field.Feedback message={errors[field('description')]} />
        </div>
      </div>

      <aside class="col-lg-3 d-flex flex-column gap-3">
        <div class="card">
          <div class="card-header fw-semibold">{t('admin.simpan')}</div>
          <div class="card-body">
            <button type="submit" class="btn btn-primary w-100" disabled={processing}>
              {t('admin-services.perbarui')}
            </button>
          </div>
        </div>

        <div class="card">
          <div class="card-header fw-semibold">{t('admin.gambar')}</div>
          <div class="card-body">
            <MediaField name="image" value={service.image} invalid={!!errors.image} />
            <Field.Feedback message={errors.image} />
          </div>
        </div>

        <div class="card">
          <div class="card-header fw-semibold">{t('admin-services.warna')}</div>
          <div class="card-body">
            <input
              name="color"
              type="color"
              aria-label={t('admin-services.warna_layanan')}
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
