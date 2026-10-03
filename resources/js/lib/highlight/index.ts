import {
  highlight,
  highlightAuto,
  listLanguages,
  registerLanguage,
} from './engine';
import type { HighlightResult } from './engine';
import { builtinLanguages } from './languages';

export { highlight, highlightAuto, registerLanguage } from './engine';
export type { HighlightResult, Language, Mode } from './engine';

for (const language of builtinLanguages) {
  registerLanguage(language);
}

const SKIPPED = new Set(['plaintext', 'text', 'txt', 'nohighlight']);

/** `language-php`, `lang-php` or `data-language="php"` on the `<code>` or its `<pre>`. */
function declaredLanguage(code: HTMLElement): string | null {
  for (const element of [code, code.parentElement]) {
    if (!element) {
      continue;
    }

    const fromClass = [...element.classList]
      .map((name) => /^(?:language|lang)-(.+)$/.exec(name)?.[1])
      .find(Boolean);
    const declared = fromClass ?? element.dataset.language;

    if (declared) {
      return declared.toLowerCase();
    }
  }

  return null;
}

/**
 * Highlights one `<code>` element in place, using its declared language or
 * auto-detection. Idempotent; unknown or plain-text languages are left alone.
 */
export function highlightElement(code: HTMLElement): void {
  if (code.dataset.highlighted === 'yes') {
    return;
  }

  const source = code.textContent ?? '';
  const declared = declaredLanguage(code);

  if (declared && SKIPPED.has(declared)) {
    return;
  }

  const result = highlightSource(source, declared);

  if (!result) {
    return;
  }

  code.replaceChildren(result.fragment);
  code.classList.add('hljs', `language-${result.language}`);
  code.dataset.highlighted = 'yes';
}

/** Highlights every `pre code` block under `root`. */
export function highlightAll(root: ParentNode = document): void {
  root
    .querySelectorAll<HTMLElement>('pre code')
    .forEach((code) => highlightElement(code));
}

/** Names of every registered language, for language pickers. */
export function languageNames(): string[] {
  return listLanguages().map((language) => language.name);
}

/**
 * Highlights `source` with `language` (auto-detected when empty). `null` means
 * the text should stay unhighlighted.
 */
export function highlightSource(
  source: string,
  language?: string | null,
): HighlightResult | null {
  const result = language
    ? highlight(source, language.toLowerCase())
    : highlightAuto(source);

  return !result || result.language === 'plaintext' ? null : result;
}
