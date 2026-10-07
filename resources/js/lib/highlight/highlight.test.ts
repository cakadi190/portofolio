import { describe, expect, it } from 'vitest';
import {
  highlightAll,
  highlightElement,
  highlightSource,
  languageNames,
} from '@/lib/highlight';
import { highlightCode } from '@/lib/highlight/action';
import {
  getLanguage,
  highlight,
  highlightAuto,
  scopeClasses,
} from '@/lib/highlight/engine';

function block(source: string, className = '') {
  const pre = document.createElement('pre');
  const code = document.createElement('code');
  code.className = className;
  code.textContent = source;
  pre.appendChild(code);
  document.body.appendChild(pre);

  return code;
}

describe('engine', () => {
  it('registers built-in languages', () => {
    expect(languageNames().length).toBeGreaterThan(3);
    expect(getLanguage('php')).toBeDefined();
  });

  it('returns plaintext for unknown languages', () => {
    const result = highlight('x = 1', 'nope');

    expect(result.language).toBe('plaintext');
    expect(result.fragment.textContent).toBe('x = 1');
  });

  it('preserves source text when highlighting', () => {
    const source = '<?php echo "hi"; // note';
    const result = highlight(source, 'php');

    expect(result.fragment.textContent).toBe(source);
    expect(result.fragment.querySelector('[class^="hljs-"]')).not.toBeNull();
  });

  it('auto-detects only above the threshold', () => {
    expect(highlightAuto('just words here', undefined, 1000)).toBeNull();
  });

  it('prefixes scope classes', () => {
    expect(scopeClasses('string')[0]).toMatch(/hljs-/);
  });
});

describe('highlightSource', () => {
  it('returns null for plain text / unknown languages', () => {
    expect(highlightSource('abc', 'nope')).toBeNull();
  });

  it('highlights a declared language', () => {
    expect(highlightSource('const a = 1;', 'javascript')?.language).toBe(
      'javascript',
    );
  });
});

describe('highlightElement', () => {
  it('highlights using the declared language class', () => {
    const code = block('const a = "x";', 'language-javascript');

    highlightElement(code);

    expect(code.classList).toContain('hljs');
    expect(code.dataset.highlighted).toBe('yes');
    expect(code.textContent).toBe('const a = "x";');
  });

  it('reads the language from the parent pre', () => {
    const code = block('const a = 1;');
    code.parentElement?.setAttribute('data-language', 'javascript');

    highlightElement(code);

    expect(code.classList).toContain('language-javascript');
  });

  it('skips plaintext blocks', () => {
    const code = block('hello', 'language-text');

    highlightElement(code);

    expect(code.dataset.highlighted).toBeUndefined();
  });

  it('is idempotent', () => {
    const code = block('const a = 1;', 'language-javascript');

    highlightElement(code);
    const html = code.innerHTML;
    highlightElement(code);

    expect(code.innerHTML).toBe(html);
  });
});

describe('highlightAll / highlightCode', () => {
  it('highlights every block under a root', () => {
    document.body.innerHTML = '';
    const a = block('const a = 1;', 'language-javascript');
    const b = block('let b = 2;', 'language-javascript');

    highlightAll();

    expect(a.dataset.highlighted).toBe('yes');
    expect(b.dataset.highlighted).toBe('yes');
  });

  it('highlights on mount and on update', () => {
    const root = document.createElement('div');
    const first = document.createElement('pre');
    first.innerHTML = '<code class="language-javascript">let a = 1;</code>';
    root.appendChild(first);

    const action = highlightCode(root, 'html');
    expect(first.querySelector('code')?.dataset.highlighted).toBe('yes');

    const second = document.createElement('pre');
    second.innerHTML = '<code class="language-javascript">let b = 1;</code>';
    root.appendChild(second);
    action?.update?.('html2');

    expect(second.querySelector('code')?.dataset.highlighted).toBe('yes');
  });
});
