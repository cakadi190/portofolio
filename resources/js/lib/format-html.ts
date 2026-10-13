const BLOCK_TAGS = new Set([
  'article',
  'aside',
  'blockquote',
  'div',
  'figcaption',
  'figure',
  'h1',
  'h2',
  'h3',
  'h4',
  'h5',
  'h6',
  'li',
  'ol',
  'p',
  'section',
  'table',
  'tbody',
  'td',
  'tfoot',
  'th',
  'thead',
  'tr',
  'ul',
]);
const VOID_BLOCK_TAGS = new Set(['hr']);
const TOKEN = /<!--[\s\S]*?-->|<pre[\s\S]*?<\/pre\s*>|<[^>]+>|[^<]+/gi;

/**
 * Re-indents editor HTML: block elements get their own indented lines while
 * inline content stays on one line, and `<pre>` blocks are left untouched.
 */
export function formatHtml(source: string, indent = '  '): string {
  const lines: string[] = [];
  const openLineCounts: number[] = [];
  let line = '';
  let lineDepth = 0;
  let depth = 0;

  const flush = (): void => {
    if (line.trim() !== '') {
      lines.push(indent.repeat(lineDepth) + line.trim());
    }

    line = '';
  };
  const append = (text: string): void => {
    if (line === '') {
      lineDepth = depth;
    }

    line += text;
  };
  const pushRaw = (text: string): void => {
    flush();
    lineDepth = depth;
    line = text;
    flush();
  };

  for (const token of source.match(TOKEN) ?? []) {
    if (!token.startsWith('<') || token.startsWith('<!--')) {
      if (token.startsWith('<!--')) {
        pushRaw(token);
      } else {
        const text = token.replace(/\s+/g, ' ');

        if (text.trim() !== '' || line !== '') {
          append(text);
        }
      }

      continue;
    }

    if (/^<pre[\s>]/i.test(token)) {
      pushRaw(token);

      continue;
    }

    const match = /^<(\/?)([a-z][a-z0-9-]*)/i.exec(token);
    const tag = match?.[2].toLowerCase() ?? '';
    const isClosing = match?.[1] === '/';

    if (VOID_BLOCK_TAGS.has(tag)) {
      pushRaw(token);
    } else if (!BLOCK_TAGS.has(tag)) {
      append(token);
    } else if (!isClosing) {
      flush();
      lineDepth = depth;
      line = token;
      openLineCounts.push(lines.length);
      depth++;
    } else {
      const openedAt = openLineCounts.pop();

      if (openedAt === lines.length) {
        line += token;
        depth--;
        flush();
      } else {
        flush();
        depth = Math.max(0, depth - 1);
        lineDepth = depth;
        line = token;
        flush();
      }
    }
  }

  flush();

  return lines.join('\n');
}
