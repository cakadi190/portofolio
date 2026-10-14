import { toast } from '@/lib/toast';
import { store } from '@/wayfinder/routes/admin/media';
import type { MediaItem } from '@/types/media';

function xsrfToken(): string {
  const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

  return match ? decodeURIComponent(match[1]) : '';
}

/** Longest side, in pixels, kept before an image leaves the browser. */
export const MAX_UPLOAD_SIDE = 3000;

/**
 * The server decodes uploads with GD inside a small container, so a photo of
 * tens of megapixels would exhaust its memory. Oversized bitmaps are scaled
 * down here (the server caps stored images at 1920 px anyway) and re-encoded
 * as WebP, which keeps transparency. Anything that cannot be decoded, or does
 * not need shrinking, is uploaded untouched.
 */
export async function shrinkLargeImage(file: File): Promise<File> {
  const shrinkable =
    file.type.startsWith('image/') &&
    !['image/svg+xml', 'image/gif'].includes(file.type);

  if (!shrinkable || typeof createImageBitmap !== 'function') {
    return file;
  }

  try {
    const source = await createImageBitmap(file);
    const longest = Math.max(source.width, source.height);

    if (longest <= MAX_UPLOAD_SIDE) {
      source.close();

      return file;
    }

    const scale = MAX_UPLOAD_SIDE / longest;
    const width = Math.round(source.width * scale);
    const height = Math.round(source.height * scale);
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    canvas.getContext('2d')?.drawImage(source, 0, 0, width, height);
    source.close();

    const blob = await new Promise<Blob | null>((resolve) =>
      canvas.toBlob(resolve, 'image/webp', 0.9),
    );

    return blob
      ? new File([blob], file.name.replace(/\.[^.]+$/, '') + '.webp', {
          type: 'image/webp',
        })
      : file;
  } catch {
    return file;
  }
}

/**
 * Upload one file into the media library. Rejects with a readable message
 * (the first validation error, or a generic one) when the upload fails.
 */
export async function uploadMedia(original: File): Promise<MediaItem> {
  const file = await shrinkLargeImage(original);
  const body = new FormData();
  body.append('file', file);

  const response = await fetch(store().url, {
    method: 'POST',
    body,
    headers: { Accept: 'application/json', 'X-XSRF-TOKEN': xsrfToken() },
    credentials: 'same-origin',
  });

  if (!response.ok) {
    const payload = (await response.json().catch(() => null)) as {
      errors?: Record<string, string[]>;
    } | null;

    const message =
      payload?.errors?.file?.[0] ??
      (response.status === 413
        ? 'Berkas terlalu besar untuk server. Perkecil ukurannya (maksimal 10 MB).'
        : response.status >= 500
          ? 'Server gagal memproses unggahan. Coba lagi, atau perkecil resolusi gambar.'
          : 'Unggahan gagal. Gunakan JPG, PNG, WebP, GIF, atau PDF maksimal 10 MB.');

    toast.error(message);

    throw new Error(message);
  }

  toast.success(`${original.name} berhasil diunggah.`);

  return (await response.json()) as MediaItem;
}

export function formatBytes(bytes: number): string {
  if (bytes < 1024) {
    return `${bytes} B`;
  }

  return bytes < 1024 * 1024
    ? `${(bytes / 1024).toFixed(0)} KB`
    : `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}
