/**
 * Equation markup is rendered by a third-party typesetting library whose class
 * names and font files are a recognisable fingerprint. Every class it emits is
 * renamed here (`katex…` → `mx…`, anything else → `mx-…`), the same way for the
 * stylesheet at build time (`vite-obscure-math.ts`) and for the rendered DOM at
 * runtime (`lib/math.ts`), so the two always agree.
 */
export function obscureMathClass(token: string): string {
  return token.startsWith('katex')
    ? `mx${token.slice('katex'.length)}`
    : `mx-${token}`;
}

/** Selector fragments `.name` → `.mx…`; only used on selectors, never on values or urls. */
export function obscureMathSelector(selector: string): string {
  return selector.replace(
    /\.(-?[A-Za-z_][\w-]*)/g,
    (_match, name: string) => `.${obscureMathClass(name)}`,
  );
}
