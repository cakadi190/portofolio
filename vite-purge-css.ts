import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join } from 'node:path';
import postcss, { type AtRule, type Rule } from 'postcss';

/**
 * Build-time purge of the public stylesheet (`resources/css/app.scss`).
 *
 * Bootstrap ships every component and every responsive utility, but the site
 * uses a fraction of them; the whole file is render-blocking, so unused rules
 * cost first paint. A rule is dropped only when each of its selectors needs a
 * class that appears nowhere in the sources (Svelte, TypeScript, Blade, PHP,
 * seeders, language files) and does not match the safelist below. Selectors
 * without classes, `:is()/:where()/:has()` selectors, keyframes, font faces and
 * custom properties are always kept.
 *
 * Dynamic class names are covered two ways: `prefix-${x}` / `'prefix-' + x`
 * keep every class starting with that prefix, and the safelist keeps classes
 * that Bootstrap's own JavaScript or the rich-text editor content can add.
 */

const SOURCE_DIRECTORIES = [
  'resources/js',
  'resources/views',
  'app',
  'database/seeders',
  'lang',
];
const SOURCE_EXTENSIONS = /\.(svelte|ts|js|php|json)$/;
const IGNORED = /(^|\/)(wayfinder|node_modules|vendor)(\/|$)|\.test\.ts$/;

/**
 * Classes added at runtime that never appear in the sources: state classes toggled by Bootstrap's JS, the
 * markup its Modal, Offcanvas, Dropdown and Tooltip plugins create (the only ones this project uses), and
 * classes that editor-authored article HTML can carry.
 */
const SAFELIST: RegExp[] = [
  /^(show|showing|hiding|fade|active|disabled|open|focus|visible|invisible)$/,
  /^(modal|offcanvas|dropdown|tooltip|bs-tooltip)(-|$)/,
  /^(table|blockquote|figure|img-|lead$|small$|mark$|display-|text-(start|center|end|break|wrap|nowrap|truncate)|fw-|fst-)/,
  /^(wp-block|is-align|callout)/,
  // Overlay motion for Bootstrap popovers and the third-party select/date pickers, whose markup lives in node_modules.
  /^(popover|bs-popover|popper|svelte-select|calendars-container|datepicker)/,
  // Added by Tiptap's table extension at runtime.
  /^(selectedCell|tableWrapper)$/,
];

function collectFiles(directory: string, files: string[] = []): string[] {
  for (const entry of readdirSync(directory)) {
    const path = join(directory, entry);

    if (IGNORED.test(path)) {
      continue;
    }

    if (statSync(path).isDirectory()) {
      collectFiles(path, files);
    } else if (SOURCE_EXTENSIONS.test(entry)) {
      files.push(path);
    }
  }

  return files;
}

function scanSources(root: string): { tokens: Set<string>; prefixes: string[] } {
  const tokens = new Set<string>();
  const prefixes = new Set<string>();

  for (const directory of SOURCE_DIRECTORIES) {
    let files: string[] = [];

    try {
      files = collectFiles(join(root, directory));
    } catch {
      continue;
    }

    for (const file of files) {
      const text = readFileSync(file, 'utf8');

      for (const match of text.matchAll(/[A-Za-z_][\w-]*/g)) {
        tokens.add(match[0]);
      }

      for (const match of text.matchAll(/([A-Za-z][\w-]*-)\$\{/g)) {
        prefixes.add(match[1]);
      }

      for (const match of text.matchAll(/['"`]([A-Za-z][\w-]*-)['"`]\s*\+/g)) {
        prefixes.add(match[1]);
      }
    }
  }

  return { tokens, prefixes: [...prefixes] };
}

/** Removes `:not(...)` groups (balanced), since a negated class never requires its presence. */
function withoutNegations(selector: string): string {
  let result = selector;
  let index = result.indexOf(':not(');

  while (index !== -1) {
    let depth = 0;
    let end = index + 4;

    for (; end < result.length; end++) {
      if (result[end] === '(') {
        depth++;
      } else if (result[end] === ')' && --depth === 0) {
        break;
      }
    }

    result = result.slice(0, index) + result.slice(end + 1);
    index = result.indexOf(':not(');
  }

  return result;
}

function requiredClasses(selector: string): string[] {
  const stripped = withoutNegations(selector)
    .replace(/\[[^\]]*\]/g, '')
    .replace(/"[^"]*"|'[^']*'/g, '');

  return [...stripped.matchAll(/\.((?:\\.|[\w-])+)/g)].map((match) =>
    match[1].replace(/\\(.)/g, '$1'),
  );
}

export function purgeUnusedCss(options: { root: string; entry: string }) {
  let tokens = new Set<string>();
  let prefixes: string[] = [];

  const isUsed = (className: string): boolean =>
    tokens.has(className) ||
    prefixes.some((prefix) => className.startsWith(prefix)) ||
    SAFELIST.some((pattern) => pattern.test(className));

  /** Bytes kept only because a safelist pattern matched (PURGE_REPORT=1), by pattern. */
  const keptBySafelist = new Map<string, number>();
  const reason = (className: string): RegExp | null =>
    tokens.has(className) || prefixes.some((prefix) => className.startsWith(prefix))
      ? null
      : (SAFELIST.find((pattern) => pattern.test(className)) ?? null);

  const selectorIsNeeded = (selector: string): boolean => {
    if (/:(is|where|has)\(/.test(selector)) {
      return true;
    }

    return requiredClasses(selector).every(isUsed);
  };

  return {
    name: 'purge-unused-css',
    apply: 'build' as const,
    enforce: 'post' as const,
    buildStart() {
      ({ tokens, prefixes } = scanSources(options.root));
    },
    async generateBundle(
      _output: unknown,
      bundle: Record<string, { type: string; source?: string | Uint8Array; originalFileNames?: string[]; fileName: string }>,
    ) {
      for (const asset of Object.values(bundle)) {
        if (
          asset.type !== 'asset' ||
          typeof asset.source !== 'string' ||
          !asset.originalFileNames?.some((name) => name.endsWith(options.entry))
        ) {
          continue;
        }

        const before = asset.source.length;
        const root = postcss.parse(asset.source);

        root.walkRules((rule: Rule) => {
          if (rule.parent?.type === 'atrule' && /keyframes$/i.test((rule.parent as AtRule).name)) {
            return;
          }

          const kept = rule.selectors.filter(selectorIsNeeded);

          if (process.env.PURGE_REPORT) {
            for (const selector of kept) {
              const classes = requiredClasses(selector);
              const pattern = classes.map(reason).find(Boolean);

              if (pattern && classes.every((name) => isUsed(name))) {
                keptBySafelist.set(String(pattern), (keptBySafelist.get(String(pattern)) ?? 0) + rule.toString().length);
              }
            }
          }

          if (kept.length === 0) {
            rule.remove();
          } else if (kept.length !== rule.selectors.length) {
            rule.selectors = kept;
          }
        });

        root.walkAtRules((atRule: AtRule) => {
          if (atRule.nodes && atRule.nodes.length === 0) {
            atRule.remove();
          }
        });

        asset.source = root.toString();

        if (process.env.PURGE_REPORT) {
          for (const [pattern, bytes] of [...keptBySafelist].sort((a, b) => b[1] - a[1])) {
            console.info(`[purge-unused-css]   kept only by safelist ${pattern}: ${(bytes / 1024).toFixed(1)} KB`);
          }
        }

        console.info(
          `[purge-unused-css] ${asset.fileName}: ${(before / 1024).toFixed(1)} KB -> ${(asset.source.length / 1024).toFixed(1)} KB`,
        );
      }
    },
  };
}
