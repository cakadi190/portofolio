import { describe, expect, it } from 'vitest';
import { obscureMathCss } from '../../../vite-obscure-math';
import { obscureMathClass, obscureMathSelector } from '@/lib/math-classes';
import { obscureRendered } from '@/lib/math';

describe('equation fingerprint removal', () => {
  it('renames classes without leaving the library name behind', () => {
    expect(obscureMathClass('katex')).toBe('mx');
    expect(obscureMathClass('katex-display')).toBe('mx-display');
    expect(obscureMathClass('mord')).toBe('mx-mord');
    expect(obscureMathSelector('.katex .mord>.vlist-t:not(.x)')).toBe(
      '.mx .mx-mord>.mx-vlist-t:not(.mx-x)',
    );
  });

  it('rewrites selectors and font families but keeps font urls resolvable', () => {
    const css = obscureMathCss(
      '@font-face{font-family:KaTeX_Main;src:url(fonts/KaTeX_Main-Regular.woff2) format("woff2")}.katex{font:normal 1.21em KaTeX_Main,serif}.katex .mathbf{font-family:KaTeX_Main}.katex .eqn-num:before{counter-increment:katexEqnNo;content:"(" counter(katexEqnNo) ")"}',
    );

    expect(css).toContain('font-family:MX_Main');
    expect(css).toContain('.mx .mx-mathbf');
    expect(css).toContain('url(fonts/KaTeX_Main-Regular.woff2)');
    expect(css).not.toMatch(/\.katex/);
    expect(css).not.toContain('katexEqnNo');
    expect(css).toContain('font:normal 1.21em MX_Main,serif');
  });

  it('renames rendered DOM classes', () => {
    const root = document.createElement('span');

    root.innerHTML =
      '<span class="katex"><span class="katex-mathml">x</span><span class="base strut">y</span></span>';
    obscureRendered(root);

    expect(root.innerHTML).not.toContain('katex');
    expect(root.querySelector('.mx .mx-mathml')).not.toBeNull();
    expect(root.querySelector('.mx-base.mx-strut')).not.toBeNull();
  });
});
