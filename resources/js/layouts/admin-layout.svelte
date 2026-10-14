<script lang="ts">
  import { page } from '@inertiajs/svelte';
  import type { Snippet } from 'svelte';
  import { adminMenu } from '@/layouts/admin/admin-menu';
  import AdminSidebarLayout from '@/layouts/admin/admin-sidebar-layout.svelte';
  import type { AdminSidebarEntry } from '@/types/admin-sidebar';
  import { Toaster } from 'svelte-french-toast';
  import { onMount } from 'svelte';

  // Admin CSS is not part of the public bundle; SPA navigations into the admin
  // load it here, full page loads get it from a <link> in app.blade.php.
  onMount(() => {
    void import('../../css/admin.scss');
  });

  let {
    menu: menuOverride,
    userName = page.props.auth.user.name,
    userEmail = page.props.auth.user.email,
    children,
  }: {
    menu?: AdminSidebarEntry[];
    userName?: string;
    userEmail?: string;
    children?: Snippet;
  } = $props();

  const menu = $derived(menuOverride ?? adminMenu(page.url, page.props.auth.user.account_type));
</script>

<AdminSidebarLayout {menu} {userName} {userEmail}>
  {@render children?.()}

  <Toaster position="top-right" toastOptions={{ duration: 4000 }} />
</AdminSidebarLayout>
