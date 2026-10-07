/**
 * Inertia's `Link` resolves hrefs against the current origin; compare only the
 * path and query so assertions stay origin-agnostic.
 */
export function hrefOf(element: Element): string {
  const url = new URL(
    (element as HTMLAnchorElement).href,
    window.location.origin,
  );

  return `${url.pathname}${url.search}`;
}
