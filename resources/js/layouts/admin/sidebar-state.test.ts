import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
  AdminSidebarState,
  COLLAPSED_STORAGE_KEY,
  FLOATING_MENU_HOVER_CLOSE_DELAY_MS,
  FLOATING_MENU_SCROLL_SETTLE_MS,
  useAdminSidebarState,
} from '@/layouts/admin/sidebar-state.svelte';

beforeEach(() => {
  window.localStorage.clear();
  document.documentElement.classList.remove('sidebar-toggled');
});

describe('AdminSidebarState', () => {
  it('restores the collapsed preference from storage', () => {
    window.localStorage.setItem(COLLAPSED_STORAGE_KEY, '1');

    expect(new AdminSidebarState().collapsed).toBe(true);
  });

  it('reads the viewport from matchMedia', () => {
    vi.mocked(window.matchMedia).mockReturnValueOnce({
      matches: false,
    } as MediaQueryList);

    expect(new AdminSidebarState().isDesktop).toBe(false);
  });

  it('collapses on desktop and persists it', () => {
    const state = new AdminSidebarState();
    state.isDesktop = true;

    state.toggle();

    expect(state.collapsed).toBe(true);
    expect(window.localStorage.getItem(COLLAPSED_STORAGE_KEY)).toBe('1');
    expect(document.documentElement.classList).toContain('sidebar-toggled');

    state.toggle();

    expect(state.collapsed).toBe(false);
    expect(window.localStorage.getItem(COLLAPSED_STORAGE_KEY)).toBe('0');
  });

  it('toggles the mobile drawer on small screens', () => {
    const state = new AdminSidebarState();
    state.syncViewport(false);

    state.toggle();
    expect(state.mobileOpen).toBe(true);
    expect(state.collapsed).toBe(false);

    state.closeMobile();
    expect(state.mobileOpen).toBe(false);
  });

  it('closes the drawer when switching to desktop', () => {
    const state = new AdminSidebarState();
    state.mobileOpen = true;

    state.syncViewport(true);

    expect(state.mobileOpen).toBe(false);
  });

  it('clears search when collapsing', () => {
    const state = new AdminSidebarState();
    state.searchQuery = '  pos ';
    expect(state.searching).toBe(true);

    state.setCollapsed(true);

    expect(state.searchQuery).toBe('');
    expect(state.searching).toBe(false);
  });

  it('keeps branches as an accordion within a group', () => {
    const state = new AdminSidebarState();

    state.toggleBranch('root-0-0', 'root-0');
    state.toggleBranch('root-0-1', 'root-0');

    expect(state.isBranchOpen('root-0-0')).toBe(false);
    expect(state.isBranchOpen('root-0-1')).toBe(true);

    state.toggleBranch('root-0-1', 'root-0');
    expect(state.isBranchOpen('root-0-1')).toBe(false);
  });

  it('drops descendants of a closed sibling and keeps other groups', () => {
    const state = new AdminSidebarState();
    state.openBranch('a-0', 'a');
    state.openBranch('a-0-1', 'a-0');
    state.openBranch('b-0', 'b');

    state.openBranch('a-1', 'a');

    expect(state.isBranchOpen('a-0')).toBe(false);
    expect(state.isBranchOpen('a-0-1')).toBe(false);
    expect(state.isBranchOpen('b-0')).toBe(true);
  });

  it('openBranch is a no-op for an already open branch', () => {
    const state = new AdminSidebarState();
    state.openBranch('a-0', 'a');

    state.openBranch('a-0', 'a');

    expect(state.isBranchOpen('a-0')).toBe(true);
  });

  describe('floating flyouts', () => {
    it('opens, repositions and closes', () => {
      const state = new AdminSidebarState();
      const reposition = vi.fn();

      state.openFloating('x', reposition);

      expect(state.floatingId).toBe('x');
      expect(reposition).toHaveBeenCalledOnce();

      state.closeFloating();
      expect(state.floatingId).toBeNull();
    });

    it('closes after a delay unless cancelled', () => {
      vi.useFakeTimers();
      const state = new AdminSidebarState();
      state.openFloating('x', vi.fn());

      state.scheduleFloatingClose('x');
      state.cancelFloatingClose();
      vi.advanceTimersByTime(FLOATING_MENU_HOVER_CLOSE_DELAY_MS + 1);
      expect(state.floatingId).toBe('x');

      state.scheduleFloatingClose('x');
      vi.advanceTimersByTime(FLOATING_MENU_HOVER_CLOSE_DELAY_MS + 1);
      expect(state.floatingId).toBeNull();
      vi.useRealTimers();
    });

    it('ignores scheduled close for a different flyout', () => {
      vi.useFakeTimers();
      const state = new AdminSidebarState();
      state.openFloating('x', vi.fn());

      state.scheduleFloatingClose('other');
      vi.advanceTimersByTime(1000);

      expect(state.floatingId).toBe('x');
      vi.useRealTimers();
    });

    it('suspends hover-open briefly while scrolling', () => {
      const state = new AdminSidebarState();
      state.openFloating('x', vi.fn());
      const before = Date.now();

      state.handleMenuScroll();

      expect(state.floatingId).toBeNull();
      expect(state.floatingSuspendedUntil).toBeGreaterThanOrEqual(
        before + FLOATING_MENU_SCROLL_SETTLE_MS,
      );
    });
  });
});

describe('useAdminSidebarState', () => {
  it('throws outside of the sidebar layout', () => {
    expect(() => useAdminSidebarState()).toThrow();
  });
});
