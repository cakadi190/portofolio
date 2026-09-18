<script lang="ts">
  import type { Snippet } from 'svelte';
  import type { AdminSidebarEntry } from '@/types/admin-sidebar';
  import AdminBackToTop from './admin-back-to-top.svelte';
  import AdminFooter from './admin-footer.svelte';
  import AdminNavbar from './admin-navbar.svelte';
  import AdminPullToRefresh from './admin-pull-to-refresh.svelte';
  import AdminSidebar from './admin-sidebar.svelte';
  import { provideAdminSidebarState } from './sidebar-state.svelte';

  let {
    menu = [],
    userName,
    userEmail,
    navbarActions,
    children,
  }: {
    menu?: AdminSidebarEntry[];
    userName?: string;
    userEmail?: string;
    navbarActions?: Snippet;
    children?: Snippet;
  } = $props();

  provideAdminSidebarState();

  let mainElement = $state<HTMLElement>();
</script>

<div class="admin-shell">
  <a class="visually-hidden-focusable" href="#admin-main-content"
    >Skip to main content</a
  >

  <AdminSidebar {menu} {userName} {userEmail} />

  <main id="admin-main-content" data-main-scroll bind:this={mainElement}>
    <AdminPullToRefresh scroller={mainElement} />

    <AdminNavbar actions={navbarActions} />

    <div class="content-inner">
      {@render children?.()}
    </div>

    <AdminFooter />
  </main>

  <AdminBackToTop scroller={mainElement} bottom="5rem" />
</div>
