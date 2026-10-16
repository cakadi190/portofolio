<script lang="ts">
  import { t } from '@/lib/i18n';
  import ExternalLink from '@lucide/svelte/icons/external-link';
  import Plus from '@lucide/svelte/icons/plus';
  import Trash from '@lucide/svelte/icons/trash';
  import { Form, router } from '@inertiajs/svelte';
  import { flushSync } from 'svelte';
  import AppHead from '@/components/app-head.svelte';
  import EditorPanel from '@/components/admin/editor-panel.svelte';
  import EditorStatusField from '@/components/admin/editor-status-field.svelte';
  import EditorSummaryRow from '@/components/admin/editor-summary-row.svelte';
  import EditorSaveMenu from '@/components/admin/editor-save-menu.svelte';
  import EditorTitle from '@/components/admin/editor-title.svelte';
  import TranslationBar from '@/components/admin/translation-bar.svelte';
  import TranslationHiddenInputs from '@/components/admin/translation-hidden-inputs.svelte';
  import { createTranslatable, fieldName } from '@/lib/translatable.svelte';
  import DatePicker from '@/components/ui/date-picker.svelte';
  import { Field } from '@/components/ui/field';
  import MediaField from '@/components/media/media-field.svelte';
  import RichTextEditor from '@/components/ui/rich-text-editor.svelte';
  import Select from '@/components/ui/select.svelte';
  import {
    destroy,
    preview,
    store,
    update,
  } from '@/wayfinder/routes/admin/clarifications';
  import { show } from '@/wayfinder/routes/clarifications';

  type Source = { title: string; url: string };
  type Correction = { date: string; note: string };

  type Clarification = {
    id: number;
    title: string;
    slug: string;
    claim: string;
    claim_origin: string | null;
    verdict: string;
    summary: string | null;
    content: string;
    translations: Record<string, Record<string, string>>;
    apology: string | null;
    cover_image: string | null;
    sources: Source[] | null;
    updates: Correction[] | null;
    withdrawn_at: string | null;
    withdrawal_note: string | null;
    is_published: boolean;
    published_at: string | null;
  };

  let {
    clarification,
    verdicts,
  }: {
    clarification: Clarification | null;
    verdicts: { value: string; label: string }[];
  } = $props();

  let nextKey = 0;

  /** Rows get a stable key so removing one does not shift the others' input values. */
  const withKey = <T extends object>(row: T): T & { key: number } => ({
    ...row,
    key: nextKey++,
  });

  // svelte-ignore state_referenced_locally
  let isPublished = $state(clarification?.is_published ?? false);
  const FIELDS = ['title', 'claim', 'claim_origin', 'summary', 'content', 'apology', 'withdrawal_note'];
  // svelte-ignore state_referenced_locally
  const translatable = createTranslatable(clarification?.translations ?? {}, {
    title: 'text',
    claim: 'text',
    claim_origin: 'text',
    summary: 'text',
    content: 'html',
    apology: 'text',
    withdrawal_note: 'text',
  });
  const name = (field: string): string => fieldName(field, translatable.locale);
  const error = (field: string, errors: Record<string, string>): string | undefined =>
    errors[name(field)];
  const title = $derived(translatable.values.title[translatable.locale] || translatable.values.title.id);
  // svelte-ignore state_referenced_locally
  let slug = $state(clarification?.slug ?? '');
  let publishedDisplay = $state('');
  // svelte-ignore state_referenced_locally
  let isWithdrawn = $state(!!clarification?.withdrawn_at);
  // svelte-ignore state_referenced_locally
  let sources = $state((clarification?.sources ?? []).map(withKey));
  // svelte-ignore state_referenced_locally
  let updates = $state((clarification?.updates ?? []).map(withKey));

  const heading = $derived(
    clarification ? t('admin-clarifications.ubah_klarifikasi') : t('admin-clarifications.tulis_klarifikasi_baru'),
  );

  let deleting = $state(false);

  function deleteClarification(): void {
    if (!clarification) return;

    deleting = true;
    router.delete(destroy(clarification.id).url, {
      onFinish: () => {
        deleting = false;
      },
    });
  }

  function setStatus(published: boolean): void {
    isPublished = published;
    flushSync();
  }
</script>

<AppHead title={heading} />

<Form
  {...clarification ? update.form(clarification.id) : store.form()}
  novalidate
  class="post-editor"
>
  {#snippet children({ errors, processing })}
    {#snippet clarificationHeader()}
      <TranslationBar {translatable} />
      <EditorTitle
        name={name('title')}
        bind:value={translatable.values.title[translatable.locale]}
        placeholder={t('admin-clarifications.judul_klarifikasi')}
        ariaLabel={t('admin-clarifications.judul_klarifikasi')}
        required={translatable.isDefault}
        error={error('title', errors)}
      />
      <TranslationHiddenInputs {translatable} fields={FIELDS} />

      <div class="card mb-4">
        <div class="card-header fw-semibold">{t('admin-clarifications.klaim_yang_beredar')}</div>
        <div class="card-body d-flex flex-column gap-3">
          <div>
            <textarea
              name={name('claim')}
              aria-label={t('admin-clarifications.klaim_yang_beredar')}
              class="form-control"
              class:is-invalid={!!error('claim', errors)}
              rows="3"
              maxlength="1000"
              placeholder={t('admin-clarifications.tulis_klaim_persis_seperti_yang_beredar')}
              bind:value={translatable.values.claim[translatable.locale]}></textarea>
            <Field.Feedback message={error('claim', errors)} />
          </div>
          <div>
            <input
              name={name('claim_origin')}
              type="text"
              aria-label={t('admin-clarifications.asal_klaim')}
              class="form-control"
              class:is-invalid={!!error('claim_origin', errors)}
              maxlength="255"
              placeholder={t('admin-clarifications.asal_klaim_mis_platform_dan_tanggal')}
              bind:value={translatable.values.claim_origin[translatable.locale]}
            />
            <Field.Feedback message={error('claim_origin', errors)} />
          </div>
        </div>
      </div>
    {/snippet}

    {#snippet clarificationAfter()}
      <Field.Feedback message={error('content', errors)} />

      <div class="d-flex flex-column gap-4 mt-4">
        <div class="card">
          <div class="card-header fw-semibold">{t('admin-clarifications.permintaan_maaf')}</div>
          <div class="card-body">
            <textarea
              name={name('apology')}
              aria-label={t('admin-clarifications.permintaan_maaf')}
              class="form-control"
              class:is-invalid={!!error('apology', errors)}
              rows="4"
              maxlength="5000"
              placeholder="Wajib bila vonis &quot;Benar, Saya Meminta Maaf&quot;. Kosongkan bila tidak ada."
              bind:value={translatable.values.apology[translatable.locale]}></textarea>
            <Field.Feedback message={error('apology', errors)} />
          </div>
        </div>

        <div class="card">
          <div
            class="card-header fw-semibold d-flex justify-content-between align-items-center"
          >
            <span>{t('admin-clarifications.bukti_dan_sumber')}</span>
            <button
              type="button"
              class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
              onclick={() => sources.push(withKey({ title: '', url: '' }))}
            >
              <Plus size={14} /> {t('admin-clarifications.tambah')}
            </button>
          </div>
          <div class="card-body d-flex flex-column gap-3">
            {#each sources as source, i (source.key)}
              <div class="row g-2 align-items-start">
                <div class="col-md-5">
                  <input
                    name={`sources[${i}][title]`}
                    type="text"
                    aria-label={`Judul sumber ${i + 1}`}
                    class="form-control"
                    class:is-invalid={!!errors[`sources.${i}.title`]}
                    placeholder={t('admin-clarifications.judul_sumber')}
                    bind:value={source.title}
                  />
                  <Field.Feedback message={errors[`sources.${i}.title`]} />
                </div>
                <div class="col-md-6">
                  <input
                    name={`sources[${i}][url]`}
                    type="url"
                    aria-label={`Tautan sumber ${i + 1}`}
                    class="form-control"
                    class:is-invalid={!!errors[`sources.${i}.url`]}
                    placeholder="https://"
                    bind:value={source.url}
                  />
                  <Field.Feedback message={errors[`sources.${i}.url`]} />
                </div>
                <div class="col-md-1 text-end">
                  <button
                    type="button"
                    class="btn btn-outline-danger"
                    aria-label={t('admin-clarifications.hapus_sumber', { n: i + 1 })}
                    onclick={() => sources.splice(i, 1)}
                  >
                    <Trash size={14} />
                  </button>
                </div>
              </div>
            {:else}
              <p class="text-muted small mb-0">
                {t('admin-clarifications.belum_ada_sumber_minimal_satu_sumber')}
              </p>
            {/each}
            <Field.Feedback message={errors.sources} />
          </div>
        </div>

        <div class="card">
          <div
            class="card-header fw-semibold d-flex justify-content-between align-items-center"
          >
            <span>{t('admin-clarifications.riwayat_pembaruan')}</span>
            <button
              type="button"
              class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
              onclick={() =>
                updates.push(
                  withKey({
                    date: new Date().toISOString().slice(0, 10),
                    note: '',
                  }),
                )}
            >
              <Plus size={14} /> {t('admin-clarifications.tambah')}
            </button>
          </div>
          <div class="card-body d-flex flex-column gap-3">
            {#each updates as correction, i (correction.key)}
              <div class="row g-2 align-items-start">
                <div class="col-md-3">
                  <input
                    name={`updates[${i}][date]`}
                    type="date"
                    aria-label={`Tanggal pembaruan ${i + 1}`}
                    class="form-control"
                    class:is-invalid={!!errors[`updates.${i}.date`]}
                    bind:value={correction.date}
                  />
                  <Field.Feedback message={errors[`updates.${i}.date`]} />
                </div>
                <div class="col-md-8">
                  <textarea
                    name={`updates[${i}][note]`}
                    aria-label={`Catatan pembaruan ${i + 1}`}
                    class="form-control"
                    class:is-invalid={!!errors[`updates.${i}.note`]}
                    rows="2"
                    maxlength="2000"
                    placeholder={t('admin-clarifications.apa_yang_diperbarui_atau_dikoreksi')}
                    bind:value={correction.note}></textarea>
                  <Field.Feedback message={errors[`updates.${i}.note`]} />
                </div>
                <div class="col-md-1 text-end">
                  <button
                    type="button"
                    class="btn btn-outline-danger"
                    aria-label={t('admin-clarifications.hapus_pembaruan', { n: i + 1 })}
                    onclick={() => updates.splice(i, 1)}
                  >
                    <Trash size={14} />
                  </button>
                </div>
              </div>
            {:else}
              <p class="text-muted small mb-0">
                {t('admin-clarifications.catat_setiap_koreksi_atau_tambahan_agar')}
              </p>
            {/each}
          </div>
        </div>
      </div>
    {/snippet}

    {#snippet clarificationActions(editor: {
      mode: 'visual' | 'html';
      setMode: (next: 'visual' | 'html') => void;
    })}
      <EditorSaveMenu
        {processing}
        exists={!!clarification}
        {isPublished}
        mode={editor.mode}
        setMode={editor.setMode}
        onStatus={setStatus}
        danger={{
          label: t('admin-clarifications.hapus_klarifikasi'),
          title: t('admin-clarifications.hapus_klarifikasi_ini'),
          description:
            t('admin-clarifications.klarifikasi_dihapus_permanen_bila_hanya_ingin'),
          confirmLabel: t('security.hapus'),
          processing: deleting,
          run: deleteClarification,
        }}
      />
    {/snippet}

    {#snippet clarificationSettings()}
      <EditorPanel title={t('admin.ringkasan')}>
        <div class="gb-sum">
          <EditorSummaryRow
            label={t('admin.status')}
            title="Status &amp; visibilitas"
            value={isPublished ? t('admin.telah_terbit') : t('admin.draf')}
          >
            <EditorStatusField {isPublished} onStatus={setStatus} />
          </EditorSummaryRow>
          <EditorSummaryRow
            label={t('admin.terbitkan')}
            title={t('admin.tanggal_terbit')}
            width="23rem"
            value={publishedDisplay}
            placeholder={t('admin.segera')}
            invalid={!!errors.published_at}
          >
            <DatePicker
              id="clarification-published_at"
              name="published_at"
              value={clarification?.published_at ?? null}
              invalid={!!errors.published_at}
              withTime
              inline
              bind:display={publishedDisplay}
            />
            <Field.Feedback message={errors.published_at} />
          </EditorSummaryRow>
          <EditorSummaryRow
            label={t('admin.slug')}
            title={t('admin.permalink')}
            value={slug}
            placeholder={t('admin.otomatis')}
            invalid={!!errors.slug}
          >
            <input
              id="clarification-slug"
              name="slug"
              type="text"
              class="form-control form-control-sm"
              class:is-invalid={!!errors.slug}
              placeholder={t('admin.otomatis_dari_judul')}
              aria-label={t('admin.permalink')}
              bind:value={slug}
            />
            <Field.Feedback message={errors.slug} />
            {#if clarification}
              <a
                href={preview(clarification.id).url}
                target="_blank"
                rel="noopener"
                title={t('admin-clarifications.pratinjau_termasuk_draf')}
              >
                {t('admin-clarifications.pratinjau')}
              </a>
            {/if}
            {#if clarification?.is_published}
              <a
                href={show(clarification.slug).url}
                target="_blank"
                rel="noopener"
                class="d-inline-flex align-items-center gap-1"
              >
                {t('admin-clarifications.lihat_klarifikasi')} <ExternalLink size={14} />
              </a>
            {/if}
          </EditorSummaryRow>
        </div>
      </EditorPanel>

      <EditorPanel title={t('admin-clarifications.vonis')}>
        <Select
          id="clarification-verdict"
          name="verdict"
          items={verdicts}
          value={clarification?.verdict ?? 'unverified'}
          required
          invalid={!!errors.verdict}
        />
        <Field.Feedback message={errors.verdict} />
      </EditorPanel>

      <EditorPanel title={t('admin.kutipan')}>
        <textarea
          name={name('summary')}
          aria-label={t('admin-clarifications.ringkasan_klarifikasi')}
          class="form-control form-control-sm"
          class:is-invalid={!!error('summary', errors)}
          rows="4"
          maxlength="255"
          placeholder={t('admin-clarifications.ringkasan_singkat_untuk_daftar_dan_hasil')}
          bind:value={translatable.values.summary[translatable.locale]}></textarea>
        <Field.Feedback message={error('summary', errors)} />
      </EditorPanel>

      <EditorPanel title={t('admin.gambar_sampul')}>
        <MediaField
          name="cover_image"
          value={clarification?.cover_image ?? null}
          invalid={!!errors.cover_image}
        />
        <Field.Feedback message={errors.cover_image} />
      </EditorPanel>

      <EditorPanel title={t('admin-clarifications.penarikan')} open={isWithdrawn}>
        <input
          type="hidden"
          name="is_withdrawn"
          value={isWithdrawn ? '1' : '0'}
        />
        <div class="form-check">
          <input
            id="clarification-withdrawn"
            type="checkbox"
            class="form-check-input"
            bind:checked={isWithdrawn}
          />
          <label for="clarification-withdrawn" class="form-check-label"
            >{t('admin-clarifications.tarik_klarifikasi_ini')}</label
          >
        </div>
        <textarea
          name={name('withdrawal_note')}
          class="form-control form-control-sm"
          class:is-invalid={!!error('withdrawal_note', errors)}
          rows="3"
          placeholder={t('admin-clarifications.alasan_penarikan_opsional_tampil_publik')}
          bind:value={translatable.values.withdrawal_note[translatable.locale]}></textarea>
        <Field.Feedback message={error('withdrawal_note', errors)} />
      </EditorPanel>
    {/snippet}

    <RichTextEditor
      shell
      name={name('content')}
      value={translatable.values.content[translatable.locale]}
      contentKey={translatable.contentKey}
      onchange={(html) => (translatable.values.content[translatable.locale] = html)}
      languages={translatable.languages}
      language={translatable.locale}
      onlanguage={(code) => (translatable.locale = code)}
      invalid={!!error('content', errors)}
      viewHref={clarification?.is_published && clarification.slug
        ? show(clarification.slug).url
        : null}
      documentTitle={title}
      rootLabel={t('admin-clarifications.klarifikasi')}
      header={clarificationHeader}
      settings={clarificationSettings}
      actions={clarificationActions}
      after={clarificationAfter}
    />
  {/snippet}
</Form>
