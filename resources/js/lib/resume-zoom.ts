export type ZoomMode = 'fit-width' | 'fit-page' | 'custom';

export const MIN_ZOOM = 0.5;
export const MAX_ZOOM = 3;
export const ZOOM_STEPS = [0.5, 0.75, 1, 1.25, 1.5, 2, 3];

const round = (value: number): number => Math.round(value * 100) / 100;

export const clampZoom = (value: number): number =>
  round(Math.min(Math.max(value, MIN_ZOOM), MAX_ZOOM));

/** Next preset zoom in the given direction; stays put at the limits. */
export const stepZoom = (current: number, direction: 1 | -1): number => {
  const next =
    direction > 0
      ? ZOOM_STEPS.find((step) => step > current + 0.001)
      : [...ZOOM_STEPS].reverse().find((step) => step < current - 0.001);

  return clampZoom(next ?? current);
};

/** Scale at which a page of `pageWidth` fills the container width minus `gutter` (total, both sides). */
export const fitWidthZoom = (
  containerWidth: number,
  pageWidth: number,
  gutter = 24,
): number => clampZoom((containerWidth - gutter) / pageWidth);

/** Scale at which the whole page fits inside the container, capped so desktop does not over-enlarge. */
export const fitPageZoom = (
  containerWidth: number,
  containerHeight: number,
  pageWidth: number,
  pageHeight: number,
  gutter = 24,
  cap = 1.3,
): number =>
  clampZoom(
    Math.min(
      (containerWidth - gutter) / pageWidth,
      (containerHeight - gutter) / pageHeight,
      cap,
    ),
  );

/** Zoom after a two-finger gesture, relative to where it started. */
export const pinchZoom = (
  startZoom: number,
  startDistance: number,
  distance: number,
): number =>
  startDistance > 0
    ? clampZoom((startZoom * distance) / startDistance)
    : startZoom;

/**
 * Scroll offset that keeps the content under `anchor` (a viewport coordinate) in place after
 * scaling by `ratio`.
 */
export const anchoredScroll = (
  scroll: number,
  anchor: number,
  ratio: number,
): number => Math.max(0, (scroll + anchor) * ratio - anchor);

/** Largest bitmap (in pixels) rendered for one page; iOS Safari's canvas limit is 16.7 MP. */
export const MAX_CANVAS_PIXELS = 16_000_000;

/**
 * Render scale for a page canvas: the wanted `zoom x pixel ratio`, lowered so the bitmap stays within
 * `maxPixels` (zoom 3x on a 3x screen would otherwise be ~40 MP, which mobile browsers refuse to allocate).
 * CSS scales the canvas to the sheet size, so a capped bitmap only costs sharpness.
 */
export const renderScale = (
  zoom: number,
  pixelRatio: number,
  pageWidth: number,
  pageHeight: number,
  maxPixels = MAX_CANVAS_PIXELS,
): number =>
  Math.min(zoom * pixelRatio, Math.sqrt(maxPixels / (pageWidth * pageHeight)));

export const distanceBetween = (
  a: { x: number; y: number },
  b: { x: number; y: number },
): number => Math.hypot(a.x - b.x, a.y - b.y);
