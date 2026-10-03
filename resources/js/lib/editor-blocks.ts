import { Extension, Node } from '@tiptap/core';
import Image from '@tiptap/extension-image';
import type { Node as ProseNode } from '@tiptap/pm/model';
import { Plugin, PluginKey } from '@tiptap/pm/state';
import { Decoration, DecorationSet } from '@tiptap/pm/view';
import { highlightSource } from '@/lib/highlight';

export const ALIGNMENTS = ['left', 'center', 'right'] as const;
export type BlockAlign = (typeof ALIGNMENTS)[number];

export const CALLOUT_TYPES = ['info', 'success', 'warning', 'danger'] as const;
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

function alignFromClass(element: Element): BlockAlign {
  return (
    ALIGNMENTS.find((align) =>
      element.classList.contains(`is-align-${align}`),
    ) ?? 'center'
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
      caption: { default: '', rendered: false },
    };
  },

  parseHTML() {
    return [
      {
        tag: 'figure.wp-block-image',
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
            caption: element.querySelector('figcaption')?.textContent ?? '',
          };
        },
      },
      { tag: 'img[src]' },
    ];
  },

  renderHTML({ node }) {
    const { src, alt, title, align, width, caption } = node.attrs;
    const attrs: Record<string, string> = {
      class: `wp-block-image is-align-${pick(ALIGNMENTS, align, 'center')}`,
    };

    if (width) attrs.style = `width: ${Number(width)}%`;

    const image: Record<string, string> = { src };

    if (alt) image.alt = alt;
    if (title) image.title = title;

    return caption
      ? ['figure', attrs, ['img', image], ['figcaption', caption]]
      : ['figure', attrs, ['img', image]];
  },
});

/** Highlighted note box (`info`, `success`, `warning`, `danger`) wrapping regular blocks. */
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
    };
  },

  parseHTML() {
    return [{ tag: 'div.wp-block-callout' }];
  },

  renderHTML({ node }) {
    const type = pick(CALLOUT_TYPES, node.attrs.type, 'info');

    return [
      'div',
      { class: `wp-block-callout is-${type}`, 'data-type': type },
      0,
    ];
  },
});

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

/** Live syntax colouring for code blocks inside the editor. */
export const CodeBlockHighlight = Extension.create({
  name: 'codeBlockHighlight',

  addGlobalAttributes() {
    return [
      {
        types: ['codeBlock'],
        attributes: {
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
        },
      }),
    ];
  },
});
