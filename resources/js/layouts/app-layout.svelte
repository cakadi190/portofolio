<script lang="ts">
  import type { Snippet } from 'svelte';
  import AppLayout from '@/layouts/app/app-sidebar-layout.svelte';
  import type { BreadcrumbItem } from '@/types';
  import { Toaster } from 'svelte-french-toast';

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

  <Toaster position="top-right" toastOptions={{ duration: 4000 }} />
</AppLayout>
