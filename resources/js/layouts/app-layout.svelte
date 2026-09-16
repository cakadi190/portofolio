<script lang="ts">
  import type { Snippet } from 'svelte';
  import AppLayout from '@/layouts/app/app-sidebar-layout.svelte';
  import type { BreadcrumbItem } from '@/types';
  import { FlatToast, ToastContainer } from 'svelte-toasts';

  let {
    breadcrumbs = [],
    children,
  }: {
    breadcrumbs?: BreadcrumbItem[];
    children?: Snippet;
  } = $props();
</script>

<AppLayout {breadcrumbs}>
  {@render children?.()}

  <ToastContainer let:data>
    <FlatToast {data} />
  </ToastContainer>
</AppLayout>
