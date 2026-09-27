<script lang="ts">
  import SimpleBar from 'simplebar';
  import 'simplebar/dist/simplebar.css';
  import { Link } from '@inertiajs/svelte';
  import LogOut from '@lucide/svelte/icons/log-out';
  import PanelLeftClose from '@lucide/svelte/icons/panel-left-close';
  import Search from '@lucide/svelte/icons/search';
  import AppLogoIcon from '@/components/app-logo-icon.svelte';
  import LogoutAction from '@/components/logout-action.svelte';
  import type { AdminSidebarEntry } from '@/types/admin-sidebar';
  import AdminSidebarMenu from './admin-sidebar-menu.svelte';
  import {
    DESKTOP_BREAKPOINT,
    useAdminSidebarState,
  } from './sidebar-state.svelte';

  let {
    menu,
    userName,
    userEmail,
    logoutHref = '/logout',
  }: {
    menu: AdminSidebarEntry[];
    userName?: string;
    userEmail?: string;
    logoutHref?: string;
  } = $props();

  const sidebar = useAdminSidebarState();

  let searchInput = $state<HTMLInputElement>();

  $effect(() => {
    const query = window.matchMedia(`(min-width: ${DESKTOP_BREAKPOINT}px)`);
    const onChange = () => sidebar.syncViewport(query.matches);

    onChange();
    query.addEventListener('change', onChange);

    return () => query.removeEventListener('change', onChange);
  });

  $effect(() => {
    function onKeydown(event: KeyboardEvent): void {
      const target = event.target;
      const isEditable =
        target instanceof HTMLInputElement ||
        target instanceof HTMLTextAreaElement ||
        (target instanceof HTMLElement && target.isContentEditable);

      if (event.key === '/' && !isEditable) {
        event.preventDefault();

        if (sidebar.collapsed) {
          sidebar.setCollapsed(false);
        }

        searchInput?.focus();
      }

      if (event.key === 'Escape') {
        if (!sidebar.isDesktop && sidebar.mobileOpen) {
          sidebar.closeMobile();
        } else if (sidebar.floatingId !== null) {
          sidebar.closeFloating();
        }
      }
    }

    function onDocumentClick(event: MouseEvent): void {
      if (
        sidebar.floatingId !== null &&
        !(event.target as Element | null)?.closest('.sm-item--floating-open')
      ) {
        sidebar.closeFloating();
      }
    }

    document.addEventListener('keydown', onKeydown);
    document.addEventListener('click', onDocumentClick);

    return () => {
      document.removeEventListener('keydown', onKeydown);
      document.removeEventListener('click', onDocumentClick);
    };
  });

  /**
   * Overlay scrollbar for the menu region, as in batamtix's SidebarScroll.
   * SimpleBar's ResizeObserver keeps it in sync as accordions open and close.
   */
  function simplebar(node: HTMLElement) {
    const scrollbar = new SimpleBar(node, {
      autoHide: false,
      forceVisible: false,
      scrollbarMinSize: 24,
    });

    scrollbar.recalculate();

    const scroller = scrollbar.getScrollElement();
    const onScroll = () => sidebar.handleMenuScroll();

    scroller?.addEventListener('scroll', onScroll, { passive: true });

    return {
      destroy: () => {
        scroller?.removeEventListener('scroll', onScroll);
        scrollbar.unMount();
      },
    };
  }
</script>

<aside
  class="sidebar"
  id="appSidebar"
  tabindex="-1"
  aria-label="Sidebar admin"
  class:sidebar--collapsed={sidebar.isDesktop && sidebar.collapsed}
  class:sidebar--mobile-open={!sidebar.isDesktop && sidebar.mobileOpen}
>
  <div class="sidebar-inner">
    <div class="sidebar-header">
      <Link href="/dashboard" class="sidebar-brand">
        <div class="sidebar-icon">
          <div class="sidebar-icon-box">
            <AppLogoIcon height={32} />
          </div>
        </div>
        <div class="sidebar-logo">CatatanCakadi</div>
      </Link>

      <button
        type="button"
        class="sidebar-toggler"
        aria-label="Buka/tutup sidebar"
        aria-controls="appSidebar"
        aria-expanded={!sidebar.collapsed}
        onclick={() => sidebar.toggle()}
      >
        <PanelLeftClose size={18} />
      </button>
    </div>

    <div class="sidebar-search" role="search">
      <label class="sidebar-search-field">
        <Search size={16} aria-hidden="true" />
        <input
          type="search"
          class="form-control sidebar-search-input"
          placeholder="Cari menu… (/)"
          aria-label="Cari menu"
          autocomplete="off"
          bind:this={searchInput}
          bind:value={sidebar.searchQuery}
        />
      </label>
    </div>

    <div class="sidebar-body">
      <div class="sidebar-scroll" use:simplebar>
        <nav class="sidebar-nav" aria-label="Navigasi utama">
          <AdminSidebarMenu entries={menu} />
        </nav>
      </div>
    </div>

    <div class="sidebar-footer">
      <div class="userinfo">
        <div class="userinfo-avatar">
          <AppLogoIcon height={20} />
        </div>
        <div class="userinfo-detail">
          <strong class="userinfo-detail-title">{userName ?? '—'}</strong>
          <p class="userinfo-detail-email">{userEmail ?? ''}</p>
        </div>
        <div class="ms-auto">
          <LogoutAction
            href={logoutHref}
            class="btn btn-outline-light btn-square logout-actions"
            aria-label="Keluar dari akun"
          >
            <LogOut size={16} />
          </LogoutAction>
        </div>
      </div>
    </div>
  </div>
</aside>

<div
  class="sidebar-backdrop"
  class:sidebar-backdrop--visible={!sidebar.isDesktop && sidebar.mobileOpen}
  hidden={sidebar.isDesktop || !sidebar.mobileOpen}
  onclick={() => sidebar.closeMobile()}
  role="presentation"
></div>
