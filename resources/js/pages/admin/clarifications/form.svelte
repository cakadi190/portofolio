<script lang="ts">
  import ArrowLeft from '@lucide/svelte/icons/arrow-left';
  import ExternalLink from '@lucide/svelte/icons/external-link';
  import Plus from '@lucide/svelte/icons/plus';
  import Trash from '@lucide/svelte/icons/trash';
  import { Form, Link } from '@inertiajs/svelte';
  import { flushSync } from 'svelte';
  import AppHead from '@/components/app-head.svelte';
  import DatePicker from '@/components/ui/date-picker.svelte';
  import { Field } from '@/components/ui/field';
  import MediaField from '@/components/media/media-field.svelte';
  import RichTextEditor from '@/components/ui/rich-text-editor.svelte';
  import Select from '@/components/ui/select.svelte';
  import { index, store, update } from '@/wayfinder/routes/admin/clarifications';
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
    apology: string | null;
    cover_image: string | null;
    sources: Source[] | null;
    updates: Correction[] | null;
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
  // svelte-ignore state_referenced_locally
  let sources = $state((clarification?.sources ?? []).map(withKey));
  // svelte-ignore state_referenced_locally
  let updates = $state((clarification?.updates ?? []).map(withKey));

  const heading = $derived(
    clarification ? 'Ubah Klarifikasi' : 'Tulis Klarifikasi Baru',
  );

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
    <div
      class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap"
    >
      <Link
        href={index().url}
        class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2"
      >
        <ArrowLeft size={14} />
        Semua Klarifikasi
      </Link>
      <h1 class="h5 mb-0">{heading}</h1>
    </div>

    <div class="row g-4">
      <div class="col-lg-9 d-flex flex-column gap-3">
        <div>
          <input
            id="clarification-title"
            name="title"
            type="text"
            class="form-control form-control-lg fw-semibold"
            class:is-invalid={!!errors.title}
            placeholder="Judul klarifikasi"
            value={clarification?.title ?? ''}
            required
          />
          <Field.Feedback message={errors.title} />
        </div>

        <div class="d-flex align-items-center gap-2 small">
          <label for="clarification-slug" class="text-muted text-nowrap"
            >Permalink:</label
          >
          <input
            id="clarification-slug"
            name="slug"
            type="text"
            class="form-control form-control-sm"
            class:is-invalid={!!errors.slug}
            placeholder="Otomatis dari judul"
            value={clarification?.slug ?? ''}
          />
          {#if clarification?.is_published}
            <a
              href={show(clarification.slug).url}
              target="_blank"
              rel="noopener"
              class="text-nowrap"
              title="Lihat klarifikasi"
            >
              <ExternalLink size={14} />
            </a>
          {/if}
        </div>
        <Field.Feedback message={errors.slug} />

        <div class="card">
          <div class="card-header fw-semibold">Klaim yang beredar</div>
          <div class="card-body d-flex flex-column gap-3">
            <div>
              <textarea
                name="claim"
                aria-label="Klaim yang beredar"
                class="form-control"
                class:is-invalid={!!errors.claim}
                rows="3"
                maxlength="1000"
                placeholder="Tulis klaim persis seperti yang beredar"
                value={clarification?.claim ?? ''}></textarea>
              <Field.Feedback message={errors.claim} />
            </div>
            <div>
              <input
                name="claim_origin"
                type="text"
                aria-label="Asal klaim"
                class="form-control"
                class:is-invalid={!!errors.claim_origin}
                maxlength="255"
                placeholder="Asal klaim, mis. platform dan tanggal (tanpa menyebut akun pribadi)"
                value={clarification?.claim_origin ?? ''}
              />
              <Field.Feedback message={errors.claim_origin} />
            </div>
          </div>
        </div>

        <div>
          <RichTextEditor
            name="content"
            value={clarification?.content ?? ''}
            invalid={!!errors.content}
          />
          <Field.Feedback message={errors.content} />
        </div>

        <div class="card">
          <div class="card-header fw-semibold">Permintaan maaf</div>
          <div class="card-body">
            <textarea
              name="apology"
              aria-label="Permintaan maaf"
              class="form-control"
              class:is-invalid={!!errors.apology}
              rows="4"
              maxlength="5000"
              placeholder="Wajib bila vonis &quot;Benar, Saya Meminta Maaf&quot;. Kosongkan bila tidak ada."
              value={clarification?.apology ?? ''}></textarea>
            <Field.Feedback message={errors.apology} />
          </div>
        </div>

        <div class="card">
          <div
            class="card-header fw-semibold d-flex justify-content-between align-items-center"
          >
            <span>Bukti dan sumber</span>
            <button
              type="button"
              class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
              onclick={() => sources.push(withKey({ title: '', url: '' }))}
            >
              <Plus size={14} /> Tambah
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
                    placeholder="Judul sumber"
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
                    aria-label={`Hapus sumber ${i + 1}`}
                    onclick={() => sources.splice(i, 1)}
                  >
                    <Trash size={14} />
                  </button>
                </div>
              </div>
            {:else}
              <p class="text-muted small mb-0">
                Belum ada sumber. Minimal satu sumber wajib saat menerbitkan.
              </p>
            {/each}
            <Field.Feedback message={errors.sources} />
          </div>
        </div>

        <div class="card">
          <div
            class="card-header fw-semibold d-flex justify-content-between align-items-center"
          >
            <span>Riwayat pembaruan</span>
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
              <Plus size={14} /> Tambah
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
                    placeholder="Apa yang diperbarui atau dikoreksi"
                    bind:value={correction.note}></textarea>
                  <Field.Feedback message={errors[`updates.${i}.note`]} />
                </div>
                <div class="col-md-1 text-end">
                  <button
                    type="button"
                    class="btn btn-outline-danger"
                    aria-label={`Hapus pembaruan ${i + 1}`}
                    onclick={() => updates.splice(i, 1)}
                  >
                    <Trash size={14} />
                  </button>
                </div>
              </div>
            {:else}
              <p class="text-muted small mb-0">
                Catat setiap koreksi atau tambahan agar perubahan tidak
                dilakukan diam-diam.
              </p>
            {/each}
          </div>
        </div>
      </div>

      <aside class="col-lg-3 d-flex flex-column gap-3">
        <div class="card">
          <div class="card-header fw-semibold">Terbitkan</div>
          <div class="card-body d-flex flex-column gap-3">
            <input
              type="hidden"
              name="is_published"
              value={isPublished ? '1' : '0'}
            />

            <div class="small">
              Status:
              <span
                class={`badge ${isPublished ? 'text-bg-success' : 'text-bg-secondary'}`}
              >
                {isPublished ? 'Terbit' : 'Draf'}
              </span>
            </div>

            <Field.Group>
              <Field.Label for="clarification-published_at"
                >Tanggal Terbit</Field.Label
              >
              <DatePicker
                id="clarification-published_at"
                name="published_at"
                value={clarification?.published_at ?? null}
                invalid={!!errors.published_at}
                withTime
              />
              <Field.Feedback message={errors.published_at} />
            </Field.Group>

            <div class="d-flex flex-column gap-2">
              <button
                type="submit"
                class="btn btn-primary"
                disabled={processing}
                onclick={() => setStatus(true)}
              >
                {clarification?.is_published ? 'Perbarui' : 'Terbitkan'}
              </button>
              <button
                type="submit"
                class="btn btn-outline-secondary"
                disabled={processing}
                onclick={() => setStatus(false)}
              >
                {clarification?.is_published
                  ? 'Kembalikan ke Draf'
                  : 'Simpan Draf'}
              </button>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header fw-semibold">Vonis</div>
          <div class="card-body">
            <Select
              id="clarification-verdict"
              name="verdict"
              items={verdicts}
              value={clarification?.verdict ?? 'unverified'}
              required
              invalid={!!errors.verdict}
            />
            <Field.Feedback message={errors.verdict} />
          </div>
        </div>

        <div class="card">
          <div class="card-header fw-semibold">Ringkasan</div>
          <div class="card-body">
            <textarea
              name="summary"
              aria-label="Ringkasan klarifikasi"
              class="form-control"
              class:is-invalid={!!errors.summary}
              rows="3"
              maxlength="255"
              placeholder="Ringkasan singkat untuk daftar dan hasil pencarian"
              value={clarification?.summary ?? ''}></textarea>
            <Field.Feedback message={errors.summary} />
          </div>
        </div>

        <div class="card">
          <div class="card-header fw-semibold">Gambar Sampul</div>
          <div class="card-body">
            <MediaField
              name="cover_image"
              value={clarification?.cover_image ?? null}
              invalid={!!errors.cover_image}
            />
            <Field.Feedback message={errors.cover_image} />
          </div>
        </div>
      </aside>
    </div>
  {/snippet}
</Form>
