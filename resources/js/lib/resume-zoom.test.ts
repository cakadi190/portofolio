import { describe, expect, it } from 'vitest';
import {
  anchoredScroll,
  clampZoom,
  distanceBetween,
  fitPageZoom,
  fitWidthZoom,
  MAX_ZOOM,
  MIN_ZOOM,
  pinchZoom,
  stepZoom,
} from './resume-zoom';

describe('resume zoom', () => {
  it('clamps to the configured limits', () => {
    expect(clampZoom(0.1)).toBe(MIN_ZOOM);
    expect(clampZoom(9)).toBe(MAX_ZOOM);
    expect(clampZoom(1.234)).toBe(1.23);
  });

  it('steps through presets and stops at the limits', () => {
    expect(stepZoom(1, 1)).toBe(1.25);
    expect(stepZoom(1, -1)).toBe(0.75);
    expect(stepZoom(1.3, 1)).toBe(1.5);
    expect(stepZoom(1.3, -1)).toBe(1.25);
    expect(stepZoom(MAX_ZOOM, 1)).toBe(MAX_ZOOM);
    expect(stepZoom(MIN_ZOOM, -1)).toBe(MIN_ZOOM);
  });

  it('computes fit width from the container and page widths', () => {
    expect(fitWidthZoom(375, 595)).toBeCloseTo(0.59, 2);
    expect(fitWidthZoom(100, 595)).toBe(MIN_ZOOM);
  });

  it('fits the page by the tighter dimension and caps it', () => {
    expect(fitPageZoom(1200, 500, 595, 842)).toBeCloseTo(0.57, 2);
    expect(fitPageZoom(2000, 2000, 595, 842)).toBe(1.3);
  });

  it('scales pinch gestures relative to the start distance', () => {
    expect(pinchZoom(1, 100, 150)).toBe(1.5);
    expect(pinchZoom(1, 100, 10)).toBe(MIN_ZOOM);
    expect(pinchZoom(1, 0, 150)).toBe(1);
  });

  it('keeps the anchored content in place when scaling', () => {
    expect(anchoredScroll(100, 200, 2)).toBe(400);
    expect(anchoredScroll(0, 0, 0.5)).toBe(0);
    expect(anchoredScroll(10, 300, 0.5)).toBe(0);
  });

  it('measures the distance between two touch points', () => {
    expect(distanceBetween({ x: 0, y: 0 }, { x: 3, y: 4 })).toBe(5);
  });
});
