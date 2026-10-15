<script lang="ts">
  import type { Snippet } from 'svelte';
  import type { AdminSidebarEntry } from '@/types/admin-sidebar';

  // The admin shell (sidebar, menu, admin routes, scrollbars) is ~115 KB that
  // public visitors never use, so it is split out of the entry bundle and only
  // fetched when an admin page is rendered.
  const content = import('@/layouts/admin/admin-layout-content.svelte');

  let {
    children,
    ...props
  }: {
    menu?: AdminSidebarEntry[];
    userName?: string;
    userEmail?: string;
    children?: Snippet;
  } = $props();
</script>

{#await content then { default: AdminLayoutContent }}
  <AdminLayoutContent {...props}>
    {@render children?.()}
  </AdminLayoutContent>
{/await}
