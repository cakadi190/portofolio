import type { TransitionConfig } from 'svelte/transition';

/**
 * shadcn/ui overlay motion for popups rendered by Svelte (`{#if open}`), the
 * counterpart of the Bootstrap dropdown/tooltip/popover styles in
 * `resources/css/components/_dropdown-animation.scss`:
 *
 *   enter  150ms ease · fade 0 → 1 · zoom .95 → 1 · slide 0.5rem in from the
 *          side facing the trigger
 *   leave  150ms ease · fade 1 → 0 · zoom 1 → .95 · no slide
 *
 * @example <div in:overlayIn={{ side: 'bottom' }} out:overlayOut>…</div>
 */
export type OverlaySide = 'top' | 'bottom' | 'left' | 'right';

type OverlayParams = {
  /** Side of the trigger the popup sits on; it slides in away from it. */
  side?: OverlaySide;
  /** Where the zoom grows from, e.g. `top left`. Defaults to the trigger-facing edge. */
  origin?: string;
};

const DURATION = 150;
const SCALE = 0.95;
const OFFSET_REM = 0.5;

const SLIDE: Record<OverlaySide, [number, number]> = {
  bottom: [0, -OFFSET_REM],
  top: [0, OFFSET_REM],
  right: [-OFFSET_REM, 0],
  left: [OFFSET_REM, 0],
};

const ORIGIN: Record<OverlaySide, string> = {
  bottom: 'top center',
  top: 'bottom center',
  right: 'left center',
  left: 'right center',
};

/** CSS `ease` (cubic-bezier(.25, .1, .25, 1)) as an easing function over t ∈ [0, 1]. */
export function ease(t: number): number {
  const [x1, y1, x2, y2] = [0.25, 0.1, 0.25, 1];
  const bezier = (a: number, b: number, s: number): number =>
    3 * a * (1 - s) ** 2 * s + 3 * b * (1 - s) * s ** 2 + s ** 3;

  if (t <= 0 || t >= 1) {
    return Math.min(1, Math.max(0, t));
  }

  let low = 0;
  let high = 1;

  for (let step = 0; step < 24; step++) {
    const mid = (low + high) / 2;

    if (bezier(x1, x2, mid) < t) {
      low = mid;
    } else {
      high = mid;
    }
  }

  return bezier(y1, y2, (low + high) / 2);
}

function prefersReducedMotion(): boolean {
  return (
    typeof window !== 'undefined' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches
  );
}

export function overlayIn(
  _node: Element,
  { side = 'bottom', origin }: OverlayParams = {},
): TransitionConfig {
  const [x, y] = SLIDE[side];

  return {
    duration: prefersReducedMotion() ? 0 : DURATION,
    easing: ease,
    css: (t, u) =>
      `transform-origin: ${origin ?? ORIGIN[side]}; opacity: ${t}; scale: ${
        SCALE + (1 - SCALE) * t
      }; translate: ${x * u}rem ${y * u}rem;`,
  };
}

export function overlayOut(
  _node: Element,
  { side = 'bottom', origin }: OverlayParams = {},
): TransitionConfig {
  return {
    duration: prefersReducedMotion() ? 0 : DURATION,
    easing: ease,
    css: (t) =>
      `transform-origin: ${origin ?? ORIGIN[side]}; opacity: ${t}; scale: ${
        SCALE + (1 - SCALE) * t
      };`,
  };
}
