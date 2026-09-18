<script lang="ts">
  import { page } from '@inertiajs/svelte';
  import type { Snippet } from 'svelte';
  import PanelLeftOpen from '@lucide/svelte/icons/panel-left-open';
  import AdminClock from './admin-clock.svelte';
  import AdminFullscreenToggle from './admin-fullscreen-toggle.svelte';
  import AdminLanguageSwitcher from './admin-language-switcher.svelte';
  import AdminNotificationBell from './admin-notification-bell.svelte';
  import AdminUserMenu from './admin-user-menu.svelte';
  import { useAdminSidebarState } from './sidebar-state.svelte';

  let { actions }: { actions?: Snippet } = $props();

  const sidebar = useAdminSidebarState();
  const user = $derived(page.props.auth.user);
</script>

<nav class="navbar navbar-expand border-bottom" aria-label="Top navigation">
  <ul class="navbar-start">
    <li
      class="nav-item sidebar-toggling"
      class:sidebar-toggling--visible={sidebar.isDesktop && sidebar.collapsed}
    >
      <button
        class="sidebar-toggler"
        aria-label="Open/close sidebar"
        aria-controls="appSidebar"
        aria-expanded={!sidebar.collapsed}
        onclick={() => sidebar.toggle()}
      >
        <PanelLeftOpen size={18} aria-hidden="true" />
      </button>
    </li>

    <li class="nav-item">
      <AdminClock />
    </li>
  </ul>
  <ul class="navbar-end">
    <li class="nav-item">
      <AdminFullscreenToggle />
    </li>

    <li class="nav-item">
      <AdminNotificationBell />
    </li>

    <li class="nav-item">
      <AdminLanguageSwitcher />
    </li>

    <AdminUserMenu userName={user.name} userEmail={user.email} />

    {#if actions}
      {@render actions()}
    {/if}
  </ul>
</nav>
