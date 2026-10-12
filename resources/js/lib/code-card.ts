const COPY_LABEL = 'Salin';
const COPIED_LABEL = 'Tersalin!';
const DEFAULT_TITLE = 'Kode';

export const CODE_TITLE_MAX_LENGTH = 60;

const COPY_ICON =
  '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>';
const CHECK_ICON =
  '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>';

/** Trims a user supplied tab title to something safe to store and render. */
export function normalizeCodeTitle(value: unknown): string | null {
  const title =
    typeof value === 'string'
      ? // eslint-disable-next-line no-control-regex
        value.replace(/[\x00-\x1f<>"]/g, '').trim()
      : '';

  return title ? title.slice(0, CODE_TITLE_MAX_LENGTH) : null;
}

/** Copies text using the async clipboard API, falling back to `execCommand`. */
export async function copyText(text: string): Promise<boolean> {
  try {
    await navigator.clipboard.writeText(text);

    return true;
  } catch {
    const area = document.createElement('textarea');

    area.value = text;
    area.setAttribute('readonly', '');
    area.style.cssText = 'position:fixed;opacity:0;top:0;left:0';
    document.body.append(area);
    area.select();

    try {
      return document.execCommand('copy');
    } catch {
      return false;
    } finally {
      area.remove();
    }
  }
}

/** Button that copies `getText()` and briefly confirms with a check mark. */
export function createCopyButton(getText: () => string): HTMLButtonElement {
  const button = document.createElement('button');
  let timer: ReturnType<typeof setTimeout> | undefined;

  button.type = 'button';
  button.className = 'code-card__copy';
  button.title = 'Salin kode';
  button.setAttribute('aria-label', 'Salin kode');
  button.innerHTML = `${COPY_ICON}<span>${COPY_LABEL}</span>`;

  button.addEventListener('mousedown', (event) => event.preventDefault());
  button.addEventListener('click', async () => {
    const copied = await copyText(getText());

    clearTimeout(timer);
    button.classList.toggle('is-copied', copied);
    button.innerHTML = copied
      ? `${CHECK_ICON}<span>${COPIED_LABEL}</span>`
      : `${COPY_ICON}<span>Gagal</span>`;
    timer = setTimeout(() => {
      button.classList.remove('is-copied');
      button.innerHTML = `${COPY_ICON}<span>${COPY_LABEL}</span>`;
    }, 1800);
  });

  return button;
}

/** VS Code style header: a tab carrying the title, and the copy button. */
export function createCodeHeader(
  tab: HTMLElement,
  getText: () => string,
): HTMLDivElement {
  const header = document.createElement('div');
  const strip = document.createElement('div');

  header.className = 'code-card__header';
  header.contentEditable = 'false';
  strip.className = 'code-card__tab';
  strip.append(tab);
  header.append(strip, createCopyButton(getText));

  return header;
}

/**
 * Wraps every `pre` under `root` in a titled card with a copy button.
 * Idempotent, so it can run again whenever the HTML changes.
 */
export function enhanceCodeCards(root: ParentNode = document): void {
  root.querySelectorAll<HTMLPreElement>('pre').forEach((pre) => {
    if (pre.parentElement?.classList.contains('code-card')) {
      return;
    }

    const card = document.createElement('div');
    const label = document.createElement('span');

    card.className = 'code-card';
    label.className = 'code-card__title';
    label.textContent = pre.dataset.codeTitle || DEFAULT_TITLE;

    pre.replaceWith(card);
    card.append(
      createCodeHeader(label, () => pre.textContent ?? ''),
      pre,
    );
  });
}
