<script lang="ts">
  import ChevronDown from '@lucide/svelte/icons/chevron-down';
  import BadgeCheck from '@lucide/svelte/icons/badge-check';
  import ShieldAlert from '@lucide/svelte/icons/shield-alert';
  import ShieldQuestion from '@lucide/svelte/icons/shield-question-mark';
  import type {
    CertificateInfo,
    PdfSignature,
    SignatureIntegrity,
  } from '@/lib/pdf-signatures';

  let { signatures }: { signatures: PdfSignature[] } = $props();

  const INTEGRITY: Record<
    SignatureIntegrity,
    { label: string; tone: 'ok' | 'bad' | 'unknown' }
  > = {
    valid: { label: 'Dokumen tidak berubah sejak ditandatangani', tone: 'ok' },
    modified: {
      label: 'Dokumen berubah atau tanda tangan tidak cocok',
      tone: 'bad',
    },
    unknown: { label: 'Integritas tidak dapat diperiksa', tone: 'unknown' },
  };

  const dateFormat = new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'long',
    timeStyle: 'short',
  });
  const dayFormat = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' });

  function origin(signature: PdfSignature): string {
    if (signature.komdigiRoot) {
      return 'Root CA Indonesia (Kominfo/Komdigi)';
    }

    if (signature.rootCountry === 'ID') {
      return 'Penerbit dalam negeri (Indonesia)';
    }

    return signature.rootCountry
      ? `Penerbit luar negeri (${signature.rootCountry})`
      : 'Penerbit tidak diketahui';
  }

  function validity(certificate: CertificateInfo): string {
    if (!certificate.validFrom || !certificate.validTo) {
      return '-';
    }

    return `${dayFormat.format(certificate.validFrom)} – ${dayFormat.format(certificate.validTo)}`;
  }
</script>

<div class="pdf-certs">
  <p class="pdf-certs-title">Sertifikat Elektronik</p>
  <p class="pdf-certs-note">
    {signatures.length} tanda tangan elektronik ditemukan. Informasi dibaca dari berkas
    ini dan bukan pengganti validasi resmi (pencabutan sertifikat belum diperiksa).
  </p>

  <ul class="pdf-cert-list">
    {#each signatures as signature, index (index)}
      {@const integrity = INTEGRITY[signature.integrity]}
      <li>
        <details class="pdf-cert" name="pdf-certificates" open={signatures.length === 1 || index === 0}>
          <summary class="pdf-cert-head">
            {#if integrity.tone === 'ok'}
              <BadgeCheck size={18} class="pdf-tone-ok" />
            {:else if integrity.tone === 'bad'}
              <ShieldAlert size={18} class="pdf-tone-bad" />
            {:else}
              <ShieldQuestion size={18} class="pdf-tone-unknown" />
            {/if}
            <strong>{signature.signer.commonName}</strong>
            <ChevronDown size={16} class="pdf-cert-caret" />
          </summary>

          <p class="pdf-cert-status pdf-tone-{integrity.tone}">
            {integrity.label}
          </p>

          {#if !signature.coversWholeDocument}
            <p class="pdf-cert-status pdf-tone-unknown">
              Ada tanda tangan atau perubahan setelahnya
            </p>
          {/if}

          <dl>
            <dt>Sumber</dt>
            <dd class:pdf-tone-ok={signature.komdigiRoot}>
              {origin(signature)}
            </dd>

            {#if signature.signedAt}
              <dt>Waktu tanda tangan</dt>
              <dd>{dateFormat.format(signature.signedAt)}</dd>
            {/if}

            {#if signature.reason}
              <dt>Alasan</dt>
              <dd>{signature.reason}</dd>
            {/if}

            {#if signature.location}
              <dt>Lokasi</dt>
              <dd>{signature.location}</dd>
            {/if}

            <dt>Berlaku</dt>
            <dd>{validity(signature.signer)}</dd>

            <dt>No. seri</dt>
            <dd class="pdf-cert-serial">{signature.signer.serialNumber}</dd>

            <dt>Rantai penerbit</dt>
            <dd>
              <ol class="pdf-cert-chain">
                {#each [...signature.chain].reverse() as certificate (certificate.serialNumber + certificate.subject)}
                  <li>
                    {certificate.commonName}
                    {#if certificate.organization}<span
                        >{certificate.organization}</span
                      >{/if}
                  </li>
                {/each}
              </ol>
            </dd>
          </dl>
        </details>
      </li>
    {/each}
  </ul>
</div>

<style>
  .pdf-certs {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 0;
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.8125rem;
  }

  .pdf-certs-title {
    margin: 0;
    font-weight: 600;
  }

  .pdf-certs-note {
    margin: 0;
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.75rem;
  }

  .pdf-cert-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .pdf-cert {
    padding: 0.625rem;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 0.5rem;
    background: rgba(255, 255, 255, 0.04);
  }

  .pdf-cert-head {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    overflow-wrap: anywhere;
    cursor: pointer;
    list-style: none;
  }

  .pdf-cert-head::-webkit-details-marker {
    display: none;
  }

  .pdf-cert-head strong {
    flex: 1;
    min-width: 0;
  }

  .pdf-cert-head :global(.pdf-cert-caret) {
    flex-shrink: 0;
    transition: transform 0.15s ease;
  }

  .pdf-cert[open] > .pdf-cert-head :global(.pdf-cert-caret) {
    transform: rotate(180deg);
  }

  .pdf-cert-status {
    margin: 0.25rem 0 0.5rem;
    font-size: 0.75rem;
  }

  dl {
    display: grid;
    gap: 0.125rem;
    margin: 0;
  }

  dt {
    margin-top: 0.375rem;
    color: rgba(255, 255, 255, 0.55);
    font-size: 0.6875rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.03em;
  }

  dd {
    margin: 0;
    overflow-wrap: anywhere;
  }

  .pdf-cert-serial {
    font-family: ui-monospace, monospace;
    font-size: 0.6875rem;
  }

  .pdf-cert-chain {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    margin: 0;
    padding: 0 0 0 0.75rem;
    border-left: 1px solid rgba(255, 255, 255, 0.2);
    list-style: none;
  }

  .pdf-cert-chain span {
    display: block;
    color: rgba(255, 255, 255, 0.55);
    font-size: 0.6875rem;
  }

  .pdf-certs :global(.pdf-tone-ok),
  .pdf-tone-ok {
    color: #5dd39e;
  }

  .pdf-certs :global(.pdf-tone-bad),
  .pdf-tone-bad {
    color: #ff8080;
  }

  .pdf-certs :global(.pdf-tone-unknown),
  .pdf-tone-unknown {
    color: #f5c451;
  }
</style>
