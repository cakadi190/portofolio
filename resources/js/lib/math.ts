import { obscureMathClass } from '@/lib/math-classes';

/**
 * KaTeX is large, so it is loaded the first time an equation needs drawing.
 * Equations are stored as plain LaTeX text inside `.wp-math` (inline) and
 * `.wp-math-block` (display) elements and drawn client-side; until KaTeX is
 * ready, or if it fails, the LaTeX source stays visible.
 */
type Katex = typeof import('katex').default;

let katexPromise: Promise<Katex> | null = null;

function loadKatex(): Promise<Katex> {
  katexPromise ??= Promise.all([
    import('katex'),
    import('katex/dist/katex.min.css'),
  ]).then(([module]) => module.default);

  return katexPromise;
}

/** Renames every class the library rendered (see `math-classes.ts`). */
export function obscureRendered(root: HTMLElement): void {
  root.querySelectorAll<HTMLElement>('[class]').forEach((element) => {
    element.className = element.className
      .split(/\s+/)
      .filter(Boolean)
      .map(obscureMathClass)
      .join(' ');
  });
}

/** Draws `latex` inside `element`, falling back to the source text. */
export function renderMathInto(
  element: HTMLElement,
  latex: string,
  displayMode: boolean,
): void {
  element.textContent = latex;
  element.dataset.latex = latex;

  void loadKatex()
    .then((katex) => {
      if (element.dataset.latex !== latex) return;

      katex.render(latex, element, {
        displayMode,
        throwOnError: false,
        strict: 'ignore',
        trust: false,
        output: 'htmlAndMathml',
      });
      obscureRendered(element);
    })
    .catch(() => {
      element.textContent = latex;
    });
}

/** Draws every equation found in rendered article HTML. */
export function renderMathIn(root: HTMLElement): void {
  root
    .querySelectorAll<HTMLElement>('.wp-math, .wp-math-block')
    .forEach((element) => {
      if (element.dataset.latex !== undefined) return;

      renderMathInto(
        element,
        element.textContent ?? '',
        element.classList.contains('wp-math-block'),
      );
    });
}
