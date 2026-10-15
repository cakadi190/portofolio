import { describe, expect, it } from 'vitest';
import {
  anchoredScroll,
  clampZoom,
  distanceBetween,
  fitPageZoom,
  fitWidthZoom,
  MAX_ZOOM,
  MAX_CANVAS_PIXELS,
  MIN_ZOOM,
  pinchFrame,
  pinchStart,
  pinchZoom,
  renderScale,
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

describe('renderScale', () => {
  const a4 = { width: 595, height: 842 };

  it('keeps the wanted scale while the bitmap stays within the pixel budget', () => {
    expect(renderScale(1, 2, a4.width, a4.height)).toBe(2);
  });

  it('caps the bitmap of a 3x zoom on a 3x screen to the pixel budget', () => {
    const scale = renderScale(MAX_ZOOM, 3, a4.width, a4.height);

    expect(scale).toBeLessThan(MAX_ZOOM * 3);
    expect(a4.width * scale * (a4.height * scale)).toBeLessThanOrEqual(
      MAX_CANVAS_PIXELS * 1.001,
    );
  });

  it('honours a custom budget', () => {
    expect(renderScale(3, 3, 100, 100, 10_000)).toBe(1);
  });
});

describe('pinch gesture', () => {
  const start = pinchStart(1, 100, { left: 40, top: 300 }, { x: 200, y: 400 });

  it('captures the document point under the initial midpoint', () => {
    expect(start).toMatchObject({
      zoom: 1,
      distance: 100,
      contentX: 240,
      contentY: 700,
    });
  });

  it('keeps that point under the midpoint while the fingers spread', () => {
    const frame = pinchFrame(start, { distance: 200, x: 200, y: 400 });

    expect(frame.zoom).toBe(2);
    // Document point 240 now sits at 240 * 2 = 480; the offset puts it back at the 200px midpoint.
    expect(frame.scrollLeft + 200).toBe(480);
    expect(frame.scrollTop + 400).toBe(1400);
  });

  it('pans with the midpoint when both fingers move together', () => {
    const still = pinchFrame(start, { distance: 100, x: 200, y: 400 });
    const moved = pinchFrame(start, { distance: 100, x: 150, y: 380 });

    expect(still.zoom).toBe(1);
    expect(moved.scrollLeft - still.scrollLeft).toBe(50);
    expect(moved.scrollTop - still.scrollTop).toBe(20);
  });

  it('clamps the zoom to the supported range', () => {
    expect(pinchFrame(start, { distance: 5000, x: 200, y: 400 }).zoom).toBe(
      MAX_ZOOM,
    );
    expect(pinchFrame(start, { distance: 1, x: 200, y: 400 }).zoom).toBe(
      MIN_ZOOM,
    );
  });
});
