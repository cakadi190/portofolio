import { t } from '@/lib/i18n';

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
  button.title = t('code.copy_code');
  button.setAttribute('aria-label', t('code.copy_code'));
  button.innerHTML = `${COPY_ICON}<span>${t('code.copy')}</span>`;

  button.addEventListener('mousedown', (event) => event.preventDefault());
  button.addEventListener('click', async () => {
    const copied = await copyText(getText());

    clearTimeout(timer);
    button.classList.toggle('is-copied', copied);
    button.innerHTML = copied
      ? `${CHECK_ICON}<span>${t('code.copied')}</span>`
      : `${COPY_ICON}<span>${t('code.failed')}</span>`;
    timer = setTimeout(() => {
      button.classList.remove('is-copied');
      button.innerHTML = `${COPY_ICON}<span>${t('code.copy')}</span>`;
    }, 1800);
  });

  return button;
}

/** Number of source lines in a code block (a trailing newline does not add one). */
export function codeLineCount(text: string): number {
  return text.replace(/\n$/, '').split('\n').length;
}

/** The "1\n2\n3…" column text shown in the line-number gutter. */
export function lineNumbersText(text: string): string {
  return Array.from(
    { length: codeLineCount(text) },
    (_, index) => index + 1,
  ).join('\n');
}

/** Line-number gutter: sticky inside the scrolling `pre`, excluded from copy and selection. */
export function createGutter(text: string): HTMLSpanElement {
  const gutter = document.createElement('span');

  gutter.className = 'code-card__gutter';
  gutter.contentEditable = 'false';
  gutter.setAttribute('aria-hidden', 'true');
  gutter.textContent = lineNumbersText(text);

  return gutter;
}

/** Plain code of a `pre`, without the gutter. */
export function codeText(pre: HTMLElement): string {
  return pre.querySelector('code')?.textContent ?? pre.textContent ?? '';
}

/**
 * VS Code style header: a tab carrying the title (omitted for untitled
 * blocks), and the copy button.
 */
export function createCodeHeader(
  tab: HTMLElement | null,
  getText: () => string,
): HTMLDivElement {
  const header = document.createElement('div');
  const strip = document.createElement('div');

  header.className = 'code-card__header';
  header.contentEditable = 'false';
  strip.className = 'code-card__tab';

  if (tab) {
    strip.append(tab);
  }

  header.append(strip, createCopyButton(getText));

  return header;
}

/**
 * Wraps every `pre` under `root` in a card with a copy button, an optional
 * title tab (`data-code-title-hidden="1"` turns it off) and an optional line-number
 * gutter (`data-code-lines="1"`). Idempotent, so it can run again whenever the HTML changes.
 */
export function enhanceCodeCards(root: ParentNode = document): void {
  root.querySelectorAll<HTMLPreElement>('pre').forEach((pre) => {
    if (pre.parentElement?.classList.contains('code-card')) {
      return;
    }

    const card = document.createElement('div');
    const untitled = pre.dataset.codeTitleHidden === '1';
    let label: HTMLSpanElement | null = null;

    card.className = untitled ? 'code-card code-card--untitled' : 'code-card';

    if (!untitled) {
      label = document.createElement('span');
      label.className = 'code-card__title';
      label.textContent = pre.dataset.codeTitle || t('code.default_title');
    }

    if (pre.dataset.codeLines === '1') {
      pre.prepend(createGutter(codeText(pre)));
    }

    pre.replaceWith(card);
    card.append(
      createCodeHeader(label, () => codeText(pre)),
      pre,
    );
  });
}
