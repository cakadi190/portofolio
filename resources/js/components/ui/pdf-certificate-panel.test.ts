import { render } from '@testing-library/svelte';
import { describe, expect, it } from 'vitest';
import PdfCertificatePanel from '@/components/ui/pdf-certificate-panel.svelte';
import type { CertificateInfo, PdfSignature } from '@/lib/pdf-signatures';

function certificate(commonName: string): CertificateInfo {
  return {
    subject: `CN=${commonName}`,
    issuer: 'CN=Root CA Indonesia DS G1',
    commonName,
    organization: null,
    country: 'ID',
    serialNumber: '01',
    validFrom: new Date('2026-01-01T00:00:00Z'),
    validTo: new Date('2027-01-01T00:00:00Z'),
    selfSigned: false,
  };
}

function signature(commonName: string): PdfSignature {
  const signer = certificate(commonName);

  return {
    signer,
    chain: [signer],
    signedAt: null,
    reason: null,
    location: null,
    integrity: 'valid',
    coversWholeDocument: true,
    komdigiRoot: true,
    rootCountry: 'ID',
  };
}

function openStates(container: HTMLElement): boolean[] {
  return [...container.querySelectorAll('details')].map((details) => details.open);
}

describe('PdfCertificatePanel', () => {
  it('opens a lone certificate straight away', () => {
    const { container } = render(PdfCertificatePanel, { signatures: [signature('A')] });

    expect(openStates(container)).toEqual([true]);
  });

  it('collapses every certificate but the first when there are several', () => {
    const { container } = render(PdfCertificatePanel, {
      signatures: [signature('A'), signature('B'), signature('C')],
    });

    expect(openStates(container)).toEqual([true, false, false]);
  });

  it('groups the certificates as an accordion so only one stays open', () => {
    const { container } = render(PdfCertificatePanel, { signatures: [signature('A'), signature('B')] });
    const names = [...container.querySelectorAll('details')].map((details) => details.getAttribute('name'));

    expect(new Set(names).size).toBe(1);
    expect(names[0]).toBeTruthy();
  });
});
