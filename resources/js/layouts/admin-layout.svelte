<script lang="ts">
  import { page } from '@inertiajs/svelte';
  import LayoutDashboard from '@lucide/svelte/icons/layout-dashboard';
  import type { Snippet } from 'svelte';
  import AdminSidebarLayout from '@/layouts/admin/admin-sidebar-layout.svelte';
  import type { AdminSidebarEntry } from '@/types/admin-sidebar';
  import { FlatToast, ToastContainer } from 'svelte-toasts';

  const defaultMenu: AdminSidebarEntry[] = [
    { type: 'header', label: 'Menu Utama' },
    {
      label: 'Dasbor',
      icon: LayoutDashboard,
      href: '/dashboard',
      active: page.url.startsWith('/dashboard'),
    },
  ];

  let {
    menu = defaultMenu,
    userName = page.props.auth.user.name,
    userEmail = page.props.auth.user.email,
    children,
  }: {
    menu?: AdminSidebarEntry[];
    userName?: string;
    userEmail?: string;
    children?: Snippet;
  } = $props();
</script>

<AdminSidebarLayout {menu} {userName} {userEmail}>
  {@render children?.()}

  <ToastContainer let:data>
    <FlatToast {data} />
  </ToastContainer>
</AdminSidebarLayout>
