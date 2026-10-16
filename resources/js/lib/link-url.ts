const ALLOWED = /^(https?:\/\/|mailto:|tel:|\/|#)/i;
const DOMAIN = /^(?:[a-z0-9-]+\.)+[a-z]{2,}(?::\d+)?(?:[/?#]\S*)?$/i;
const EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

/**
 * Turns what a writer typed into a safe link target: keeps http(s)/mailto/tel,
 * site paths and anchors, adds `https://` to bare domains and `mailto:` to bare
 * email addresses. Returns null for anything else (spaces, scripts, plain words).
 */
export function normalizeLinkUrl(input: string): string | null {
  const value = input.trim();

  if (value === '' || /\s/.test(value)) {
    return null;
  }

  if (ALLOWED.test(value)) {
    return value;
  }

  if (EMAIL.test(value)) {
    return `mailto:${value}`;
  }

  return DOMAIN.test(value) ? `https://${value}` : null;
}

/** Host (or path for site-relative links) shown under a link's title in the preview. */
export function linkDisplay(href: string): string {
  try {
    const url = new URL(href, window.location.origin);

    return /^https?:/i.test(href)
      ? `${url.host}${url.pathname === '/' && !url.search ? '' : url.pathname}${url.search}`
      : href;
  } catch {
    return href;
  }
}

/** Whether the preview endpoint can fetch metadata for this link (absolute http(s) only). */
export function isPreviewable(href: string): boolean {
  return /^https?:\/\//i.test(href);
}
