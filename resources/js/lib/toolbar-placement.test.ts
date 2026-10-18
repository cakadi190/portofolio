import { describe, expect, it } from 'vitest';
import {
  placeBlockToolbar,
  type ToolbarPlacementInput,
} from '@/lib/toolbar-placement';

const base: ToolbarPlacementInput = {
  rect: { top: 400, bottom: 460, left: 300, height: 60 },
  previous: null,
  next: null,
  toolbar: { width: 360, height: 46 },
  viewport: { width: 1400, height: 900 },
  floor: 74,
  footer: 44,
  isMedia: false,
};

describe('placeBlockToolbar', () => {
  it('goes above the block when the gap to the previous block is tall enough', () => {
    const place = placeBlockToolbar({
      ...base,
      previous: { top: 100, bottom: 300, left: 300, height: 200 },
    });

    expect(place).toEqual({ top: 400 - 46 - 6, left: 300 });
  });

  it('goes below a text block when the previous block sits right above it', () => {
    const place = placeBlockToolbar({
      ...base,
      previous: { top: 340, bottom: 390, left: 300, height: 50 },
      next: { top: 600, bottom: 660, left: 300, height: 60 },
    });

    expect(place.top).toBe(460 + 6);
  });

  it('goes inside the top edge of an image squeezed between neighbours', () => {
    const place = placeBlockToolbar({
      ...base,
      isMedia: true,
      previous: { top: 340, bottom: 390, left: 300, height: 50 },
      next: { top: 470, bottom: 520, left: 300, height: 50 },
    });

    expect(place).toEqual({ top: 400 + 6, left: 300 + 6 });
  });

  it('never covers the text above for an image whose neighbours are close', () => {
    const previous = { top: 330, bottom: 375, left: 300, height: 45 };
    const place = placeBlockToolbar({
      ...base,
      isMedia: true,
      rect: { top: 383, bottom: 743, left: 300, height: 360 },
      previous,
      next: { top: 765, bottom: 800, left: 300, height: 35 },
    });

    expect(place.top).toBeGreaterThanOrEqual(previous.bottom);
  });

  it('keeps the toolbar between the sticky bars and the footer', () => {
    const high = placeBlockToolbar({
      ...base,
      rect: { top: 20, bottom: 80, left: 300, height: 60 },
    });
    const low = placeBlockToolbar({
      ...base,
      isMedia: true,
      rect: { top: 880, bottom: 1500, left: 300, height: 620 },
      previous: { top: 840, bottom: 872, left: 300, height: 32 },
    });

    expect(high.top).toBeGreaterThanOrEqual(74);
    expect(low.top).toBeLessThanOrEqual(900 - 44 - 46);
  });

  it('keeps the toolbar inside the viewport horizontally', () => {
    const right = placeBlockToolbar({
      ...base,
      rect: { ...base.rect, left: 1300 },
    });
    const left = placeBlockToolbar({
      ...base,
      rect: { ...base.rect, left: -50 },
    });

    expect(right.left).toBe(1400 - 360 - 8);
    expect(left.left).toBe(8);
  });
});
