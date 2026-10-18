import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { extractPdfSignatures } from '@/lib/pdf-signatures';

function fixture(name: string): Uint8Array {
  return new Uint8Array(
    readFileSync(join(process.cwd(), 'resources/js/tests/fixtures', name)),
  );
}

describe('extractPdfSignatures', () => {
  // The default resolver asks the server (`/pdf-trust`); without a stub that is a real
  // request to happy-dom's localhost:3000, which nothing serves.
  beforeEach(() => {
    vi.stubGlobal(
      'fetch',
      vi.fn(async () => new Response(null, { status: 404 })),
    );
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('reads the signer, chain and metadata of a document signed under the Indonesian root CA', async () => {
    const [signature] = await extractPdfSignatures(
      fixture('signed-komdigi.pdf'),
    );

    expect(signature.signer.commonName).toBe('BUDI CONTOH');
    expect(
      signature.chain.map((certificate) => certificate.commonName),
    ).toEqual(['BUDI CONTOH', 'Contoh CA Class 3', 'Root CA Indonesia DS G1']);
    expect(signature.komdigiRoot).toBe(true);
    expect(signature.rootCountry).toBe('ID');
    expect(signature.reason).toBe('Saya menyetujui dokumen ini');
    expect(signature.location).toBe('Jakarta');
    expect(signature.signedAt).toBeInstanceOf(Date);
    expect(signature.integrity).toBe('valid');
    expect(signature.coversWholeDocument).toBe(true);
  });

  it('flags a foreign root CA as not Komdigi', async () => {
    const [signature] = await extractPdfSignatures(
      fixture('signed-foreign.pdf'),
    );

    expect(signature.komdigiRoot).toBe(false);
    expect(signature.rootCountry).toBe('US');
    expect(signature.chain.at(-1)?.commonName).toBe('Example Global Root');
  });

  it('reports a document changed after signing as modified', async () => {
    const bytes = fixture('signed-komdigi.pdf').slice();
    const text = new TextDecoder('latin1').decode(bytes);
    bytes[text.indexOf('MediaBox[0 0 200 200]') + 17] = '9'.charCodeAt(0);

    const [signature] = await extractPdfSignatures(bytes);

    expect(signature.integrity).toBe('modified');
  });

  it('returns nothing for a PDF without signatures', async () => {
    const bytes = new TextEncoder().encode(
      '%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF\n',
    );

    expect(await extractPdfSignatures(bytes)).toEqual([]);
  });
});

describe('trust anchoring', () => {
  it('asks the resolver about the top certificate and reports the store it names', async () => {
    const asked: number[] = [];
    const [signature] = await extractPdfSignatures(
      fixture('signed-komdigi.pdf'),
      async (top) => {
        asked.push(top.length);

        return { source: 'komdigi', anchor: 'Root CA Indonesia DS G1' };
      },
    );

    expect(asked).toEqual([signature.chain.at(-1)!.der.length]);
    expect(signature.trust).toMatchObject({
      source: 'komdigi',
      anchor: 'Root CA Indonesia DS G1',
      linksValid: true,
    });
  });

  it('is untrusted when no store vouches for the chain, even if the name says Komdigi', async () => {
    const [signature] = await extractPdfSignatures(
      fixture('signed-komdigi.pdf'),
      async () => null,
    );

    expect(signature.komdigiRoot).toBe(true);
    expect(signature.trust).toMatchObject({ source: null, linksValid: true });
  });

  it('asks the resolver for a foreign chain too, leaving the verdict to the server', async () => {
    let asked = false;
    await extractPdfSignatures(fixture('signed-foreign.pdf'), async () => {
      asked = true;

      return null;
    });

    expect(asked).toBe(true);
  });

  it('falls back to untrusted when the server cannot be reached', async () => {
    const { resolveAnchorOnServer } = await import('@/lib/pdf-signatures');
    const original = globalThis.fetch;

    globalThis.fetch = (async () => {
      throw new Error('offline');
    }) as typeof fetch;

    try {
      expect(await resolveAnchorOnServer(new Uint8Array([1, 2, 3]))).toBeNull();
    } finally {
      globalThis.fetch = original;
    }
  });
});
