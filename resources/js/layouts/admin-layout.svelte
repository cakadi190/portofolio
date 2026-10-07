<script lang="ts">
  import { page } from '@inertiajs/svelte';
  import type { Snippet } from 'svelte';
  import { adminMenu } from '@/layouts/admin/admin-menu';
  import AdminSidebarLayout from '@/layouts/admin/admin-sidebar-layout.svelte';
  import type { AdminSidebarEntry } from '@/types/admin-sidebar';
  import { FlatToast, ToastContainer } from 'svelte-toasts';

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

  <ToastContainer let:data>
    <FlatToast {data} />
  </ToastContainer>
</AdminSidebarLayout>
