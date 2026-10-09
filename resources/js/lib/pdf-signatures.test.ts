import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { describe, expect, it } from 'vitest';
import { extractPdfSignatures } from '@/lib/pdf-signatures';

function fixture(name: string): Uint8Array {
  return new Uint8Array(readFileSync(join(process.cwd(), 'resources/js/tests/fixtures', name)));
}

describe('extractPdfSignatures', () => {
  it('reads the signer, chain and metadata of a document signed under the Indonesian root CA', async () => {
    const [signature] = await extractPdfSignatures(fixture('signed-komdigi.pdf'));

    expect(signature.signer.commonName).toBe('BUDI CONTOH');
    expect(signature.chain.map((certificate) => certificate.commonName)).toEqual([
      'BUDI CONTOH',
      'Contoh CA Class 3',
      'Root CA Indonesia DS G1',
    ]);
    expect(signature.komdigiRoot).toBe(true);
    expect(signature.rootCountry).toBe('ID');
    expect(signature.reason).toBe('Saya menyetujui dokumen ini');
    expect(signature.location).toBe('Jakarta');
    expect(signature.signedAt).toBeInstanceOf(Date);
    expect(signature.integrity).toBe('valid');
    expect(signature.coversWholeDocument).toBe(true);
  });

  it('flags a foreign root CA as not Komdigi', async () => {
    const [signature] = await extractPdfSignatures(fixture('signed-foreign.pdf'));

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
    const bytes = new TextEncoder().encode('%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF\n');

    expect(await extractPdfSignatures(bytes)).toEqual([]);
  });
});
