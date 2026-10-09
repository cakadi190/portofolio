/**
 * Reads the electronic signatures embedded in a PDF (PAdES / adbe.pkcs7.detached) entirely in the browser:
 * who signed, the issuer chain, when, and whether the signed bytes still match. It does not check revocation
 * (CRL/OCSP) or anchor the chain in a trust store, so it informs rather than replaces an official validator.
 */

export type CertificateInfo = {
  subject: string;
  issuer: string;
  commonName: string;
  organization: string | null;
  country: string | null;
  serialNumber: string;
  validFrom: Date | null;
  validTo: Date | null;
  selfSigned: boolean;
};

export type SignatureIntegrity = 'valid' | 'modified' | 'unknown';

export type PdfSignature = {
  signer: CertificateInfo;
  /** Signer certificate first, then each issuer up to the root that could be resolved. */
  chain: CertificateInfo[];
  signedAt: Date | null;
  reason: string | null;
  location: string | null;
  integrity: SignatureIntegrity;
  /** False when bytes were appended after this signature was applied. */
  coversWholeDocument: boolean;
  /** Chain ends at (or passes through) the Indonesian Root CA operated by Kominfo / Komdigi. */
  komdigiRoot: boolean;
  /** Country code of the topmost certificate in the chain, e.g. "ID". */
  rootCountry: string | null;
};

type Node = { tag: number; start: number; body: number; end: number };

const OID_COMMON_NAME = '2.5.4.3';
const OID_COUNTRY = '2.5.4.6';
const OID_ORGANIZATION = '2.5.4.10';
const OID_MESSAGE_DIGEST = '1.2.840.113549.1.9.4';
const OID_SIGNING_TIME = '1.2.840.113549.1.9.5';
const OID_RSA_ENCRYPTION = '1.2.840.113549.1.1.1';
const OID_EC_PUBLIC_KEY = '1.2.840.10045.2.1';
const EC_CURVES: Record<string, { name: string; size: number }> = {
  '1.2.840.10045.3.1.7': { name: 'P-256', size: 32 },
  '1.3.132.0.34': { name: 'P-384', size: 48 },
  '1.3.132.0.35': { name: 'P-521', size: 66 },
};

const DIGEST_ALGORITHMS: Record<string, string> = {
  '1.3.14.3.2.26': 'SHA-1',
  '2.16.840.1.101.3.4.2.1': 'SHA-256',
  '2.16.840.1.101.3.4.2.2': 'SHA-384',
  '2.16.840.1.101.3.4.2.3': 'SHA-512',
};

const KOMDIGI_ROOT_PATTERN = /root ca indonesia|kementerian komunikasi (dan|&) (digital|informatika)|komdigi|kominfo/i;

function readNode(bytes: Uint8Array, offset: number): Node {
  if (offset + 2 > bytes.length) {
    throw new Error('DER truncated');
  }

  const tag = bytes[offset];
  let length = bytes[offset + 1];
  let body = offset + 2;

  if (length & 0x80) {
    const count = length & 0x7f;

    if (count === 0 || count > 4 || body + count > bytes.length) {
      throw new Error('DER length unsupported');
    }

    length = 0;

    for (let index = 0; index < count; index++) {
      length = length * 256 + bytes[body + index];
    }

    body += count;
  }

  if (body + length > bytes.length) {
    throw new Error('DER truncated');
  }

  return { tag, start: offset, body, end: body + length };
}

function children(bytes: Uint8Array, node: Node): Node[] {
  const result: Node[] = [];

  for (let offset = node.body; offset < node.end; ) {
    const child = readNode(bytes, offset);
    result.push(child);
    offset = child.end;
  }

  return result;
}

function oidOf(bytes: Uint8Array, node: Node): string {
  const parts: number[] = [];
  let value = 0;

  for (let index = node.body; index < node.end; index++) {
    value = value * 128 + (bytes[index] & 0x7f);

    if (!(bytes[index] & 0x80)) {
      parts.push(value);
      value = 0;
    }
  }

  const first = parts.shift() ?? 0;

  return [Math.min(Math.floor(first / 40), 2), first >= 80 ? first - 80 : first % 40, ...parts].join('.');
}

function textOf(bytes: Uint8Array, node: Node): string {
  const body = bytes.subarray(node.body, node.end);

  if (node.tag === 0x1e) {
    return new TextDecoder('utf-16be').decode(body);
  }

  return new TextDecoder(node.tag === 0x0c ? 'utf-8' : 'latin1').decode(body);
}

function timeOf(bytes: Uint8Array, node: Node): Date | null {
  const raw = new TextDecoder().decode(bytes.subarray(node.body, node.end));
  const match =
    node.tag === 0x17
      ? /^(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})?Z$/.exec(raw)
      : /^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})?Z$/.exec(raw);

  if (!match) {
    return null;
  }

  const [, year, month, day, hour, minute, second = '0'] = match;
  const fullYear = node.tag === 0x17 ? (Number(year) >= 50 ? 1900 : 2000) + Number(year) : Number(year);

  return new Date(Date.UTC(fullYear, Number(month) - 1, Number(day), Number(hour), Number(minute), Number(second)));
}

function hex(bytes: Uint8Array): string {
  return Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('');
}

function raw(bytes: Uint8Array, node: Node): Uint8Array {
  return bytes.subarray(node.start, node.end);
}

function parseName(bytes: Uint8Array, node: Node): { text: string; values: Record<string, string> } {
  const values: Record<string, string> = {};
  const labels: Record<string, string> = { [OID_COMMON_NAME]: 'CN', [OID_COUNTRY]: 'C', [OID_ORGANIZATION]: 'O' };
  const parts: string[] = [];

  for (const set of children(bytes, node)) {
    for (const pair of children(bytes, set)) {
      const [oid, value] = children(bytes, pair);
      const id = oidOf(bytes, oid);
      const text = textOf(bytes, value);

      values[id] ??= text;
      parts.push(`${labels[id] ?? id}=${text}`);
    }
  }

  return { text: parts.join(', '), values };
}

type ParsedCertificate = {
  info: CertificateInfo;
  subjectKey: string;
  issuerKey: string;
  publicKey: Uint8Array;
  publicKeyOid: string;
  curveOid: string | null;
};

function parseCertificate(bytes: Uint8Array, node: Node): ParsedCertificate {
  const [tbs] = children(bytes, node);
  const fields = children(bytes, tbs);
  const offset = fields[0].tag === 0xa0 ? 1 : 0;
  const serial = fields[offset];
  const issuer = fields[offset + 2];
  const validity = children(bytes, fields[offset + 3]);
  const subject = fields[offset + 4];
  const publicKeyInfo = fields[offset + 5];
  const issuerName = parseName(bytes, issuer);
  const subjectName = parseName(bytes, subject);
  const [algorithm] = children(bytes, publicKeyInfo);

  return {
    subjectKey: hex(raw(bytes, subject)),
    issuerKey: hex(raw(bytes, issuer)),
    publicKey: raw(bytes, publicKeyInfo),
    publicKeyOid: oidOf(bytes, children(bytes, algorithm)[0]),
    curveOid: children(bytes, algorithm)[1]?.tag === 0x06 ? oidOf(bytes, children(bytes, algorithm)[1]) : null,
    info: {
      subject: subjectName.text,
      issuer: issuerName.text,
      commonName: subjectName.values[OID_COMMON_NAME] ?? subjectName.text,
      organization: subjectName.values[OID_ORGANIZATION] ?? null,
      country: subjectName.values[OID_COUNTRY] ?? null,
      serialNumber: hex(bytes.subarray(serial.body, serial.end)).replace(/^(00)+(?=.)/, ''),
      validFrom: timeOf(bytes, validity[0]),
      validTo: timeOf(bytes, validity[1]),
      selfSigned: hex(raw(bytes, subject)) === hex(raw(bytes, issuer)),
    },
  };
}

/** Decodes a PDF string object (literal or hex) to text, honouring UTF-16BE byte-order-marked strings. */
function decodePdfString(value: string, isHex: boolean): string {
  let codes: number[];

  if (isHex) {
    const digits = value.replace(/\s+/g, '');
    codes = (digits.match(/../g) ?? []).map((pair) => parseInt(pair, 16));
  } else {
    codes = [];

    for (let index = 0; index < value.length; index++) {
      const char = value[index];

      if (char !== '\\') {
        codes.push(value.charCodeAt(index) & 0xff);
        continue;
      }

      const next = value[++index];
      const escapes: Record<string, number> = { n: 10, r: 13, t: 9, b: 8, f: 12 };

      if (next !== undefined && /[0-7]/.test(next)) {
        const octal = /^[0-7]{1,3}/.exec(value.slice(index))![0];
        codes.push(parseInt(octal, 8) & 0xff);
        index += octal.length - 1;
      } else if (next !== undefined && next in escapes) {
        codes.push(escapes[next]);
      } else if (next !== undefined && next !== '\n' && next !== '\r') {
        codes.push(next.charCodeAt(0) & 0xff);
      }
    }
  }

  const buffer = Uint8Array.from(codes);

  if (buffer[0] === 0xfe && buffer[1] === 0xff) {
    return new TextDecoder('utf-16be').decode(buffer.subarray(2));
  }

  return new TextDecoder('latin1').decode(buffer);
}

function dictionaryString(dictionary: string, key: string): string | null {
  const match = new RegExp(`/${key}\\s*(?:\\(((?:\\\\.|[^\\\\)])*)\\)|<([0-9A-Fa-f\\s]*)>)`).exec(dictionary);

  if (!match) {
    return null;
  }

  return decodePdfString(match[1] ?? match[2], match[1] === undefined) || null;
}

/** Parses a PDF date string such as D:20260310063302+07'00' (the signing time recorded in the signature dictionary). */
function pdfDate(value: string | null): Date | null {
  const match = value ? /^D:(\d{4})(\d{2})?(\d{2})?(\d{2})?(\d{2})?(\d{2})?(?:([Zz+-])(\d{2})?'?(\d{2})?'?)?/.exec(value) : null;

  if (!match) {
    return null;
  }

  const [, year, month = '01', day = '01', hour = '00', minute = '00', second = '00', sign, offsetHours = '00', offsetMinutes = '00'] = match;
  const offset = sign === '+' || sign === '-' ? (sign === '+' ? 1 : -1) * (Number(offsetHours) * 60 + Number(offsetMinutes)) : 0;

  return new Date(Date.UTC(Number(year), Number(month) - 1, Number(day), Number(hour), Number(minute), Number(second)) - offset * 60000);
}

async function checkIntegrity(
  bytes: Uint8Array,
  container: Uint8Array,
  ranges: number[],
  signedAttributes: Node | undefined,
  digestOid: string,
  signer: ParsedCertificate,
  signature: Uint8Array,
): Promise<SignatureIntegrity> {
  const algorithm = DIGEST_ALGORITHMS[digestOid];

  if (!algorithm || !signedAttributes || !globalThis.crypto?.subtle) {
    return 'unknown';
  }

  try {
    const [offsetA, lengthA, offsetB, lengthB] = ranges;
    const signed = new Uint8Array(lengthA + lengthB);

    signed.set(bytes.subarray(offsetA, offsetA + lengthA), 0);
    signed.set(bytes.subarray(offsetB, offsetB + lengthB), lengthA);

    const digest = new Uint8Array(await crypto.subtle.digest(algorithm, signed));
    const attribute = children(container, signedAttributes)
      .map((node) => children(container, node))
      .find(([oid]) => oidOf(container, oid) === OID_MESSAGE_DIGEST);

    if (!attribute) {
      return 'unknown';
    }

    const expected = container.subarray(...digestBounds(container, attribute[1]));

    if (hex(digest) !== hex(expected)) {
      return 'modified';
    }

    const attributes = raw(container, signedAttributes).slice();
    attributes[0] = 0x31;

    if (signer.publicKeyOid === OID_RSA_ENCRYPTION) {
      const key = await crypto.subtle.importKey(
        'spki',
        signer.publicKey as BufferSource,
        { name: 'RSASSA-PKCS1-v1_5', hash: algorithm },
        false,
        ['verify'],
      );

      return (await crypto.subtle.verify('RSASSA-PKCS1-v1_5', key, signature as BufferSource, attributes as BufferSource))
        ? 'valid'
        : 'modified';
    }

    const curve = signer.publicKeyOid === OID_EC_PUBLIC_KEY && signer.curveOid ? EC_CURVES[signer.curveOid] : undefined;

    if (!curve) {
      return 'unknown';
    }

    const key = await crypto.subtle.importKey(
      'spki',
      signer.publicKey as BufferSource,
      { name: 'ECDSA', namedCurve: curve.name },
      false,
      ['verify'],
    );

    return (await crypto.subtle.verify(
      { name: 'ECDSA', hash: algorithm },
      key,
      ecdsaToRaw(signature, curve.size) as BufferSource,
      attributes as BufferSource,
    ))
      ? 'valid'
      : 'modified';
  } catch {
    return 'unknown';
  }
}

/** Converts a DER ECDSA signature (SEQUENCE of r, s) to the fixed-width r||s form WebCrypto expects. */
function ecdsaToRaw(signature: Uint8Array, size: number): Uint8Array {
  const [r, s] = children(signature, readNode(signature, 0));
  const output = new Uint8Array(size * 2);

  [r, s].forEach((part, index) => {
    const value = signature.subarray(part.body, part.end);
    const trimmed = value.subarray(Math.max(value.length - size, 0));

    output.set(trimmed, index * size + size - trimmed.length);
  });

  return output;
}

function digestBounds(bytes: Uint8Array, set: Node): [number, number] {
  const [value] = children(bytes, set);

  return [value.body, value.end];
}

async function parseSignedData(
  bytes: Uint8Array,
  container: Uint8Array,
  ranges: number[],
): Promise<Omit<PdfSignature, 'reason' | 'location' | 'coversWholeDocument'> | null> {
  const root = readNode(container, 0);
  const [, explicit] = children(container, root);
  const [signedData] = children(container, explicit);
  const parts = children(container, signedData);
  const certificateSet = parts.find((node) => node.tag === 0xa0);
  const signerInfos = parts[parts.length - 1];

  if (!certificateSet || !signerInfos) {
    return null;
  }

  const certificates = children(container, certificateSet)
    .filter((node) => node.tag === 0x30)
    .map((node) => parseCertificate(container, node));
  const signerInfo = children(container, children(container, signerInfos)[0]);
  const sid = signerInfo[1];
  const digestOid = oidOf(container, children(container, signerInfo[2])[0]);
  const signedAttributes = signerInfo.find((node) => node.tag === 0xa0);
  const signatureNode = signerInfo.filter((node) => node.tag === 0x04).pop() ?? signerInfo[signerInfo.length - 1];
  let signer: ParsedCertificate | undefined;

  if (sid.tag === 0x30) {
    const [issuer, serial] = children(container, sid);
    const issuerKey = hex(raw(container, issuer));
    const serialKey = hex(container.subarray(serial.body, serial.end)).replace(/^(00)+(?=.)/, '');

    signer = certificates.find((entry) => entry.issuerKey === issuerKey && entry.info.serialNumber === serialKey);
  }

  signer ??= certificates[0];

  if (!signer) {
    return null;
  }

  const chain: ParsedCertificate[] = [signer];

  while (chain.length < certificates.length) {
    const tail = chain[chain.length - 1];
    const issuer = tail.info.selfSigned
      ? undefined
      : certificates.find((entry) => entry.subjectKey === tail.issuerKey && !chain.includes(entry));

    if (!issuer) {
      break;
    }

    chain.push(issuer);
  }

  const signingTime = signedAttributes
    ? children(container, signedAttributes)
        .map((node) => children(container, node))
        .find(([oid]) => oidOf(container, oid) === OID_SIGNING_TIME)
    : undefined;
  const infos = chain.map((entry) => entry.info);
  const top = infos[infos.length - 1];

  return {
    signer: signer.info,
    chain: infos,
    signedAt: signingTime ? timeOf(container, children(container, signingTime[1])[0]) : null,
    integrity: await checkIntegrity(
      bytes,
      container,
      ranges,
      signedAttributes,
      digestOid,
      signer,
      container.subarray(signatureNode.body, signatureNode.end),
    ),
    komdigiRoot: infos.some((entry) => KOMDIGI_ROOT_PATTERN.test(`${entry.subject} ${entry.issuer}`)),
    rootCountry: top.selfSigned ? top.country : (/(?:^|, )C=([A-Z]{2})$/.exec(top.issuer)?.[1] ?? top.country),
  };
}

/**
 * Lists the signatures found in a PDF, in document order. Unparseable signatures are skipped, and a PDF
 * without signatures yields an empty list.
 */
export async function extractPdfSignatures(bytes: Uint8Array): Promise<PdfSignature[]> {
  const text = new TextDecoder('latin1').decode(bytes);
  const signatures: PdfSignature[] = [];

  for (const match of text.matchAll(/\/ByteRange\s*\[\s*(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s*\]/g)) {
    const ranges = match.slice(1, 5).map(Number);
    const [, lengthA, offsetB, lengthB] = ranges;

    if (offsetB + lengthB > bytes.length || offsetB < lengthA) {
      continue;
    }

    const contents = /^\s*<([0-9A-Fa-f\s]+)>/.exec(text.slice(lengthA, offsetB));

    if (!contents) {
      continue;
    }

    try {
      const digits = contents[1].replace(/\s+/g, '');
      const container = Uint8Array.from((digits.match(/../g) ?? []).map((pair) => parseInt(pair, 16)));
      const parsed = await parseSignedData(bytes, container, ranges);

      if (!parsed) {
        continue;
      }

      const index = match.index ?? 0;
      const dictionary = text.slice(
        Math.max(text.lastIndexOf('obj', index), 0),
        text.indexOf('endobj', index) === -1 ? index + 2000 : text.indexOf('endobj', index),
      );

      signatures.push({
        ...parsed,
        signedAt: parsed.signedAt ?? pdfDate(dictionaryString(dictionary, 'M')),
        reason: dictionaryString(dictionary, 'Reason'),
        location: dictionaryString(dictionary, 'Location'),
        coversWholeDocument: offsetB + lengthB === bytes.length,
      });
    } catch {
      continue;
    }
  }

  return signatures;
}
