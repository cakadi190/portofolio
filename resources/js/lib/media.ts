import { store } from '@/wayfinder/routes/admin/media';
import type { MediaItem } from '@/types/media';

function xsrfToken(): string {
  const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

  return match ? decodeURIComponent(match[1]) : '';
}

/**
 * Upload one file into the media library. Rejects with a readable message
 * (the first validation error, or a generic one) when the upload fails.
 */
export async function uploadMedia(file: File): Promise<MediaItem> {
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

    throw new Error(
      payload?.errors?.file?.[0] ??
        'Unggahan gagal. Gunakan JPG, PNG, WebP, GIF, atau PDF maksimal 10 MB.',
    );
  }

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
