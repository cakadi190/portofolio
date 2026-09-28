import { getContext, setContext } from 'svelte';

/** localStorage key persisting the desktop collapsed preference. Keep in sync
 *  with the anti-FOUC boot script in resources/views/app.blade.php. */
export const COLLAPSED_STORAGE_KEY = 'sidebar:collapsed';

/** Bootstrap 5 `lg` breakpoint (px); mirrors the SCSS desktop media query. */
export const DESKTOP_BREAKPOINT = 992;

export const FLOATING_MENU_GAP = 8;
export const FLOATING_MENU_VIEWPORT_PADDING = 8;
export const FLOATING_MENU_HOVER_CLOSE_DELAY_MS = 200;
export const FLOATING_MENU_SCROLL_SETTLE_MS = 150;
export const TRANSITION_DURATION_MS = 250;

const CONTEXT_KEY = Symbol('admin-sidebar-state');

/**
 * Behavioural state for the admin sidebar shell: desktop collapse (persisted),
 * mobile drawer (runtime-only, always closed on load), open submenu branches,
 * and the live search query.
 *
 * Owned as a single runes class instead of several independent `$state`
 * variables so the sidebar, navbar toggle button, and menu items can all read
 * and mutate the same instance via Svelte context, mirroring how batamtix's
 * `SidebarMenuManager` was the single facade over `SidebarState`.
 */
export class AdminSidebarState {
  collapsed = $state(false);
  mobileOpen = $state(false);
  searchQuery = $state('');
  private openBranches = $state(new Set<string>());
  isDesktop = $state(true);

  /** Level-1 item whose collapsed-rail flyout is open (one at a time). */
  floatingId = $state<string | null>(null);
  /**
   * Per-item panel coordinates. Kept after a panel closes so it fades out where
   * it was instead of snapping to its static position mid-transition.
   */
  floatingLayout = $state<Record<string, { style: string; up: boolean }>>({});
  /** Hover-open is suppressed until this timestamp (ms) while the menu scrolls. */
  floatingSuspendedUntil = 0;
  private floatingCloseTimer: number | null = null;
  private floatingReposition: (() => void) | null = null;

  constructor() {
    if (typeof window === 'undefined') {
      return;
    }

    this.collapsed = window.localStorage.getItem(COLLAPSED_STORAGE_KEY) === '1';
    this.isDesktop = window.matchMedia(
      `(min-width: ${DESKTOP_BREAKPOINT}px)`,
    ).matches;
  }

  get searching(): boolean {
    return this.searchQuery.trim().length > 0;
  }

  toggle(): void {
    if (this.isDesktop) {
      this.setCollapsed(!this.collapsed);
      return;
    }

    this.mobileOpen = !this.mobileOpen;
  }

  openFloating(id: string, reposition: () => void): void {
    this.cancelFloatingClose();
    this.floatingReposition = reposition;
    this.floatingId = id;
    reposition();
  }

  closeFloating(): void {
    this.cancelFloatingClose();
    this.floatingId = null;
    this.floatingReposition = null;
  }

  /** Deferred so the pointer can cross the gap between trigger and panel. */
  scheduleFloatingClose(id: string): void {
    if (this.floatingId !== id) {
      return;
    }

    this.cancelFloatingClose();
    this.floatingCloseTimer = window.setTimeout(
      () => this.closeFloating(),
      FLOATING_MENU_HOVER_CLOSE_DELAY_MS,
    );
  }

  cancelFloatingClose(): void {
    if (this.floatingCloseTimer !== null) {
      window.clearTimeout(this.floatingCloseTimer);
      this.floatingCloseTimer = null;
    }
  }

  /**
   * Called on menu scroll: panels are `position: fixed` at the coordinates
   * measured when they opened, so they'd be left behind by the scrolling
   * items, and items sliding under a still pointer must not open flyouts.
   */
  handleMenuScroll(): void {
    this.closeFloating();
    this.floatingSuspendedUntil = Date.now() + FLOATING_MENU_SCROLL_SETTLE_MS;
  }

  /** Re-clamps the open panel after a nested accordion changes its height. */
  repositionFloating(): void {
    this.floatingReposition?.();
  }

  setCollapsed(value: boolean): void {
    this.closeFloating();
    this.collapsed = value;

    if (value) {
      this.clearSearch();
    }

    if (typeof window !== 'undefined') {
      window.localStorage.setItem(COLLAPSED_STORAGE_KEY, value ? '1' : '0');
    }
  }

  closeMobile(): void {
    this.mobileOpen = false;
  }

  syncViewport(isDesktop: boolean): void {
    this.isDesktop = isDesktop;
    this.closeFloating();

    if (isDesktop) {
      this.mobileOpen = false;
    }
  }

  isBranchOpen(id: string): boolean {
    return this.openBranches.has(id);
  }

  /**
   * Accordion behaviour: opening a branch closes every other branch sharing
   * its `groupId` (`${parentId}-${level}`), while closing just closes it. Branch
   * ids are `${groupId}-${index}`, so descendants of a closed sibling are
   * dropped along with it.
   */
  toggleBranch(id: string, groupId: string): void {
    const next = new Set(this.openBranches);

    if (next.has(id)) {
      next.delete(id);
    } else {
      this.closeSiblings(next, id, groupId);
      next.add(id);
    }

    this.openBranches = next;
  }

  /** Opens a branch exclusively within its group (used to reveal the active route). */
  openBranch(id: string, groupId: string): void {
    if (this.openBranches.has(id)) {
      return;
    }

    const next = new Set(this.openBranches);

    this.closeSiblings(next, id, groupId);
    next.add(id);
    this.openBranches = next;
  }

  private closeSiblings(branches: Set<string>, id: string, groupId: string): void {
    for (const openId of branches) {
      if (openId.startsWith(`${groupId}-`) && openId !== id && !openId.startsWith(`${id}-`)) {
        branches.delete(openId);
      }
    }
  }

  clearSearch(): void {
    this.searchQuery = '';
  }
}

export function provideAdminSidebarState(): AdminSidebarState {
  const state = new AdminSidebarState();

  setContext(CONTEXT_KEY, state);

  return state;
}

export function useAdminSidebarState(): AdminSidebarState {
  const state = getContext<AdminSidebarState>(CONTEXT_KEY);

  if (!state) {
    throw new Error(
      'useAdminSidebarState() must be called within AdminSidebarLayout.',
    );
  }

  return state;
}
