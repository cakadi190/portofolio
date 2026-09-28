import type { LinkComponentBaseProps } from '@inertiajs/core';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

export function toUrl(
  href: NonNullable<LinkComponentBaseProps['href']>,
): string {
  return typeof href === 'string' ? href : href.url;
}

/**
 * Resolve a stored image value into a loadable URL. Mirrors
 * `App\Services\ImageService::url()`: absolute and web-root values pass
 * through, anything else is a path on the public disk under `/storage`.
 */
export function storageUrl(path: string | null | undefined): string | null {
  if (!path) {
    return null;
  }

  if (/^(https?:)?\/\//.test(path) || path.startsWith('/')) {
    return path;
  }

  return `/storage/${path}`;
}

const dateFormatter = new Intl.DateTimeFormat('id-ID', {
  day: 'numeric',
  month: 'long',
  year: 'numeric',
});

/**
 * Format a date string into a human-friendly Indonesian date
 * (e.g. "12 Juli 2022"). Falls back to the raw value when unparseable.
 */
export function formatDate(value: string | null | undefined): string {
  if (!value) {
    return '';
  }

  const date = new Date(value);

  return Number.isNaN(date.getTime()) ? value : dateFormatter.format(date);
}
