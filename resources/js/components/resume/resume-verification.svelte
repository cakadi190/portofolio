<script lang="ts">
  import { intlLocale } from '@/lib/i18n';
  import BadgeCheck from '@lucide/svelte/icons/badge-check';
  import CircleCheck from '@lucide/svelte/icons/circle-check';
  import CircleMinus from '@lucide/svelte/icons/circle-minus';
  import ShieldAlert from '@lucide/svelte/icons/shield-alert';
  import ShieldQuestion from '@lucide/svelte/icons/shield-question-mark';
  import type { PdfSignature, TrustSource } from '@/lib/pdf-signatures';

  let {
    signatures,
    checking = false,
  }: { signatures: PdfSignature[] | null; checking?: boolean } = $props();

  const SOURCES: { key: TrustSource; label: string; hint: string }[] = [
    {
      key: 'komdigi',
      label: 'Root CA Komdigi',
      hint: 'Penyelenggara sertifikasi elektronik Indonesia',
    },
    {
      key: 'international',
      label: 'Root CA internasional',
      hint: 'DigiCert, Sectigo, USERTrust, Comodo',
    },
    {
      key: 'aatl',
      label: 'Adobe AATL',
      hint: 'Adobe Approved Trust List untuk tanda tangan dokumen',
    },
  ];

  const dateFormat = new Intl.DateTimeFormat(intlLocale(), {
    dateStyle: 'long',
    timeStyle: 'short',
  });
  const preciseFormat = new Intl.DateTimeFormat(intlLocale(), {
    dateStyle: 'medium',
    timeStyle: 'medium',
  });

  const range = (certificate: { validFrom: Date | null; validTo: Date | null }): string =>
    certificate.validFrom && certificate.validTo
      ? `${preciseFormat.format(certificate.validFrom)} – ${preciseFormat.format(certificate.validTo)}`
      : '-';

  type Verdict = { tone: 'ok' | 'bad' | 'warn'; label: string };

  function verdict(signature: PdfSignature): Verdict {
    if (signature.integrity === 'modified') {
      return { tone: 'bad', label: 'Dokumen berubah setelah ditandatangani' };
    }

    if (!signature.trust.linksValid) {
      return { tone: 'bad', label: 'Rantai sertifikat tidak sah' };
    }

    if (!signature.trust.source) {
      return { tone: 'warn', label: 'Penerbit tidak ada di daftar akar tepercaya' };
    }

    if (signature.trust.expired) {
      return { tone: 'warn', label: 'Sertifikat di luar masa berlaku saat ditandatangani' };
    }

    return signature.integrity === 'valid'
      ? { tone: 'ok', label: 'Tanda tangan terverifikasi' }
      : { tone: 'warn', label: 'Integritas dokumen tidak dapat diperiksa' };
  }
</script>

<div class="rvv">
  {#if checking || signatures === null}
    <p class="rvv-muted">Memeriksa tanda tangan elektronik…</p>
  {:else if signatures.length === 0}
    <p class="rvv-muted">Dokumen ini belum memuat tanda tangan elektronik (TTE), jadi tidak ada yang diverifikasi.</p>
  {:else}
    {#each signatures as signature, index (index)}
      {@const result = verdict(signature)}
      <section class="rvv-card">
        <header class="rvv-{result.tone}">
          {#if result.tone === 'ok'}<BadgeCheck size={20} />{:else if result.tone === 'bad'}<ShieldAlert size={20} />{:else}<ShieldQuestion size={20} />{/if}
          <div>
            <strong>{result.label}</strong>
            <small>{signature.signer.commonName}</small>
          </div>
        </header>

        <ul class="rvv-sources" aria-label="Sumber akar tepercaya">
          {#each SOURCES as source (source.key)}
            {@const matched = signature.trust.source === source.key}
            <li class:matched>
              {#if matched}<CircleCheck size={16} />{:else}<CircleMinus size={16} />{/if}
              <span>
                <b>{source.label}</b>
                <small>{matched ? `Rantai berujung di ${signature.trust.anchor}` : source.hint}</small>
              </span>
            </li>
          {/each}
        </ul>

        {#if signature.komdigiRoot && signature.trust.source !== 'komdigi'}
          <p class="rvv-note">Nama penerbit menyebut Root CA Indonesia, tetapi sertifikat akarnya tidak cocok dengan yang terpasang di sini, jadi belum dianggap tepercaya.</p>
        {/if}

        {#if signature.trust.outOfRange}
          <p class="rvv-note">
            {signature.trust.outOfRange.commonName} berlaku {range(signature.trust.outOfRange)},
            sedangkan waktu tanda tangan yang tercatat di dokumen {signature.signedAt ? preciseFormat.format(signature.signedAt) : 'tidak diketahui'}.
          </p>
        {/if}

        <dl>
          <dt>Berlaku (penandatangan)</dt>
          <dd>{range(signature.signer)}</dd>
          <dt>Integritas</dt>
          <dd>{signature.integrity === 'valid' ? 'Isi dokumen tidak berubah' : signature.integrity === 'modified' ? 'Isi dokumen berubah' : 'Tidak dapat diperiksa'}</dd>
          {#if !signature.coversWholeDocument}
            <dt>Catatan</dt>
            <dd>Ada perubahan atau tanda tangan setelah yang ini</dd>
          {/if}
          {#if signature.signedAt}
            <dt>Ditandatangani</dt>
            <dd>{dateFormat.format(signature.signedAt)}</dd>
          {/if}
          {#if signature.reason}
            <dt>Alasan</dt>
            <dd>{signature.reason}</dd>
          {/if}
          <dt>Rantai penerbit</dt>
          <dd>
            <ol>
              {#each [...signature.chain].reverse() as certificate (certificate.serialNumber + certificate.subject)}
                <li>{certificate.commonName}</li>
              {/each}
            </ol>
          </dd>
        </dl>
      </section>
    {/each}
    <p class="rvv-muted">Pemeriksaan berjalan di browser Anda. Status pencabutan sertifikat (CRL/OCSP) belum diperiksa, jadi ini bukan pengganti validator resmi.</p>
  {/if}
</div>

<style>
  .rvv { display: flex; flex-direction: column; gap: 0.75rem; padding: 0.75rem; font-size: 0.8125rem; }
  .rvv-muted { margin: 0; font-size: 0.75rem; color: var(--neo-secondary-color); }
  .rvv-card { padding: 0.75rem; background: var(--neo-tertiary-bg); border: 1px solid var(--neo-border-color); border-radius: 0.5rem; }
  header { display: flex; gap: 0.5rem; align-items: flex-start; }
  header div { display: flex; flex-direction: column; min-width: 0; overflow-wrap: anywhere; }
  header small, .rvv-sources small { font-size: 0.7rem; color: var(--neo-secondary-color); }
  .rvv-ok { color: #198754; }
  .rvv-warn { color: #b7791f; }
  .rvv-bad { color: #dc3545; }
  .rvv-sources { display: flex; flex-direction: column; gap: 0.5rem; margin: 0.75rem 0 0; padding: 0; list-style: none; }
  .rvv-sources li { display: flex; gap: 0.5rem; align-items: flex-start; color: var(--neo-secondary-color); }
  .rvv-sources li.matched { color: #198754; }
  .rvv-sources span { display: flex; flex-direction: column; overflow-wrap: anywhere; }
  .rvv-sources b { font-weight: 600; color: var(--neo-body-color); }
  .rvv-note { margin: 0.5rem 0 0; font-size: 0.75rem; color: #b7791f; }
  dl { display: grid; gap: 0.125rem; margin: 0.75rem 0 0; }
  dt { margin-top: 0.375rem; font-size: 0.6875rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.03em; color: var(--neo-secondary-color); }
  dd { margin: 0; overflow-wrap: anywhere; }
  ol { display: flex; flex-direction: column; gap: 0.25rem; margin: 0; padding: 0 0 0 0.75rem; list-style: none; border-left: 1px solid var(--neo-border-color); }
</style>
