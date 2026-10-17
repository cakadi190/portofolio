import { Extension, Node } from '@tiptap/core';
import Image from '@tiptap/extension-image';
import {
  Table,
  TableCell,
  TableHeader,
  TableRow,
} from '@tiptap/extension-table';
import type { Node as ProseNode } from '@tiptap/pm/model';
import type { EditorView, NodeView } from '@tiptap/pm/view';
import { Plugin, PluginKey } from '@tiptap/pm/state';
import { Decoration, DecorationSet } from '@tiptap/pm/view';
import {
  createCodeHeader,
  createGutter,
  lineNumbersText,
  normalizeCodeTitle,
  CODE_TITLE_MAX_LENGTH,
} from '@/lib/code-card';
import { t } from '@/lib/i18n';
import { highlightSource } from '@/lib/highlight';
import { renderMathInto } from '@/lib/math';

export const ALIGNMENTS = ['left', 'center', 'right'] as const;
export type BlockAlign = (typeof ALIGNMENTS)[number];

export const CALLOUT_TYPES = [
  'info',
  'success',
  'warning',
  'danger',
  'dark',
] as const;
export type CalloutType = (typeof CALLOUT_TYPES)[number];

export const BUTTON_VARIANTS = ['solid', 'outline'] as const;
export type ButtonVariant = (typeof BUTTON_VARIANTS)[number];

function pick<T extends string>(
  allowed: readonly T[],
  value: unknown,
  fallback: T,
): T {
  return allowed.includes(value as T) ? (value as T) : fallback;
}

/** Only web, mail, phone, relative and anchor links may live in a button. */
export function safeHref(href: unknown): string {
  const value = typeof href === 'string' ? href.trim() : '';

  return /^(https?:\/\/|mailto:|tel:|\/|#)/i.test(value) ? value : '#';
}

/** Original-source links are limited to http(s); anything else is dropped. */
export function sourceUrl(value: unknown): string {
  const url = typeof value === 'string' ? value.trim() : '';

  return /^https?:\/\//i.test(url) ? url : '';
}

function alignFromClass(element: Element): BlockAlign {
  return (
    ALIGNMENTS.find((align) =>
      element.classList.contains(`is-align-${align}`),
    ) ?? 'center'
  );
}

/** Link wrapped around an image: a safe URL, or '' when there is none. */
function linkHref(anchor: Element | null): string {
  const value = anchor?.getAttribute('href')?.trim() ?? '';

  return value !== '' && safeHref(value) !== '#' ? value : '';
}

/** `sizes` for images in the editor canvas, which is as wide as the article column. */
export const EDITOR_IMAGE_SIZES = '(min-width: 992px) 800px, 100vw';

/**
 * Removes the editor-only `srcset`/`sizes` from `<img>` tags. They only make the
 * canvas preview light; saved HTML stays plain because the server rebuilds them
 * from the media library when the content is rendered.
 */
export function stripResponsiveAttributes(html: string): string {
  return html.replace(/<img\b[^>]*>/g, (tag) =>
    tag.replace(/\s(?:srcset|sizes)="[^"]*"/g, ''),
  );
}

/**
 * Image rendered as `<figure class="wp-block-image is-align-*">` with an
 * optional `<figcaption>` and a percentage width. Plain `<img>` HTML saved by
 * older content still parses.
 */
export const FigureImage = Image.extend({
  addAttributes() {
    return {
      ...this.parent?.(),
      align: { default: 'center', rendered: false },
      width: { default: null, rendered: false },
      credit: { default: '', rendered: false },
      source: { default: '', rendered: false },
      // Preview-only: set for images picked in this session, never parsed from HTML.
      srcset: { default: null, rendered: false, parseHTML: () => null },
      href: { default: '', rendered: false },
      newTab: { default: false, rendered: false },
    };
  },

  inline: false,
  group: 'block',
  // The caption is the node's own inline content, so it keeps bold, italic,
  // underline and link marks and is edited in place.
  content: 'inline*',
  draggable: true,

  parseHTML() {
    return [
      {
        tag: 'figure.wp-block-image',
        contentElement: (element) =>
          (element as Element).querySelector('figcaption') ??
          document.createElement('figcaption'),
        getAttrs: (element) => {
          const img = element.querySelector('img');
          const src = img?.getAttribute('src');

          if (!img || !src) return false;

          const width = /width:\s*(\d{1,3})%/.exec(
            element.getAttribute('style') ?? '',
          )?.[1];

          return {
            src,
            alt: img.getAttribute('alt'),
            title: img.getAttribute('title'),
            align: alignFromClass(element),
            width: width ? Number(width) : null,
            credit: cleanBlockText(
              element.querySelector('figcaption')?.getAttribute('data-credit'),
            ),
            source: sourceUrl(element.getAttribute('data-source')),
            href: linkHref(img.closest('a')),
            newTab: img.closest('a')?.getAttribute('target') === '_blank',
          };
        },
      },
      { tag: 'img[src]' },
    ];
  },

  renderHTML({ node }) {
    const { src, alt, title, align, width, credit, srcset, newTab } =
      node.attrs;
    const href = linkHref({
      getAttribute: () => node.attrs.href,
    } as unknown as Element);
    const source = sourceUrl(node.attrs.source);
    const attrs: Record<string, string> = {
      class: `wp-block-image is-align-${pick(ALIGNMENTS, align, 'center')}`,
    };

    if (width) attrs.style = `width: ${Number(width)}%`;
    if (source) attrs['data-source'] = source;

    const image: Record<string, string> = { src };

    if (alt) image.alt = alt;
    if (title) image.title = title;

    if (srcset) {
      image.srcset = srcset;
      image.sizes = EDITOR_IMAGE_SIZES;
    }

    const cleanCredit = cleanBlockText(credit);

    return [
      'figure',
      attrs,
      href
        ? [
            'a',
            {
              href,
              ...(newTab
                ? { target: '_blank', rel: 'noopener noreferrer' }
                : {}),
            },
            ['img', image],
          ]
        : ['img', image],
      ['figcaption', cleanCredit ? { 'data-credit': cleanCredit } : {}, 0],
    ];
  },
});

export const BULLET_STYLES = ['disc', 'circle', 'square', 'dash'] as const;

/**
 * Marker style of bullet lists (`<ul type="square">`). Stored as the HTML `type`
 * attribute; the public CSS maps it to `list-style-type`, so nothing else is needed.
 */
export const BulletListStyle = Extension.create({
  name: 'bulletListStyle',

  addGlobalAttributes() {
    return [
      {
        types: ['bulletList'],
        attributes: {
          type: {
            default: null,
            parseHTML: (element) => {
              const value = element.getAttribute('type');

              return (BULLET_STYLES as readonly string[]).includes(value ?? '')
                ? value
                : null;
            },
            renderHTML: (attributes) =>
              attributes.type ? { type: attributes.type } : {},
          },
        },
      },
    ];
  },
});

export const BLOCK_TEXT_MAX_LENGTH = 60;

/** Trims a short heading-like attribute so it fits the sanitizer's limit. */
export function cleanBlockText(value: unknown): string {
  return typeof value === 'string'
    ? value
        .replace(/[<>"]|\p{Cc}/gu, '')
        .trim()
        .slice(0, BLOCK_TEXT_MAX_LENGTH)
    : '';
}

/**
 * Highlighted note box (`info`, `success`, `warning`, `danger`, `dark`)
 * wrapping regular blocks. An optional title and category label render a
 * header row above the content; without them the markup stays the plain box
 * older content already uses.
 */
export const Callout = Node.create({
  name: 'callout',
  group: 'block',
  content: 'block+',
  defining: true,

  addAttributes() {
    return {
      type: {
        default: 'info',
        rendered: false,
        parseHTML: (element) =>
          pick(CALLOUT_TYPES, element.getAttribute('data-type'), 'info'),
      },
      title: {
        default: '',
        rendered: false,
        parseHTML: (element) =>
          cleanBlockText(
            element.querySelector('.wp-callout-title')?.textContent,
          ),
      },
      label: {
        default: '',
        rendered: false,
        parseHTML: (element) =>
          cleanBlockText(
            element.querySelector('.wp-callout-label')?.textContent,
          ),
      },
    };
  },

  parseHTML() {
    return [
      {
        tag: 'div.wp-block-callout',
        contentElement: (element) =>
          (element as HTMLElement).querySelector<HTMLElement>(
            '.wp-callout-body',
          ) ?? (element as HTMLElement),
      },
    ];
  },

  renderHTML({ node }) {
    const type = pick(CALLOUT_TYPES, node.attrs.type, 'info');
    const title = cleanBlockText(node.attrs.title);
    const label = cleanBlockText(node.attrs.label);
    const attrs = { class: `wp-block-callout is-${type}`, 'data-type': type };

    if (!title && !label) {
      return ['div', attrs, 0];
    }

    return [
      'div',
      attrs,
      [
        'div',
        { class: 'wp-callout-head', contenteditable: 'false' },
        ...(label ? [['span', { class: 'wp-callout-label' }, label]] : []),
        ...(title ? [['strong', { class: 'wp-callout-title' }, title]] : []),
      ],
      ['div', { class: 'wp-callout-body' }, 0],
    ];
  },
});

export const SCRIPTURE_KINDS = ['bible', 'quran', 'hadith'] as const;
export type ScriptureKind = (typeof SCRIPTURE_KINDS)[number];

export const SCRIPTURE_ARABIC_SIZES = ['sm', 'md', 'lg', 'xl'] as const;
export type ScriptureArabicSize = (typeof SCRIPTURE_ARABIC_SIZES)[number];

/** Default type labels (Indonesian, the site's content language). */
export const SCRIPTURE_LABELS: Record<ScriptureKind, string> = {
  bible: 'Alkitab',
  quran: "Al-Qur'an",
  hadith: 'Hadis',
};

const SCRIPTURE_LIMITS = {
  label: 30,
  reference: 80,
  source: 160,
  arabic: 600,
} as const;

/** Trims a scripture header/footer field, dropping characters the sanitizer would reject. */
export function cleanScriptureText(
  value: unknown,
  field: keyof typeof SCRIPTURE_LIMITS,
): string {
  return typeof value === 'string'
    ? value
        .replace(/[<>"]|\p{Cc}/gu, '')
        .trim()
        .slice(0, SCRIPTURE_LIMITS[field])
    : '';
}

function scriptureAttribute(
  field: keyof typeof SCRIPTURE_LIMITS,
  selector: string,
) {
  return {
    default: '',
    rendered: false,
    parseHTML: (element: HTMLElement) =>
      cleanScriptureText(element.querySelector(selector)?.textContent, field),
  };
}

/**
 * Editorial scripture quote (Alkitab, Al-Qur'an, Hadis): a type label and
 * reference above, the quotation as paragraphs, an optional Arabic line for
 * the Qur'an and a source line below. Header, Arabic text and source are
 * attributes edited from the block settings; the quotation is edited in place.
 */
export const ScriptureQuote = Node.create({
  name: 'scriptureQuote',
  group: 'block',
  content: 'paragraph+',
  defining: true,

  addAttributes() {
    return {
      kind: {
        default: 'bible',
        rendered: false,
        parseHTML: (element) =>
          pick(SCRIPTURE_KINDS, element.getAttribute('data-type'), 'bible'),
      },
      label: scriptureAttribute('label', '.wp-scripture-type'),
      reference: scriptureAttribute('reference', '.wp-scripture-reference'),
      arabic: scriptureAttribute('arabic', '.wp-scripture-arabic'),
      arabicSize: {
        default: 'md',
        rendered: false,
        parseHTML: (element) => {
          const classes =
            element.querySelector('.wp-scripture-arabic')?.className ?? '';

          return (
            SCRIPTURE_ARABIC_SIZES.find((size) =>
              classes.split(/\s+/).includes(`is-${size}`),
            ) ?? 'md'
          );
        },
      },
      source: scriptureAttribute('source', '.wp-scripture-source'),
    };
  },

  parseHTML() {
    return [
      {
        tag: 'div.wp-block-scripture',
        contentElement: (element) =>
          (element as HTMLElement).querySelector<HTMLElement>(
            '.wp-scripture-body',
          ) ?? (element as HTMLElement),
      },
    ];
  },

  renderHTML({ node }) {
    const kind = pick(SCRIPTURE_KINDS, node.attrs.kind, 'bible');
    const label = cleanScriptureText(node.attrs.label, 'label');
    const reference = cleanScriptureText(node.attrs.reference, 'reference');
    const arabic = cleanScriptureText(node.attrs.arabic, 'arabic');
    const source = cleanScriptureText(node.attrs.source, 'source');
    const arabicSize = pick(
      SCRIPTURE_ARABIC_SIZES,
      node.attrs.arabicSize,
      'md',
    );

    return [
      'div',
      { class: `wp-block-scripture is-${kind}`, 'data-type': kind },
      ...(label || reference
        ? [
            [
              'div',
              { class: 'wp-scripture-head', contenteditable: 'false' },
              ...(label
                ? [['span', { class: 'wp-scripture-type' }, label]]
                : []),
              ...(reference
                ? [['span', { class: 'wp-scripture-reference' }, reference]]
                : []),
            ],
          ]
        : []),
      ...(arabic
        ? [
            [
              'div',
              {
                class: `wp-scripture-arabic is-${arabicSize}`,
                lang: 'ar',
                dir: 'rtl',
                contenteditable: 'false',
              },
              arabic,
            ],
          ]
        : []),
      ['div', { class: 'wp-scripture-body' }, 0],
      ...(source
        ? [
            [
              'div',
              { class: 'wp-scripture-foot', contenteditable: 'false' },
              ['span', { class: 'wp-scripture-source' }, source],
            ],
          ]
        : []),
    ];
  },
});

/**
 * Link preview card (site, image and title of another page), rendered as a
 * single `<a class="wp-block-linkcard">`.
 */
export const LinkCard = Node.create({
  name: 'linkCard',
  group: 'block',
  atom: true,
  selectable: true,
  draggable: true,

  addAttributes() {
    return {
      url: { default: '', rendered: false },
      title: { default: '', rendered: false },
      image: { default: '', rendered: false },
      siteName: { default: '', rendered: false },
    };
  },

  parseHTML() {
    return [
      {
        tag: 'a.wp-block-linkcard',
        priority: 100,
        getAttrs: (element) => ({
          url: sourceUrl(element.getAttribute('href')),
          title: element.querySelector('.wp-linkcard-title')?.textContent ?? '',
          image: sourceUrl(
            element.querySelector('.wp-linkcard-image')?.getAttribute('src'),
          ),
          siteName:
            element.querySelector('.wp-linkcard-site')?.textContent ?? '',
        }),
      },
    ];
  },

  renderHTML({ node }) {
    const image = sourceUrl(node.attrs.image);

    return [
      'a',
      {
        class: 'wp-block-linkcard',
        href: sourceUrl(node.attrs.url) || '#',
        target: '_blank',
        rel: 'noopener noreferrer',
      },
      ...(image
        ? [
            [
              'img',
              {
                class: 'wp-linkcard-image',
                src: image,
                alt: '',
                loading: 'lazy',
              },
            ],
          ]
        : []),
      [
        'span',
        { class: 'wp-linkcard-meta' },
        ['span', { class: 'wp-linkcard-site' }, String(node.attrs.siteName)],
        ['strong', { class: 'wp-linkcard-title' }, String(node.attrs.title)],
      ],
    ];
  },
});

/** Term card: a word with its pronunciation and a definition made of regular blocks. */
export const Definition = Node.create({
  name: 'definition',
  group: 'block',
  content: 'block+',
  defining: true,

  addAttributes() {
    return {
      term: {
        default: 'Istilah',
        rendered: false,
        parseHTML: (element) =>
          cleanBlockText(
            element.querySelector('.wp-definition-term')?.textContent,
          ) || 'Istilah',
      },
      phonetic: {
        default: '',
        rendered: false,
        parseHTML: (element) =>
          cleanBlockText(
            element.querySelector('.wp-definition-phonetic')?.textContent,
          ),
      },
    };
  },

  parseHTML() {
    return [
      {
        tag: 'div.wp-block-definition',
        contentElement: (element) =>
          (element as HTMLElement).querySelector<HTMLElement>(
            '.wp-definition-body',
          ) ?? (element as HTMLElement),
      },
    ];
  },

  renderHTML({ node }) {
    const phonetic = cleanBlockText(node.attrs.phonetic);

    return [
      'div',
      { class: 'wp-block-definition' },
      [
        'div',
        { class: 'wp-definition-head', contenteditable: 'false' },
        [
          'strong',
          { class: 'wp-definition-term' },
          cleanBlockText(node.attrs.term) || 'Istilah',
        ],
        ...(phonetic
          ? [['span', { class: 'wp-definition-phonetic' }, phonetic]]
          : []),
      ],
      ['div', { class: 'wp-definition-body' }, 0],
    ];
  },
});

/** Cell of a spec sheet row: formatted inline text. */
function specCell(name: 'specLabel' | 'specValue', className: string) {
  return Node.create({
    name,
    content: 'inline*',
    defining: true,
    parseHTML: () => [{ tag: `div.${className}`, priority: 100 }],
    renderHTML: () => ['div', { class: className }, 0],
  });
}

export const SpecLabel = specCell('specLabel', 'wp-spec-label');
export const SpecValue = specCell('specValue', 'wp-spec-value');

export const SpecRow = Node.create({
  name: 'specRow',
  content: 'specLabel specValue',
  defining: true,
  parseHTML: () => [{ tag: 'div.wp-spec-row', priority: 100 }],
  renderHTML: () => ['div', { class: 'wp-spec-row' }, 0],
});

/** Label/value sheet (specifications, key facts) with an optional title. */
export const SpecSheet = Node.create({
  name: 'specSheet',
  group: 'block',
  content: 'specRow+',
  defining: true,
  isolating: true,

  addAttributes() {
    return {
      title: {
        default: '',
        rendered: false,
        parseHTML: (element) =>
          cleanBlockText(element.querySelector('.wp-spec-title')?.textContent),
      },
    };
  },

  parseHTML() {
    return [
      {
        tag: 'div.wp-block-spec',
        contentElement: (element) =>
          (element as HTMLElement).querySelector<HTMLElement>(
            '.wp-spec-rows',
          ) ?? (element as HTMLElement),
      },
    ];
  },

  renderHTML({ node }) {
    const title = cleanBlockText(node.attrs.title);

    return [
      'div',
      { class: 'wp-block-spec' },
      ...(title
        ? [['div', { class: 'wp-spec-title', contenteditable: 'false' }, title]]
        : []),
      ['div', { class: 'wp-spec-rows' }, 0],
    ];
  },

  addKeyboardShortcuts() {
    const { editor } = this;

    return {
      Enter: () => {
        const { $from } = editor.state.selection;
        const name = $from.parent.type.name;

        if (name === 'specLabel') {
          return editor.commands.setTextSelection($from.after() + 2);
        }

        if (name !== 'specValue') return false;

        const rowEnd = $from.after($from.depth - 1);

        return editor
          .chain()
          .insertContentAt(rowEnd, {
            type: 'specRow',
            content: [{ type: 'specLabel' }, { type: 'specValue' }],
          })
          .setTextSelection(rowEnd + 2)
          .run();
      },
    };
  },
});

export const Reference = Node.create({
  name: 'reference',
  content: 'inline*',
  defining: true,
  parseHTML: () => [{ tag: 'p.wp-reference', priority: 100 }],
  renderHTML: () => ['p', { class: 'wp-reference' }, 0],
});

/** Numbered source list; each entry is a formatted line (bold topic, italic title, links). */
export const References = Node.create({
  name: 'references',
  group: 'block',
  content: 'reference+',
  defining: true,
  isolating: true,
  parseHTML: () => [{ tag: 'div.wp-block-references', priority: 100 }],
  renderHTML: () => ['div', { class: 'wp-block-references' }, 0],
});

function mathNode(options: {
  name: 'mathInline' | 'mathBlock';
  tag: string;
  className: string;
  inline: boolean;
}) {
  return Node.create({
    name: options.name,
    group: options.inline ? 'inline' : 'block',
    inline: options.inline,
    atom: true,
    selectable: true,
    draggable: !options.inline,

    addAttributes() {
      return { latex: { default: 'E = mc^2', rendered: false } };
    },

    parseHTML() {
      return [
        {
          tag: `${options.tag}.${options.className}`,
          priority: 100,
          getAttrs: (element) => ({
            latex:
              (element as HTMLElement).getAttribute('data-latex') ??
              element.textContent ??
              '',
          }),
        },
      ];
    },

    renderHTML({ node }) {
      return [
        options.tag,
        { class: options.className },
        String(node.attrs.latex ?? ''),
      ];
    },

    addNodeView() {
      return ({ node }) => {
        const dom = document.createElement(options.tag);

        dom.className = options.className;
        dom.contentEditable = 'false';
        renderMathInto(dom, String(node.attrs.latex ?? ''), !options.inline);

        return {
          dom,
          update: (updated) => {
            if (updated.type !== node.type) return false;

            node = updated;
            renderMathInto(
              dom,
              String(node.attrs.latex ?? ''),
              !options.inline,
            );

            return true;
          },
        };
      };
    },
  });
}

/** LaTeX rendered by KaTeX in the middle of a sentence. */
export const MathInline = mathNode({
  name: 'mathInline',
  tag: 'span',
  className: 'wp-math',
  inline: true,
});

/** Centered display equation. */
export const MathBlock = mathNode({
  name: 'mathBlock',
  tag: 'div',
  className: 'wp-math-block',
  inline: false,
});

/**
 * Placeholder shown after choosing "Tabel": asks for the number of columns and
 * rows, then swaps itself for the table. It is never meant to be saved; it
 * serialises to an empty marker `div` that is dropped again on load.
 */
export const TableSetup = Node.create({
  name: 'tableSetup',
  group: 'block',
  atom: true,
  selectable: true,
  draggable: false,

  renderHTML() {
    return ['div', { class: 'wp-table-setup' }];
  },

  addNodeView() {
    return ({ editor, getPos }) => {
      const dom = document.createElement('div');

      dom.className = 'wp-table-setup';
      dom.contentEditable = 'false';
      dom.innerHTML = `
        <div class="wp-table-setup__title"><strong>Tabel</strong></div>
        <p class="wp-table-setup__hint">Sisipkan tabel untuk membagikan data.</p>
        <div class="wp-table-setup__fields">
          <label>Jumlah kolom<input type="number" name="cols" min="1" max="12" value="2" /></label>
          <label>Jumlah baris<input type="number" name="rows" min="1" max="50" value="2" /></label>
          <button type="button">Buat tabel</button>
        </div>`;

      const read = (name: string, max: number): number => {
        const input = dom.querySelector<HTMLInputElement>(`[name="${name}"]`);

        return Math.min(
          max,
          Math.max(1, Math.round(Number(input?.value) || 1)),
        );
      };

      const create = (): void => {
        const pos = getPos();

        if (typeof pos !== 'number') return;

        editor
          .chain()
          .focus()
          .deleteRange({ from: pos, to: pos + 1 })
          .insertTable({
            cols: read('cols', 12),
            rows: read('rows', 50),
            withHeaderRow: true,
          })
          .run();
      };

      dom.querySelector('button')?.addEventListener('click', create);
      dom.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
          event.preventDefault();
          create();
        }
      });

      return {
        dom,
        stopEvent: () => true,
        ignoreMutation: () => true,
      };
    };
  },
});

/** Tables with a header row; not resizable, so no column widths are stored. */
export const TableBlocks = [
  Table.configure({ resizable: false }),
  TableRow,
  TableHeader,
  TableCell,
];

/** Call-to-action link rendered as a button. Edited from the block bar. */
export const ButtonBlock = Node.create({
  name: 'buttonBlock',
  group: 'block',
  atom: true,
  selectable: true,
  draggable: true,

  addAttributes() {
    return {
      text: { default: 'Klik di sini', rendered: false },
      href: { default: '#', rendered: false },
      variant: { default: 'solid', rendered: false },
      align: { default: 'left', rendered: false },
    };
  },

  parseHTML() {
    return [
      {
        tag: 'div.wp-block-button',
        getAttrs: (element) => {
          const link = element.querySelector('a');

          return {
            text: link?.textContent ?? 'Klik di sini',
            href: safeHref(link?.getAttribute('href')),
            variant: element.classList.contains('is-outline')
              ? 'outline'
              : 'solid',
            align: alignFromClass(element),
          };
        },
      },
    ];
  },

  renderHTML({ node }) {
    const variant = pick(BUTTON_VARIANTS, node.attrs.variant, 'solid');
    const align = pick(ALIGNMENTS, node.attrs.align, 'left');

    return [
      'div',
      { class: `wp-block-button is-${variant} is-align-${align}` },
      [
        'a',
        {
          class: 'wp-block-button__link',
          href: safeHref(node.attrs.href),
          rel: 'noopener',
        },
        node.attrs.text || 'Klik di sini',
      ],
    ];
  },
});

/**
 * Appends the offsets of every scoped span in a highlighted fragment as inline
 * decorations, so code blocks are coloured live without changing the document.
 */
function collectDecorations(
  node: globalThis.Node,
  base: number,
  offset: number,
  into: Decoration[],
): number {
  let cursor = offset;

  node.childNodes.forEach((child) => {
    if (child.nodeType === globalThis.Node.TEXT_NODE) {
      cursor += (child as Text).data.length;

      return;
    }

    const start = cursor;

    cursor = collectDecorations(child, base, cursor, into);

    if (cursor > start) {
      into.push(
        Decoration.inline(base + start, base + cursor, {
          class: (child as Element).className,
        }),
      );
    }
  });

  return cursor;
}

function buildCodeDecorations(doc: ProseNode): DecorationSet {
  const decorations: Decoration[] = [];

  doc.descendants((node, pos) => {
    if (node.type.name !== 'codeBlock') return;

    const result = highlightSource(node.textContent, node.attrs.language);

    if (result) {
      collectDecorations(result.fragment, pos + 1, 0, decorations);
    }

    return false;
  });

  return DecorationSet.create(doc, decorations);
}

/** Colour schemes for code blocks (ported highlight.js themes); empty follows the site theme. */
export const CODE_THEMES = [
  { value: '', label: 'Otomatis (GitHub)' },
  { value: 'atom-one-dark', label: 'Atom One Dark' },
  { value: 'atom-one-light', label: 'Atom One Light' },
  { value: 'monokai', label: 'Monokai' },
  { value: 'nord', label: 'Nord' },
  { value: 'dracula', label: 'Dracula' },
  { value: 'solarized-light', label: 'Solarized Light' },
  { value: 'solarized-dark', label: 'Solarized Dark' },
  { value: 'tokyo-night-dark', label: 'Tokyo Night' },
  { value: 'night-owl', label: 'Night Owl' },
  { value: 'vs2015', label: 'Visual Studio 2015' },
] as const;

function validCodeTheme(value: unknown): string | null {
  return CODE_THEMES.some((theme) => theme.value && theme.value === value)
    ? (value as string)
    : null;
}

/**
 * Editor node view: a titled card whose tab holds an editable title and whose
 * header carries the copy button. The saved HTML stays a plain `pre > code`.
 */
function codeBlockView(
  initial: ProseNode,
  view: EditorView,
  getPos: () => number | undefined,
): NodeView {
  let node = initial;
  const card = document.createElement('div');
  const pre = document.createElement('pre');
  const code = document.createElement('code');
  const input = document.createElement('input');

  card.className = 'code-card';
  input.type = 'text';
  input.className = 'code-card__title code-card__title-input';
  input.placeholder = t('code.title_placeholder');
  input.maxLength = CODE_TITLE_MAX_LENGTH;
  input.setAttribute('aria-label', t('code.title_label'));
  input.spellcheck = false;

  const commitTitle = (): void => {
    const pos = getPos();
    const title = normalizeCodeTitle(input.value);

    if (pos === undefined || title === (node.attrs.title ?? null)) {
      return;
    }

    view.dispatch(
      view.state.tr.setNodeMarkup(pos, undefined, { ...node.attrs, title }),
    );
  };

  input.addEventListener('change', commitTitle);
  input.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      commitTitle();
      view.focus();
    }
  });

  let gutter: HTMLElement | null = null;

  const sync = (): void => {
    card.classList.toggle('code-card--untitled', !!node.attrs.titleHidden);

    if (node.attrs.lineNumbers) {
      if (!gutter) {
        gutter = createGutter(node.textContent);
        pre.prepend(gutter);
      }

      const numbers = lineNumbersText(node.textContent);

      if (gutter.textContent !== numbers) {
        gutter.textContent = numbers;
      }
    } else if (gutter) {
      gutter.remove();
      gutter = null;
    }

    if (node.attrs.titleHidden) {
      pre.dataset.codeTitleHidden = '1';
    } else {
      delete pre.dataset.codeTitleHidden;
    }

    if (node.attrs.lineNumbers) {
      pre.dataset.codeLines = '1';
    } else {
      delete pre.dataset.codeLines;
    }

    if (document.activeElement !== input) {
      input.value = node.attrs.title ?? '';
    }

    const theme = validCodeTheme(node.attrs.theme);

    if (theme) {
      pre.dataset.codeTheme = theme;
    } else {
      delete pre.dataset.codeTheme;
    }

    if (node.attrs.title) {
      pre.dataset.codeTitle = node.attrs.title;
    } else {
      delete pre.dataset.codeTitle;
    }
  };

  pre.append(code);
  card.append(
    createCodeHeader(input, () => node.textContent),
    pre,
  );
  sync();

  return {
    dom: card,
    contentDOM: code,
    update(updated) {
      if (updated.type !== node.type) {
        return false;
      }

      node = updated;
      sync();

      return true;
    },
    stopEvent: (event) =>
      event.target instanceof HTMLElement &&
      event.target.closest('.code-card__header') !== null,
    ignoreMutation: (mutation) => {
      const target =
        mutation.target instanceof HTMLElement
          ? mutation.target
          : mutation.target.parentElement;

      return (
        target?.closest('.code-card__header, .code-card__gutter') != null ||
        (target === pre && mutation.type === 'childList')
      );
    },
  };
}

/** Live syntax colouring for code blocks inside the editor. */
export const CodeBlockHighlight = Extension.create({
  name: 'codeBlockHighlight',

  addGlobalAttributes() {
    return [
      {
        types: ['codeBlock'],
        attributes: {
          title: {
            default: null,
            parseHTML: (element) =>
              normalizeCodeTitle(element.getAttribute('data-code-title')),
            renderHTML: (attributes) => {
              const title = normalizeCodeTitle(attributes.title);

              return title ? { 'data-code-title': title } : {};
            },
          },
          titleHidden: {
            default: false,
            parseHTML: (element) =>
              element.getAttribute('data-code-title-hidden') === '1',
            renderHTML: (attributes) =>
              attributes.titleHidden ? { 'data-code-title-hidden': '1' } : {},
          },
          lineNumbers: {
            default: false,
            parseHTML: (element) =>
              element.getAttribute('data-code-lines') === '1',
            renderHTML: (attributes) =>
              attributes.lineNumbers ? { 'data-code-lines': '1' } : {},
          },
          theme: {
            default: null,
            parseHTML: (element) =>
              validCodeTheme(element.getAttribute('data-code-theme')),
            renderHTML: (attributes) => {
              const theme = validCodeTheme(attributes.theme);

              return theme ? { 'data-code-theme': theme } : {};
            },
          },
        },
      },
    ];
  },

  addProseMirrorPlugins() {
    const key = new PluginKey<DecorationSet>('codeBlockHighlight');

    return [
      new Plugin<DecorationSet>({
        key,
        state: {
          init: (_config, state) => buildCodeDecorations(state.doc),
          apply: (tr, previous) =>
            tr.docChanged ? buildCodeDecorations(tr.doc) : previous,
        },
        props: {
          decorations: (state) => key.getState(state),
          nodeViews: {
            codeBlock: (initial, view, getPos) =>
              codeBlockView(initial, view, getPos),
          },
        },
      }),
    ];
  },
});

/** Transaction meta that sets (or with `null` clears) the highlighted link target range. */
export const LINK_HIGHLIGHT_META = 'rteLinkHighlight';

/**
 * Keeps the text being linked visibly selected while the link popover owns the
 * keyboard focus, so the writer can see what the link will apply to.
 */
export const LinkHighlight = Extension.create({
  name: 'linkHighlight',

  addProseMirrorPlugins() {
    const key = new PluginKey<{ from: number; to: number } | null>(
      'linkHighlight',
    );

    return [
      new Plugin<{ from: number; to: number } | null>({
        key,
        state: {
          init: () => null,
          apply: (tr, previous) => {
            const meta = tr.getMeta(LINK_HIGHLIGHT_META);

            if (meta !== undefined) {
              return meta;
            }

            return previous && tr.docChanged
              ? {
                  from: tr.mapping.map(previous.from),
                  to: tr.mapping.map(previous.to),
                }
              : previous;
          },
        },
        props: {
          decorations: (state) => {
            const range = key.getState(state);

            return range && range.to > range.from
              ? DecorationSet.create(state.doc, [
                  Decoration.inline(range.from, range.to, {
                    class: 'rte-link-target',
                  }),
                ])
              : null;
          },
        },
      }),
    ];
  },
});
