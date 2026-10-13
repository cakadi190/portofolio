import { toast } from '@/lib/toast';

export type TaxonomyItem = { id: number; name: string };

function xsrfToken(): string {
  const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

  return match ? decodeURIComponent(match[1]) : '';
}

/**
 * Create (or reuse, when the name already exists) a tag or category from the
 * editor. Rejects with a readable message after showing it as a toast.
 */
export async function createTaxonomy(
  url: string,
  name: string,
): Promise<TaxonomyItem> {
  const response = await fetch(url, {
    method: 'POST',
    body: JSON.stringify({ name }),
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-XSRF-TOKEN': xsrfToken(),
    },
    credentials: 'same-origin',
  });

  if (!response.ok) {
    const payload = (await response.json().catch(() => null)) as {
      errors?: Record<string, string[]>;
    } | null;
    const message = payload?.errors?.name?.[0] ?? 'Gagal menambahkan data.';

    toast.error(message);

    throw new Error(message);
  }

  return (await response.json()) as TaxonomyItem;
}
