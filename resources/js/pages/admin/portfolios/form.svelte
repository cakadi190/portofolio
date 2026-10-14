<script lang="ts">
  import { Form, router } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import EditorPanel from '@/components/admin/editor-panel.svelte';
  import EditorSaveMenu from '@/components/admin/editor-save-menu.svelte';
  import EditorTitle from '@/components/admin/editor-title.svelte';
  import { Field } from '@/components/ui/field';
  import MediaField from '@/components/media/media-field.svelte';
  import MediaGalleryField from '@/components/media/media-gallery-field.svelte';
  import MultiCheck from '@/components/ui/multi-check.svelte';
  import TaxonomySelect from '@/components/ui/taxonomy-select.svelte';
  import RichTextEditor from '@/components/ui/rich-text-editor.svelte';
  import {
    destroy,
    index,
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
  let name = $state(portfolio?.name ?? '');

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
    portfolio ? 'Ubah Portofolio' : 'Tambah Portofolio Baru',
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
      <EditorTitle
        name="name"
        bind:value={name}
        placeholder="Tambahkan nama portofolio"
        ariaLabel="Nama portofolio"
        error={errors.name}
      />
    {/snippet}

    {#snippet portfolioAfter()}
      <Field.Feedback message={errors.description} />

      <div class="card mt-4">
        <div class="card-header fw-semibold">Galeri</div>
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
        label="Portofolio"
        {processing}
        exists={!!portfolio}
        draftable={false}
        mode={editor.mode}
        setMode={editor.setMode}
        danger={{
          label: 'Hapus portofolio',
          title: 'Hapus portofolio ini?',
          description:
            'Portofolio beserta galerinya dihapus permanen dan tidak dapat dikembalikan.',
          confirmLabel: 'Hapus',
          processing: deleting,
          run: deletePortfolio,
        }}
      />
    {/snippet}

    {#snippet portfolioSettings()}
      <EditorPanel title="Visibilitas">
        <input type="hidden" name="is_private" value="0" />
        <Field.Input.Check
          id="portfolio-is_private"
          name="is_private"
          value="1"
          checked={portfolio?.is_private ?? false}
        >
          Portofolio privat
        </Field.Input.Check>
      </EditorPanel>

      <EditorPanel title="Tautan">
        <div>
          <label class="gb-label" for="portfolio-slug">Permalink</label>
          <input
            id="portfolio-slug"
            name="slug"
            type="text"
            class="form-control form-control-sm"
            class:is-invalid={!!errors.slug}
            placeholder="Otomatis dari nama"
            value={portfolio?.slug ?? ''}
          />
          <Field.Feedback message={errors.slug} />
        </div>
        <div>
          <label class="gb-label" for="portfolio-demo_link">Tautan Demo</label>
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
          <label class="gb-label" for="portfolio-source_code">Kode Sumber</label
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

      <EditorPanel title="Gambar Sampul">
        <MediaField
          name="image"
          required
          value={portfolio?.image ?? null}
          invalid={!!errors.image}
        />
        <Field.Feedback message={errors.image} />
      </EditorPanel>

      <EditorPanel title="Ringkasan">
        <textarea
          name="short_desc"
          aria-label="Deskripsi singkat"
          class="form-control form-control-sm"
          class:is-invalid={!!errors.short_desc}
          rows="4"
          maxlength="255"
          placeholder="Deskripsi singkat yang tampil di daftar portofolio"
          value={portfolio?.short_desc ?? ''}></textarea>
        <Field.Feedback message={errors.short_desc} />
      </EditorPanel>

      <EditorPanel title="Layanan">
        <MultiCheck
          name="services"
          options={services.map((c) => ({ value: c.id, label: c.name }))}
          selected={portfolio?.services.map((c) => c.id) ?? []}
        />
      </EditorPanel>

      <EditorPanel title="Teknologi">
        <TaxonomySelect
          name="technologies"
          items={technologies}
          selected={portfolio?.technologies.map((t) => t.id) ?? []}
          createUrl={quickTechnology().url}
        />
      </EditorPanel>

      <EditorPanel title="Karier Terkait">
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
      name="description"
      value={portfolio?.description ?? ''}
      invalid={!!errors.description}
      backHref={index().url}
      viewHref={portfolio?.slug ? show(portfolio.slug).url : null}
      documentTitle={name}
      rootLabel="Portofolio"
      header={portfolioHeader}
      settings={portfolioSettings}
      actions={portfolioActions}
      after={portfolioAfter}
    />
  {/snippet}
</Form>
