import { getContext, setContext } from 'svelte';

/** localStorage key persisting the desktop collapsed preference. Keep in sync
 *  with the anti-FOUC boot script in resources/views/app.blade.php. */
export const COLLAPSED_STORAGE_KEY = 'sidebar:collapsed';

/** Bootstrap 5 `lg` breakpoint (px); mirrors the SCSS desktop media query. */
export const DESKTOP_BREAKPOINT = 992;

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

  setCollapsed(value: boolean): void {
    this.collapsed = value;

    if (typeof window !== 'undefined') {
      window.localStorage.setItem(COLLAPSED_STORAGE_KEY, value ? '1' : '0');
    }
  }

  closeMobile(): void {
    this.mobileOpen = false;
  }

  syncViewport(isDesktop: boolean): void {
    this.isDesktop = isDesktop;

    if (isDesktop) {
      this.mobileOpen = false;
    }
  }

  isBranchOpen(id: string): boolean {
    return this.openBranches.has(id);
  }

  toggleBranch(id: string): void {
    const next = new Set(this.openBranches);

    if (next.has(id)) {
      next.delete(id);
    } else {
      next.add(id);
    }

    this.openBranches = next;
  }

  openBranch(id: string): void {
    if (this.openBranches.has(id)) {
      return;
    }

    this.openBranches = new Set(this.openBranches).add(id);
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
