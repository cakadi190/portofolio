<script lang="ts">
  import { intlLocale, t } from '@/lib/i18n';
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
      label: t('ui.root_ca_komdigi'),
      hint: t('ui.penyelenggara_sertifikasi_elektronik_indonesia'),
    },
    {
      key: 'international',
      label: t('ui.root_ca_internasional'),
      hint: 'DigiCert, Sectigo, USERTrust, Comodo',
    },
    {
      key: 'aatl',
      label: t('ui.adobe_aatl'),
      hint: t('ui.adobe_approved_trust_list_untuk_tanda'),
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
      return { tone: 'bad', label: t('ui.dokumen_berubah_setelah_ditandatangani') };
    }

    if (!signature.trust.linksValid) {
      return { tone: 'bad', label: t('ui.rantai_sertifikat_tidak_sah') };
    }

    if (!signature.trust.source) {
      return { tone: 'warn', label: t('ui.penerbit_tidak_ada_di_daftar_akar') };
    }

    if (signature.trust.expired) {
      return { tone: 'warn', label: t('ui.sertifikat_di_luar_masa_berlaku_saat') };
    }

    return signature.integrity === 'valid'
      ? { tone: 'ok', label: t('ui.tanda_tangan_terverifikasi') }
      : { tone: 'warn', label: t('ui.integritas_dokumen_tidak_dapat_diperiksa') };
  }
</script>

<div class="rvv">
  {#if checking || signatures === null}
    <p class="rvv-muted">{t('ui.memeriksa_tanda_tangan_elektronik')}</p>
  {:else if signatures.length === 0}
    <p class="rvv-muted">{t('ui.dokumen_ini_belum_memuat_tanda_tangan')}</p>
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

        <ul class="rvv-sources" aria-label={t('ui.sumber_akar_tepercaya')}>
          {#each SOURCES as source (source.key)}
            {@const matched = signature.trust.source === source.key}
            <li class:matched>
              {#if matched}<CircleCheck size={16} />{:else}<CircleMinus size={16} />{/if}
              <span>
                <b>{source.label}</b>
                <small>{matched ? t('ui.rantai_berujung', { anchor: signature.trust.anchor }) : source.hint}</small>
              </span>
            </li>
          {/each}
        </ul>

        {#if signature.komdigiRoot && signature.trust.source !== 'komdigi'}
          <p class="rvv-note">{t('ui.nama_penerbit_menyebut_root_ca_indonesia')}</p>
        {/if}

        {#if signature.trust.outOfRange}
          <p class="rvv-note">
            {signature.trust.outOfRange.commonName} berlaku {range(signature.trust.outOfRange)},
            sedangkan waktu tanda tangan yang tercatat di dokumen {signature.signedAt ? preciseFormat.format(signature.signedAt) : 'tidak diketahui'}.
          </p>
        {/if}

        <dl>
          <dt>{t('ui.berlaku_penandatangan')}</dt>
          <dd>{range(signature.signer)}</dd>
          <dt>{t('ui.integritas')}</dt>
          <dd>{signature.integrity === 'valid' ? t('ui.isi_dokumen_tidak_berubah') : signature.integrity === 'modified' ? t('ui.isi_dokumen_berubah') : t('ui.tidak_dapat_diperiksa')}</dd>
          {#if !signature.coversWholeDocument}
            <dt>{t('ui.catatan')}</dt>
            <dd>{t('ui.ada_perubahan_atau_tanda_tangan_setelah')}</dd>
          {/if}
          {#if signature.signedAt}
            <dt>{t('ui.ditandatangani')}</dt>
            <dd>{dateFormat.format(signature.signedAt)}</dd>
          {/if}
          {#if signature.reason}
            <dt>{t('ui.alasan')}</dt>
            <dd>{signature.reason}</dd>
          {/if}
          <dt>{t('ui.rantai_penerbit')}</dt>
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
    <p class="rvv-muted">{t('ui.pemeriksaan_berjalan_di_browser_anda_status')}</p>
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
