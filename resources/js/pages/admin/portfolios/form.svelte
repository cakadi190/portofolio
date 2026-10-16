<script lang="ts">
  import { t } from '@/lib/i18n';
  import { Form, router } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import EditorPanel from '@/components/admin/editor-panel.svelte';
  import EditorSaveMenu from '@/components/admin/editor-save-menu.svelte';
  import EditorTitle from '@/components/admin/editor-title.svelte';
  import TranslationBar from '@/components/admin/translation-bar.svelte';
  import TranslationHiddenInputs from '@/components/admin/translation-hidden-inputs.svelte';
  import { createTranslatable, fieldName } from '@/lib/translatable.svelte';
  import { Field } from '@/components/ui/field';
  import MediaField from '@/components/media/media-field.svelte';
  import MediaGalleryField from '@/components/media/media-gallery-field.svelte';
  import MultiCheck from '@/components/ui/multi-check.svelte';
  import TaxonomySelect from '@/components/ui/taxonomy-select.svelte';
  import RichTextEditor from '@/components/ui/rich-text-editor.svelte';
  import {
    destroy,
    store,
    update,
  } from '@/wayfinder/routes/admin/portfolios';
  import { show } from '@/wayfinder/routes/portfolios';
  import { quick as quickTechnology } from '@/wayfinder/routes/admin/technologies';

  type Option = { id: number; name: string };

  type Portfolio = {
    id: number;
    name: string;
    slug: string;
    image: string;
    short_desc: string | null;
    description: string | null;
    translations: Record<string, Record<string, string>>;
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

  // svelte-ignore state_referenced_locally
  const translatable = createTranslatable(portfolio?.translations ?? {}, {
    name: 'text',
    short_desc: 'text',
    description: 'html',
  });
  const field = (key: string): string => fieldName(key, translatable.locale);
  const name = $derived(translatable.values.name[translatable.locale] || translatable.values.name.id);

  let deleting = $state(false);

  function deletePortfolio(): void {
    if (!portfolio) return;

    deleting = true;
    router.delete(destroy(portfolio.id).url, {
      onFinish: () => {
        deleting = false;
      },
    });
  }

  const heading = $derived(
    portfolio ? t('admin-portfolios.ubah_portofolio') : t('admin-portfolios.tambah_portofolio_baru'),
  );
</script>

<AppHead title={heading} />

<Form
  {...portfolio ? update.form(portfolio.id) : store.form()}
  novalidate
  class="post-editor"
>
  {#snippet children({ errors, processing })}
    {#snippet portfolioHeader()}
      <TranslationBar {translatable} />
      <EditorTitle
        name={field('name')}
        bind:value={translatable.values.name[translatable.locale]}
        placeholder={t('admin-portfolios.tambahkan_nama_portofolio')}
        ariaLabel={t('admin-portfolios.nama_portofolio')}
        required={translatable.isDefault}
        error={errors[field('name')]}
      />
      <TranslationHiddenInputs {translatable} fields={['name', 'short_desc', 'description']} />
    {/snippet}

    {#snippet portfolioAfter()}
      <Field.Feedback message={errors[field('description')]} />

      <div class="card mt-4">
        <div class="card-header fw-semibold">{t('admin.galeri')}</div>
        <div class="card-body">
          <MediaGalleryField value={portfolio?.galleries ?? []} {errors} />
        </div>
      </div>
    {/snippet}

    {#snippet portfolioActions(editor: {
      mode: 'visual' | 'html';
      setMode: (next: 'visual' | 'html') => void;
    })}
      <EditorSaveMenu
        {processing}
        exists={!!portfolio}
        draftable={false}
        mode={editor.mode}
        setMode={editor.setMode}
        danger={{
          label: t('admin-portfolios.hapus_portofolio'),
          title: t('admin-portfolios.hapus_portofolio_ini'),
          description:
            t('admin-portfolios.portofolio_beserta_galerinya_dihapus_permanen_da'),
          confirmLabel: t('security.hapus'),
          processing: deleting,
          run: deletePortfolio,
        }}
      />
    {/snippet}

    {#snippet portfolioSettings()}
      <EditorPanel title={t('admin-portfolios.visibilitas')}>
        <input type="hidden" name="is_private" value="0" />
        <Field.Input.Check
          id="portfolio-is_private"
          name="is_private"
          value="1"
          checked={portfolio?.is_private ?? false}
        >
          {t('admin-portfolios.portofolio_privat')}
        </Field.Input.Check>
      </EditorPanel>

      <EditorPanel title={t('admin-portfolios.tautan')}>
        <div>
          <label class="gb-label" for="portfolio-slug">{t('admin.permalink')}</label>
          <input
            id="portfolio-slug"
            name="slug"
            type="text"
            class="form-control form-control-sm"
            class:is-invalid={!!errors.slug}
            placeholder={t('admin-portfolios.otomatis_dari_nama')}
            value={portfolio?.slug ?? ''}
          />
          <Field.Feedback message={errors.slug} />
        </div>
        <div>
          <label class="gb-label" for="portfolio-demo_link">{t('admin-portfolios.tautan_demo')}</label>
          <Field.Input
            id="portfolio-demo_link"
            name="demo_link"
            type="url"
            placeholder="https://contoh.com"
            value={portfolio?.demo_link}
            invalid={!!errors.demo_link}
          />
          <Field.Feedback message={errors.demo_link} />
        </div>
        <div>
          <label class="gb-label" for="portfolio-source_code">{t('admin-portfolios.kode_sumber')}</label
          >
          <Field.Input
            id="portfolio-source_code"
            name="source_code"
            type="url"
            placeholder="https://contoh.com"
            value={portfolio?.source_code}
            invalid={!!errors.source_code}
          />
          <Field.Feedback message={errors.source_code} />
        </div>
      </EditorPanel>

      <EditorPanel title={t('admin.gambar_sampul')}>
        <MediaField
          name="image"
          required
          value={portfolio?.image ?? null}
          invalid={!!errors.image}
        />
        <Field.Feedback message={errors.image} />
      </EditorPanel>

      <EditorPanel title={t('admin.ringkasan')}>
        <textarea
          name={field('short_desc')}
          aria-label={t('admin-portfolios.deskripsi_singkat')}
          class="form-control form-control-sm"
          class:is-invalid={!!errors[field('short_desc')]}
          rows="4"
          maxlength="255"
          placeholder={t('admin-portfolios.deskripsi_singkat_yang_tampil_di_daftar')}
          bind:value={translatable.values.short_desc[translatable.locale]}></textarea>
        <Field.Feedback message={errors[field('short_desc')]} />
      </EditorPanel>

      <EditorPanel title={t('admin.layanan')}>
        <MultiCheck
          name="services"
          options={services.map((c) => ({ value: c.id, label: c.name }))}
          selected={portfolio?.services.map((c) => c.id) ?? []}
        />
      </EditorPanel>

      <EditorPanel title={t('admin.teknologi')}>
        <TaxonomySelect
          name="technologies"
          items={technologies}
          selected={portfolio?.technologies.map((t) => t.id) ?? []}
          createUrl={quickTechnology().url}
        />
      </EditorPanel>

      <EditorPanel title={t('admin-portfolios.karier_terkait')}>
        <MultiCheck
          name="careers"
          options={careers.map((c) => ({
            value: c.id,
            label: `${c.position} — ${c.company}`,
          }))}
          selected={portfolio?.careers.map((c) => c.id) ?? []}
        />
      </EditorPanel>
    {/snippet}

    <RichTextEditor
      shell
      name={field('description')}
      value={translatable.values.description[translatable.locale]}
      contentKey={translatable.contentKey}
      onchange={(html) => (translatable.values.description[translatable.locale] = html)}
      invalid={!!errors[field('description')]}
      viewHref={portfolio?.slug ? show(portfolio.slug).url : null}
      documentTitle={name}
      rootLabel={t('admin.portofolio')}
      header={portfolioHeader}
      settings={portfolioSettings}
      actions={portfolioActions}
      after={portfolioAfter}
    />
  {/snippet}
</Form>
