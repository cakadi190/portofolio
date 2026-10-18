export type BoxRect = {
  top: number;
  bottom: number;
  left: number;
  height: number;
};

export type ToolbarPlacementInput = {
  /** The block the toolbar belongs to. */
  rect: BoxRect;
  /** Neighbouring blocks; null or zero height when absent or not laid out. */
  previous: BoxRect | null;
  next: BoxRect | null;
  toolbar: { width: number; height: number };
  viewport: { width: number; height: number };
  /** Lowest `top` the toolbar may take (under the sticky bars). */
  floor: number;
  /** Height reserved at the bottom (footer). */
  footer: number;
  /** Blocks that are not text (image, diagram, equation, card) may take the toolbar inside their top edge. */
  isMedia: boolean;
};

const GAP = 6;
const EDGE = 8;

/**
 * Where the floating block toolbar goes so it hides as little as possible: above the block
 * when the gap to the previous block is tall enough, else below it, and for media blocks
 * inside their top edge when either side would cover a neighbour.
 */
export function placeBlockToolbar(input: ToolbarPlacementInput): {
  top: number;
  left: number;
} {
  const { rect, previous, next, toolbar, viewport, floor, footer, isMedia } =
    input;
  const minTop = floor;
  const maxTop = Math.max(minTop, viewport.height - footer - toolbar.height);

  const aboveTop = Math.max(rect.top - toolbar.height - GAP, minTop);
  const belowTop = rect.bottom + GAP;

  // Pixels of a neighbour (or of the block itself, once clamped to the floor) a side would hide.
  const coversAbove = Math.max(
    0,
    previous && previous.height > 0 ? previous.bottom - aboveTop : 0,
    aboveTop + toolbar.height - rect.top,
  );
  const coversBelow =
    belowTop > maxTop
      ? Number.POSITIVE_INFINITY
      : Math.max(
          0,
          next && next.height > 0 ? belowTop + toolbar.height - next.top : 0,
        );

  let top = aboveTop;
  let left = rect.left;

  if (coversAbove > 0 && coversBelow < coversAbove) {
    top = belowTop;
  } else if (coversAbove > 0 && isMedia) {
    top = Math.min(Math.max(rect.top + GAP, minTop), maxTop);
    left = rect.left + GAP;
  }

  return {
    top: Math.max(minTop, Math.min(top, maxTop)),
    left: Math.max(EDGE, Math.min(left, viewport.width - toolbar.width - EDGE)),
  };
}
