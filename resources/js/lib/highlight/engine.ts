/**
 * Small DOM-based port of highlight.js' tokenizer.
 *
 * Same model as the original: a language is a tree of "modes" (begin / end /
 * contains / keywords) walked with a stack, emitting `hljs-*` scoped spans. The
 * output is built with `createElement` / `createTextNode` only, never
 * `innerHTML`, so highlighted source can never turn into markup.
 *
 * Differences from highlight.js: no `illegal`, `starts` or `match` keys, no
 * backreferences in patterns, and a much simpler relevance score for
 * auto-detection.
 */

export interface Mode {
  /** Wraps the whole mode (begin, body and end) in `hljs-<scope>`. */
  scope?: string;
  /** Wraps only the text matched by `begin`. */
  beginScope?: string;
  /** Wraps only the text matched by `end`. */
  endScope?: string;
  begin: RegExp | string;
  /** Without `end` (and without `endsWithParent`) the mode is a single token. */
  end?: RegExp | string;
  /** `'self'` is this mode, `'$root'` is the language's top level. */
  contains?: (Mode | 'self' | '$root')[];
  keywords?: Record<string, string>;
  /** Alternative definitions sharing the rest of this mode's keys. */
  variants?: Partial<Mode>[];
  /** Also ends where the parent mode ends (without consuming it). */
  endsWithParent?: boolean;
  /** Highlight the body up to `end` with another registered language. */
  subLanguage?: string;
  /** Added to the auto-detection score every time the mode matches. */
  relevance?: number;
}

export interface Language {
  name: string;
  aliases?: string[];
  caseInsensitive?: boolean;
  /** Treat `word(` as a function call (`hljs-title function_`). */
  functionCalls?: boolean;
  keywords?: Record<string, string>;
  contains: Mode[];
}

interface Terminator {
  kind: 'end' | 'parentEnd' | 'begin';
  mode?: Compiled;
}

interface Compiled {
  scope?: string;
  beginScope?: string;
  endScope?: string;
  begin: string;
  end: string | null;
  endsWithParent: boolean;
  isLeaf: boolean;
  subLanguage?: string;
  relevance: number;
  keywords: Map<string, string> | null;
  source: Mode | null;
  parent: Compiled | null;
  language: Language;
  endSources: string[];
  built: boolean;
  children: Compiled[];
  terminators: RegExp | null;
  slotByGroup: Map<number, Terminator>;
  endRe: RegExp | null;
}

export interface HighlightResult {
  language: string;
  relevance: number;
  fragment: DocumentFragment;
}

const WORD = /[A-Za-z_$][\w$]*/g;

function sourceOf(pattern: RegExp | string): string {
  return typeof pattern === 'string' ? pattern : pattern.source;
}

function countGroups(source: string): number {
  return (new RegExp(`${source}|`).exec('') ?? []).length - 1;
}

export function scopeClasses(scope: string): string[] {
  const [first, ...rest] = scope.split('.');

  return [
    `hljs-${first}`,
    ...rest.map((part, index) => part + '_'.repeat(index + 1)),
  ];
}

function createCompiled(
  mode: Mode | null,
  parent: Compiled | null,
  language: Language,
  keywordsFallback: Map<string, string> | null,
): Compiled {
  const keywords = mode?.keywords
    ? toKeywordMap(mode.keywords, language)
    : keywordsFallback;
  const end = mode?.end === undefined ? null : sourceOf(mode.end);
  const endsWithParent = Boolean(mode?.endsWithParent);
  const endSources = [
    ...(end === null ? [] : [end]),
    ...(endsWithParent && parent ? parent.endSources : []),
  ];

  return {
    scope: mode?.scope,
    beginScope: mode?.beginScope,
    endScope: mode?.endScope,
    begin: mode ? sourceOf(mode.begin) : '',
    end,
    endsWithParent,
    isLeaf: mode !== null && end === null && !endsWithParent,
    subLanguage: mode?.subLanguage,
    relevance: mode?.relevance ?? 0,
    keywords,
    source: mode,
    parent,
    language,
    endSources,
    built: false,
    children: [],
    terminators: null,
    slotByGroup: new Map(),
    endRe: null,
  };
}

function toKeywordMap(
  keywords: Record<string, string>,
  language: Language,
): Map<string, string> {
  const map = new Map<string, string>();

  for (const [scope, words] of Object.entries(keywords)) {
    for (const word of words.split(/\s+/).filter(Boolean)) {
      map.set(language.caseInsensitive ? word.toLowerCase() : word, scope);
    }
  }

  return map;
}

function expandVariants(modes: Mode[]): Mode[] {
  return modes.flatMap((mode) =>
    mode.variants
      ? mode.variants.map((variant) => ({
          ...mode,
          variants: undefined,
          ...variant,
        }))
      : [mode],
  );
}

/** Lazily builds a mode's child list and combined terminator regex. */
function build(mode: Compiled, root: Compiled): void {
  if (mode.built) {
    return;
  }

  mode.built = true;

  const entries: (Mode | 'self' | '$root')[] =
    mode === root ? mode.language.contains : (mode.source?.contains ?? []);

  mode.children = [];

  for (const entry of entries) {
    if (entry === 'self') {
      mode.children.push(mode);
    } else if (entry === '$root') {
      mode.children.push(...root.children);
    } else {
      for (const variant of expandVariants([entry])) {
        mode.children.push(
          createCompiled(variant, mode, mode.language, mode.keywords),
        );
      }
    }
  }

  const parts: string[] = [];
  let group = 1;

  const add = (src: string, terminator: Terminator): void => {
    parts.push(`(${src})`);
    mode.slotByGroup.set(group, terminator);
    group += 1 + countGroups(src);
  };

  mode.endSources.forEach((src, index) => {
    add(src, { kind: index === 0 && mode.end !== null ? 'end' : 'parentEnd' });
  });

  for (const child of mode.children) {
    add(child.begin, { kind: 'begin', mode: child });
  }

  const flags = `gm${mode.language.caseInsensitive ? 'i' : ''}`;

  mode.terminators = parts.length ? new RegExp(parts.join('|'), flags) : null;
  mode.endRe = mode.end === null ? null : new RegExp(mode.end, flags);
}

class Builder {
  readonly fragment = document.createDocumentFragment();
  private stack: Node[] = [this.fragment];
  relevance = 0;

  private get top(): Node {
    return this.stack[this.stack.length - 1];
  }

  open(scope: string): void {
    const span = document.createElement('span');

    span.classList.add(...scopeClasses(scope));
    this.top.appendChild(span);
    this.stack.push(span);
  }

  close(): void {
    this.stack.pop();
  }

  text(value: string): void {
    if (value === '') {
      return;
    }

    const last = this.top.lastChild;

    if (last && last.nodeType === Node.TEXT_NODE) {
      (last as Text).appendData(value);
    } else {
      this.top.appendChild(document.createTextNode(value));
    }
  }

  scoped(value: string, scope: string | undefined): void {
    if (!scope) {
      this.text(value);

      return;
    }

    this.open(scope);
    this.text(value);
    this.close();
  }
}

const registry = new Map<string, Language>();
const compiledRoots = new WeakMap<Language, Compiled>();

export function registerLanguage(language: Language): void {
  registry.set(language.name, language);

  for (const alias of language.aliases ?? []) {
    registry.set(alias, language);
  }
}

export function getLanguage(name: string): Language | undefined {
  return registry.get(name.toLowerCase());
}

export function listLanguages(): Language[] {
  return [...new Set(registry.values())];
}

function rootOf(language: Language): Compiled {
  let root = compiledRoots.get(language);

  if (!root) {
    root = createCompiled(
      null,
      null,
      language,
      language.keywords ? toKeywordMap(language.keywords, language) : null,
    );
    compiledRoots.set(language, root);
    build(root, root);
  }

  return root;
}

function emitWords(
  out: Builder,
  code: string,
  from: number,
  to: number,
  mode: Compiled,
): void {
  const segment = code.slice(from, to);

  if (!mode.keywords) {
    out.text(segment);

    return;
  }

  const caseInsensitive = mode.language.caseInsensitive;
  let cursor = 0;

  WORD.lastIndex = 0;

  for (let match = WORD.exec(segment); match; match = WORD.exec(segment)) {
    const word = match[0];
    let scope = mode.keywords.get(caseInsensitive ? word.toLowerCase() : word);

    if (
      !scope &&
      mode.language.functionCalls &&
      /^\s*\(/.test(code.slice(from + match.index + word.length, to + 80))
    ) {
      scope = 'title.function';
    }

    if (!scope) {
      continue;
    }

    out.text(segment.slice(cursor, match.index));
    out.scoped(word, scope);
    out.relevance += scope === 'title.function' ? 0 : 1;
    cursor = match.index + word.length;
  }

  out.text(segment.slice(cursor));
}

function run(out: Builder, code: string, root: Compiled): void {
  const stack: Compiled[] = [root];
  let position = 0;

  while (position <= code.length) {
    const mode = stack[stack.length - 1];

    build(mode, root);

    const terminators = mode.terminators;

    if (!terminators) {
      emitWords(out, code, position, code.length, mode);

      return;
    }

    terminators.lastIndex = position;

    const match = terminators.exec(code);

    if (!match) {
      emitWords(out, code, position, code.length, mode);

      return;
    }

    emitWords(out, code, position, match.index, mode);

    // The first defined capture group identifies the terminator that matched.
    let slotIndex = 1;

    while (match[slotIndex] === undefined) {
      slotIndex++;
    }

    const slot = mode.slotByGroup.get(slotIndex) as Terminator;
    const text = match[0];

    position = match.index + text.length;

    if (slot.kind === 'parentEnd') {
      if (mode.scope) {
        out.close();
      }

      stack.pop();
      position = match.index;
    } else if (slot.kind === 'end') {
      out.scoped(text, mode.endScope);

      if (mode.scope) {
        out.close();
      }

      stack.pop();
    } else {
      const child = slot.mode as Compiled;

      if (text === '') {
        out.text(code.charAt(match.index));
        position = match.index + 1;

        continue;
      }

      out.relevance += child.relevance;

      if (child.scope) {
        out.open(child.scope);
      }

      out.scoped(text, child.beginScope);

      if (child.isLeaf) {
        if (child.scope) {
          out.close();
        }

        continue;
      }

      stack.push(child);

      if (child.subLanguage) {
        const sub = getLanguage(child.subLanguage);

        build(child, root);

        if (sub && child.endRe) {
          child.endRe.lastIndex = position;

          const found = child.endRe.exec(code);
          const stop = found ? found.index : code.length;

          run(out, code.slice(position, stop), rootOf(sub));
          position = stop;
        }
      }
    }
  }
}

export function highlight(code: string, languageName: string): HighlightResult {
  const language = getLanguage(languageName);
  const out = new Builder();

  if (!language) {
    out.text(code);

    return { language: 'plaintext', relevance: 0, fragment: out.fragment };
  }

  run(out, code, rootOf(language));

  return {
    language: language.name,
    relevance: out.relevance,
    fragment: out.fragment,
  };
}

/** Picks the best registered language by relevance; `null` when nothing scores. */
export function highlightAuto(
  code: string,
  subset?: string[],
  threshold = 3,
): HighlightResult | null {
  const candidates = subset
    ? subset.flatMap((name) => getLanguage(name) ?? [])
    : listLanguages();
  let best: HighlightResult | null = null;

  for (const language of candidates) {
    const result = highlight(code, language.name);

    if (!best || result.relevance > best.relevance) {
      best = result;
    }
  }

  return best && best.relevance >= threshold ? best : null;
}
