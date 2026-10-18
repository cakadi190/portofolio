import type { Editor, JSONContent } from '@tiptap/core';
import { Fragment, Slice } from '@tiptap/pm/model';

/**
 * Markdown on the clipboard.
 *
 * Copy: a selection that only holds nodes Markdown can express is also offered as
 * Markdown in the `text/plain` flavour (the HTML flavour is untouched, so rich editors
 * still receive HTML). Paste: plain text that reads as Markdown becomes real blocks.
 */

/** Nodes the Markdown serializer can write without losing anything. */
const MARKDOWN_NODES = new Set([
  'doc',
  'text',
  'paragraph',
  'heading',
  'bulletList',
  'orderedList',
  'listItem',
  'blockquote',
  'codeBlock',
  'horizontalRule',
  'hardBreak',
  'table',
  'tableRow',
  'tableHeader',
  'tableCell',
]);

const BLOCK_PATTERNS = [
  /^ {0,3}#{1,6}\s+\S/m,
  /^ {0,3}(?:[-*+]|\d{1,9}[.)])\s+\S/m,
  /^ {0,3}>\s?\S/m,
  /^ {0,3}(?:```|~~~)/m,
  /^ {0,3}\|?\s*:?-{3,}:?\s*(?:\|\s*:?-{3,}:?\s*)+\|?\s*$/m,
  /^ {0,3}(?:-{3,}|\*{3,}|_{3,})\s*$/m,
];

const INLINE_PATTERNS = [
  /\*\*[^*\n]+\*\*/,
  /(?<![\w*])__[^_\n]+__(?![\w*])/,
  /(?<![\w*])\*[^*\s][^*\n]*\*(?![\w*])/,
  /~~[^~\n]+~~/,
  /`[^`\n]+`/,
  /!?\[[^\]\n]+\]\([^)\s]+\)/,
];

/** Tags a web page or office suite uses for structure; VS Code and terminals paste none of these. */
const SEMANTIC_HTML =
  /<(?:h[1-6]|ul|ol|li|table|blockquote|pre|strong|b|em|i|a|img)[\s>]/i;

/**
 * Whether plain text is probably Markdown: one block-level construct (heading, list,
 * quote, fence, table, rule), or two different inline ones, so a lone `*` or a
 * sentence with a single link stays plain text.
 */
export function looksLikeMarkdown(text: string): boolean {
  if (text.trim() === '') {
    return false;
  }

  if (BLOCK_PATTERNS.some((pattern) => pattern.test(text))) {
    return true;
  }

  return INLINE_PATTERNS.filter((pattern) => pattern.test(text)).length >= 2;
}

/**
 * Whether clipboard HTML already carries structure worth keeping over the plain text:
 * pages, Word and editors of this app (`data-pm-slice`) do, code editors do not.
 */
export function hasSemanticHtml(html: string): boolean {
  return html.includes('data-pm-slice') || SEMANTIC_HTML.test(html);
}

/** Whether every node of a slice has a lossless Markdown form. */
export function isMarkdownSafe(slice: Slice): boolean {
  let safe = true;

  slice.content.descendants((node) => {
    if (!MARKDOWN_NODES.has(node.type.name)) {
      safe = false;
    }

    return safe;
  });

  return safe;
}

/** The slice as Markdown, or null when it holds a block Markdown cannot express. */
export function sliceToMarkdown(editor: Editor, slice: Slice): string | null {
  if (!editor.markdown || slice.content.size === 0 || !isMarkdownSafe(slice)) {
    return null;
  }

  try {
    const markdown = editor.markdown
      .serialize({
        type: 'doc',
        content: slice.content.toJSON() as JSONContent[],
      })
      .trim();

    return markdown === '' ? null : markdown;
  } catch {
    return null;
  }
}

/**
 * Markdown puts images inside paragraphs; this editor's image is a block, so lift each
 * one out and keep the text runs around it as their own paragraphs.
 */
function liftImages(nodes: JSONContent[]): JSONContent[] {
  return nodes.flatMap((node): JSONContent[] => {
    if (
      node.type === 'paragraph' &&
      node.content?.some((child) => child.type === 'image')
    ) {
      const blocks: JSONContent[] = [];
      let run: JSONContent[] = [];

      for (const child of node.content) {
        if (child.type === 'image') {
          if (run.length > 0) {
            blocks.push({ type: 'paragraph', content: run });
            run = [];
          }

          blocks.push(child);
        } else {
          run.push(child);
        }
      }

      if (run.length > 0) {
        blocks.push({ type: 'paragraph', content: run });
      }

      return blocks;
    }

    return node.content && node.type !== 'paragraph'
      ? [{ ...node, content: liftImages(node.content) }]
      : [node];
  });
}

/** Markdown text as a slice of this editor's schema, or null when it is not Markdown or does not fit. */
export function markdownSlice(editor: Editor, text: string): Slice | null {
  if (!editor.markdown || !looksLikeMarkdown(text)) {
    return null;
  }

  try {
    const parsed = editor.markdown.parse(text);
    const doc = editor.schema.nodeFromJSON({
      ...parsed,
      content: liftImages(parsed.content ?? []),
    });

    doc.check();

    return new Slice(Fragment.from(doc.content), 0, 0);
  } catch {
    return null;
  }
}
