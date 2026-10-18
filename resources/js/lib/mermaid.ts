/**
 * Mermaid is large, so it is loaded the first time a diagram needs drawing.
 * Diagrams are stored as the plain Mermaid source inside `pre.wp-mermaid` (a `pre`, so the
 * HTML minifier keeps its line breaks) and drawn client-side; until Mermaid is ready, or if
 * the source is invalid, the source text stays visible.
 */
type Mermaid = typeof import('mermaid').default;

let mermaidPromise: Promise<Mermaid> | null = null;
let diagramCount = 0;

function loadMermaid(): Promise<Mermaid> {
  mermaidPromise ??= import('mermaid').then((module) => module.default);

  return mermaidPromise;
}

function isDarkTheme(): boolean {
  return document.documentElement.getAttribute('data-bs-theme') === 'dark';
}

/** Draws `source` inside `element`, falling back to the source text. */
export function renderMermaidInto(element: HTMLElement, source: string): void {
  element.textContent = source;
  element.dataset.mermaid = source;
  element.classList.remove('is-rendered', 'is-invalid');

  if (source.trim() === '') {
    return;
  }

  void loadMermaid()
    .then(async (mermaid) => {
      if (element.dataset.mermaid !== source) return;

      // `strict` keeps Mermaid's own sanitising on: no click handlers, no HTML labels.
      mermaid.initialize({
        startOnLoad: false,
        securityLevel: 'strict',
        theme: isDarkTheme() ? 'dark' : 'default',
      });

      if (!(await mermaid.parse(source, { suppressErrors: true }))) {
        element.classList.add('is-invalid');

        return;
      }

      const { svg } = await mermaid.render(
        `wp-mermaid-${(diagramCount += 1)}`,
        source,
      );

      if (element.dataset.mermaid !== source) return;

      element.innerHTML = svg;
      element.classList.add('is-rendered');
    })
    .catch(() => {
      element.textContent = source;
      element.classList.add('is-invalid');
    });
}

/** Draws every diagram found in rendered article HTML. */
export function renderMermaidIn(root: HTMLElement): void {
  root.querySelectorAll<HTMLElement>('pre.wp-mermaid').forEach((element) => {
    if (element.dataset.mermaid !== undefined) return;

    renderMermaidInto(element, element.textContent ?? '');
  });
}
