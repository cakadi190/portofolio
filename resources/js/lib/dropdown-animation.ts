/**
 * Enter/leave animations for Bootstrap 5 dropdowns.
 *
 * Bootstrap toggles `.dropdown-menu.show` (i.e. `display: none` ↔ `block`) with
 * no transition of its own, so a menu pops in and out instantly. This module
 * layers a shadcn/popover-style fade + zoom on top of that lifecycle *without*
 * touching Bootstrap or Popper: it listens to the four public dropdown events
 * via delegation on `document`, resolves the placement Popper actually settled
 * on, and toggles the `.dropdown-anim-enter` / `.dropdown-anim-leave` classes
 * whose keyframes live in `resources/css/components/_dropdown-animation.scss`.
 *
 * Ported from Batamtix Core's `resources/js/libs/dropdown-animation.ts`, with
 * the jQuery dependency dropped (Gettix has none) and the `btx-` custom
 * property / class prefix dropped (Gettix doesn't rename Bootstrap's
 * `$prefix`, so it reads Bootstrap's own `--bs-position` directly).
 *
 * Notes on the two non-obvious pieces:
 *
 * - **Leave.** Before `hidden` fires, Bootstrap removes `.show`, destroys the
 *   Popper instance (which resets the inline positioning Popper had written)
 *   *and* strips the `data-bs-popper` attribute. That last one matters more
 *   than it looks: Bootstrap gates all of its own dropdown positioning on it
 *   (`.dropdown-menu[data-bs-popper]`, `.dropdown-menu-end[data-bs-popper]`,
 *   `.dropup .dropdown-menu[data-bs-popper]`, …), so a menu left without it
 *   snaps back to the unaligned default mid-animation. Both the inline styles
 *   and the attribute are therefore captured during `hide` and put back for the
 *   length of the leave animation, so the menu fades out exactly where the user
 *   last saw it.
 * - **Placement.** Popper resolves (and possibly flips) the placement in a
 *   microtask queued by `createPopper()`, which Bootstrap calls *before* it
 *   fires `shown`. Queueing our own microtask from the `shown` handler
 *   therefore reads the final placement, and still lands before the first paint
 *   of the newly displayed menu — no flicker, no forced reflow.
 *
 * Everything is keyed off a `WeakMap`, and the only listeners are the four
 * delegated ones registered here, so dynamically inserted dropdowns (an
 * Inertia navigation, a conditionally rendered Svelte block) animate without
 * any re-initialisation, and nothing leaks when a menu is removed from the DOM.
 */

/** Class carrying the enter keyframes. */
const ENTER_CLASS = 'dropdown-anim-enter';

/** Class carrying the leave keyframes; also re-asserts `display: block`. */
const LEAVE_CLASS = 'dropdown-anim-leave';

/**
 * Attribute the SCSS reads to pick the slide direction and transform origin.
 *
 * This is Popper's own attribute, not one of ours: while the menu is open
 * Popper already publishes the post-flip placement here, so the stylesheet
 * reads it directly rather than mirroring it onto a parallel attribute. We only
 * ever write it ourselves in the two cases Popper cannot cover — a menu Popper
 * never positions (`data-bs-display="static"`, collapsed navbars) and the leave
 * animation, which runs after Bootstrap has destroyed the Popper instance — and
 * both writes are undone again in `settle()`.
 */
const PLACEMENT_ATTRIBUTE = 'data-popper-placement';

/**
 * Bootstrap's own marker for "this menu is currently positioned". Every
 * built-in positioning rule (`.dropdown-menu[data-bs-popper]`, the `-start` /
 * `-end` alignment variants, the `dropup` / `dropend` / `dropstart` offsets) is
 * scoped to it, and Bootstrap removes it before `hidden` fires.
 */
const POPPER_ATTRIBUTE = 'data-bs-popper';

/**
 * Inline properties Popper's `applyStyles` modifier writes onto the menu, and
 * clears again when the instance is destroyed. Captured verbatim during `hide`
 * so the leave animation can put them back.
 */
const POPPER_STYLE_PROPERTIES = [
    'position',
    'top',
    'right',
    'bottom',
    'left',
    'margin',
    'transform',
];

/**
 * Safety net in case `animationend` never arrives — a menu removed from the DOM
 * mid-animation, or a background tab throttling the animation, would otherwise
 * leave the state entry and its `display: block` in place. Comfortably longer
 * than the slowest keyframe duration in the stylesheet.
 */
const ANIMATION_TIMEOUT_MS = 1000;

/** Which half of the lifecycle a menu is currently in. */
type MenuPhase = 'entering' | 'leaving';

/** Per-menu bookkeeping for the animation currently in flight. */
interface MenuAnimationState {
    /** Direction of the running (or about-to-run) animation. */
    phase: MenuPhase;
    /** Placement resolved from the dropdown's classes, used until Popper reports its own. */
    placement: string;
    /** Popper's inline positioning, captured before Bootstrap destroys the instance. */
    frozenStyles: Record<string, string> | null;
    /** Bootstrap's `data-bs-popper` value, captured before Bootstrap strips it. */
    frozenPopperAttribute: string | null;
    /** Whether *we* wrote `data-popper-placement`, and therefore owe its removal. */
    wrotePlacement: boolean;
    /** Tears down the listeners and timer of the running animation. */
    dispose: (() => void) | null;
}

const menuStates = new WeakMap<HTMLElement, MenuAnimationState>();

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

let initialized = false;

/**
 * Finds the menu a toggle controls, mirroring Bootstrap's own resolution order
 * (next matching sibling → previous matching sibling → first match inside the
 * wrapper) so we always animate exactly the element Bootstrap shows.
 */
function resolveMenu(toggle: HTMLElement): HTMLElement | null {
    for (
        let sibling = toggle.nextElementSibling;
        sibling;
        sibling = sibling.nextElementSibling
    ) {
        if (sibling.classList.contains('dropdown-menu')) {
            return sibling as HTMLElement;
        }
    }

    for (
        let sibling = toggle.previousElementSibling;
        sibling;
        sibling = sibling.previousElementSibling
    ) {
        if (sibling.classList.contains('dropdown-menu')) {
            return sibling as HTMLElement;
        }
    }

    return (
        toggle.parentElement?.querySelector<HTMLElement>('.dropdown-menu') ??
        null
    );
}

/**
 * Reproduces Bootstrap's `Dropdown#_getPlacement()`: the direction comes from
 * the wrapper's `dropup` / `dropend` / `dropstart` / `*-center` class, and the
 * alignment from the `--bs-position` custom property that `dropdown-menu-end`
 * (and its responsive variants) sets.
 *
 * This is the placement used for menus Popper never positions — anything inside
 * a `.navbar` or using `data-bs-display="static"` — and as the pre-paint value
 * for everything else.
 */
function resolveStaticPlacement(
    toggle: HTMLElement,
    menu: HTMLElement,
): string {
    const wrapper = toggle.parentElement;
    const isRtl = document.documentElement.dir === 'rtl';

    if (wrapper?.classList.contains('dropend')) {
        return isRtl ? 'left-start' : 'right-start';
    }

    if (wrapper?.classList.contains('dropstart')) {
        return isRtl ? 'right-start' : 'left-start';
    }

    if (wrapper?.classList.contains('dropup-center')) {
        return 'top';
    }

    if (wrapper?.classList.contains('dropdown-center')) {
        return 'bottom';
    }

    const isEnd =
        window
            .getComputedStyle(menu)
            .getPropertyValue('--bs-position')
            .trim() === 'end';
    const side = wrapper?.classList.contains('dropup') ? 'top' : 'bottom';
    const alignment = isEnd !== isRtl ? 'end' : 'start';

    return `${side}-${alignment}`;
}

/**
 * Snapshots the inline styles Popper wrote onto the menu. Only the inline
 * `style` object is read, never the computed style, so this costs nothing and
 * cannot force a reflow.
 */
function freezePopperStyles(menu: HTMLElement): Record<string, string> | null {
    const frozen: Record<string, string> = {};

    for (const property of POPPER_STYLE_PROPERTIES) {
        const value = menu.style.getPropertyValue(property);

        if (value !== '') {
            frozen[property] = value;
        }
    }

    return Object.keys(frozen).length > 0 ? frozen : null;
}

/**
 * Ends whatever animation a menu is running and returns it to a clean slate:
 * animation classes off, frozen Popper styles removed, listeners and timer torn
 * down, state entry dropped.
 *
 * Called both when an animation finishes normally and when the opposite
 * transition interrupts it, which is what keeps rapid open/close cycles from
 * stacking two animations on one element.
 */
function settle(menu: HTMLElement): void {
    const state = menuStates.get(menu);

    if (!state) {
        return;
    }

    menuStates.delete(menu);
    state.dispose?.();
    menu.classList.remove(ENTER_CLASS, LEAVE_CLASS);

    if (state.frozenStyles) {
        for (const property of Object.keys(state.frozenStyles)) {
            menu.style.removeProperty(property);
        }
    }

    // Only ever set by the leave path, and always back to the exact value
    // Bootstrap itself had removed — so dropping it here restores Bootstrap's
    // own hidden state rather than inventing one.
    if (state.frozenPopperAttribute !== null) {
        menu.removeAttribute(POPPER_ATTRIBUTE);
    }

    // Only ever true when this module — not Popper — put the placement attribute
    // on, so a menu Popper is still positioning keeps the one it owns.
    if (state.wrotePlacement) {
        menu.removeAttribute(PLACEMENT_ATTRIBUTE);
    }
}

/**
 * Applies an animation class and settles the menu once that animation reports
 * back. Only `animationend`/`animationcancel` raised by the menu itself are
 * honoured, so an animated icon inside the menu can never cut the menu's own
 * animation short.
 */
function runAnimation(
    menu: HTMLElement,
    state: MenuAnimationState,
    className: string,
): void {
    const onAnimationDone = (event: AnimationEvent): void => {
        if (event.target === menu) {
            settle(menu);
        }
    };

    const timer = window.setTimeout(() => settle(menu), ANIMATION_TIMEOUT_MS);

    state.dispose = (): void => {
        window.clearTimeout(timer);
        menu.removeEventListener('animationend', onAnimationDone);
        menu.removeEventListener('animationcancel', onAnimationDone);
    };

    menu.addEventListener('animationend', onAnimationDone);
    menu.addEventListener('animationcancel', onAnimationDone);
    menu.classList.add(className);
}

/**
 * `show.bs.dropdown` — fires before Bootstrap creates the Popper instance and
 * adds `.show`, which makes it the right moment to cancel a leave animation
 * that may still be running: the stale inline styles are stripped before Popper
 * gets a chance to write fresh ones.
 */
function handleShow(event: Event): void {
    const toggle = event.target as HTMLElement;
    const menu = resolveMenu(toggle);

    if (!menu || reducedMotion.matches) {
        return;
    }

    const placement = resolveStaticPlacement(toggle, menu);

    settle(menu);
    menuStates.set(menu, {
        phase: 'entering',
        placement,
        frozenStyles: null,
        frozenPopperAttribute: null,
        wrotePlacement: false,
        dispose: null,
    });
}

/**
 * `shown.bs.dropdown` — the menu is displayed but not yet painted. Defer to a
 * microtask so Popper's first positioning pass (queued earlier in this same
 * task) has published `data-popper-placement`, then start the enter animation.
 */
function handleShown(event: Event): void {
    const menu = resolveMenu(event.target as HTMLElement);
    const state = menu ? menuStates.get(menu) : undefined;

    if (!menu || !state || state.phase !== 'entering') {
        return;
    }

    queueMicrotask(() => {
        // A rapid re-toggle may already have replaced or cleared this state.
        if (
            menuStates.get(menu) !== state ||
            !menu.classList.contains('show')
        ) {
            return;
        }

        const popperPlacement = menu.getAttribute(PLACEMENT_ATTRIBUTE);

        // Popper owns the attribute whenever it positioned the menu, so the common
        // path is a pure read. Only the menus Popper never touches get it written
        // here, and `settle()` takes those back off again.
        if (popperPlacement === null) {
            menu.setAttribute(PLACEMENT_ATTRIBUTE, state.placement);
            state.wrotePlacement = true;
        } else {
            state.placement = popperPlacement;
        }

        runAnimation(menu, state, ENTER_CLASS);
    });
}

/**
 * `hide.bs.dropdown` — the last point at which Popper's inline positioning is
 * still on the element, so it is captured here and replayed in `hidden`.
 */
function handleHide(event: Event): void {
    const toggle = event.target as HTMLElement;
    const menu = resolveMenu(toggle);

    if (!menu || reducedMotion.matches) {
        return;
    }

    // Captured before `settle()` runs, so an interrupted enter can't take
    // Bootstrap's still-present positioning state down with it.
    const placement =
        menu.getAttribute(PLACEMENT_ATTRIBUTE) ??
        resolveStaticPlacement(toggle, menu);
    const frozenStyles = freezePopperStyles(menu);
    const frozenPopperAttribute = menu.getAttribute(POPPER_ATTRIBUTE);
    const isInFlow = window.getComputedStyle(menu).position === 'static';

    // Cancels a still-running enter animation.
    settle(menu);

    // A statically positioned menu — Bootstrap's collapsed-navbar layout — takes
    // up space in the flow, so holding it visible would push the surrounding
    // content around for the length of the animation. Those hide instantly.
    if (isInFlow) {
        return;
    }

    menuStates.set(menu, {
        phase: 'leaving',
        placement,
        frozenStyles,
        frozenPopperAttribute,
        wrotePlacement: false,
        dispose: null,
    });
}

/**
 * `hidden.bs.dropdown` — Bootstrap has dropped `.show` and destroyed the Popper
 * instance. Re-pin the menu where it was and play the leave animation.
 */
function handleHidden(event: Event): void {
    const menu = resolveMenu(event.target as HTMLElement);
    const state = menu ? menuStates.get(menu) : undefined;

    if (!menu || !state || state.phase !== 'leaving') {
        return;
    }

    if (state.frozenStyles) {
        for (const [property, value] of Object.entries(state.frozenStyles)) {
            menu.style.setProperty(property, value);
        }
    }

    // Re-arms Bootstrap's positioning rules for the length of the animation —
    // without this a `dropdown-menu-end` visibly jumps back to start-aligned as
    // it fades, and a `dropup` drops the spacer that lifts it above the toggle.
    if (state.frozenPopperAttribute !== null) {
        menu.setAttribute(POPPER_ATTRIBUTE, state.frozenPopperAttribute);
    }

    // Popper's instance is gone by now, so it took its own placement attribute
    // with it — put it back for the length of the leave animation.
    menu.setAttribute(PLACEMENT_ATTRIBUTE, state.placement);
    state.wrotePlacement = true;

    runAnimation(menu, state, LEAVE_CLASS);
}

/**
 * Registers the dropdown animation driver.
 *
 * Idempotent, and safe to call before any dropdown exists: the four listeners
 * are delegated on `document`, so dropdowns rendered later — after an Inertia
 * navigation, or a conditionally rendered Svelte block — animate without any
 * re-initialisation. Bootstrap's API, markup, keyboard handling and ARIA
 * attributes are left completely untouched; when the user prefers reduced
 * motion nothing is applied at all and dropdowns behave exactly like stock
 * Bootstrap.
 */
export function initDropdownAnimation(): void {
    if (initialized) {
        return;
    }

    initialized = true;

    document.addEventListener('show.bs.dropdown', handleShow);
    document.addEventListener('shown.bs.dropdown', handleShown);
    document.addEventListener('hide.bs.dropdown', handleHide);
    document.addEventListener('hidden.bs.dropdown', handleHidden);
}
