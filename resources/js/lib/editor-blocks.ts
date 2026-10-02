import { Node } from '@tiptap/core';
import Image from '@tiptap/extension-image';

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
  const value = String(href ?? '').trim();

  return /^(https?:\/\/|mailto:|tel:|\/|#)/i.test(value) ? value : '#';
}

function alignFromClass(element: Element): BlockAlign {
  return (
    ALIGNMENTS.find((align) => element.classList.contains(`is-align-${align}`)) ??
    'center'
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
