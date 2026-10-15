import { describe, expect, it } from 'vitest';
import { ease, overlayIn, overlayOut } from '@/lib/overlay-motion';

const node = document.createElement('div');

describe('overlay motion', () => {
  it('eases like CSS `ease`', () => {
    expect(ease(0)).toBe(0);
    expect(ease(1)).toBe(1);
    // cubic-bezier(.25,.1,.25,1) is already ~80% done at the halfway mark.
    expect(ease(0.5)).toBeGreaterThan(0.75);
    expect(ease(0.5)).toBeLessThan(0.85);
  });

  it('slides in from the trigger side while zooming from 95%', () => {
    const config = overlayIn(node, { side: 'bottom' });

    expect(config.duration).toBe(150);
    expect(config.css?.(0, 1)).toContain('opacity: 0');
    expect(config.css?.(0, 1)).toContain('scale: 0.95');
    expect(config.css?.(0, 1)).toContain('translate: 0rem -0.5rem');
    expect(config.css?.(1, 0)).toContain('scale: 1');
    expect(config.css?.(1, 0)).toContain('translate: 0rem 0rem');
  });

  it.each([
    ['top', 'translate: 0rem 0.5rem'],
    ['right', 'translate: -0.5rem 0rem'],
    ['left', 'translate: 0.5rem 0rem'],
  ] as const)('slides in for a %s popup', (side, expected) => {
    expect(overlayIn(node, { side }).css?.(0, 1)).toContain(expected);
  });

  it('only fades and zooms out, with no slide', () => {
    const css = overlayOut(node).css?.(0, 1) ?? '';

    expect(css).toContain('opacity: 0');
    expect(css).toContain('scale: 0.95');
    expect(css).not.toContain('translate');
  });
});
