<script lang="ts">
  import type { Snippet } from 'svelte';
  import AppLayout from '@/layouts/app/app-sidebar-layout.svelte';
  import type { BreadcrumbItem } from '@/types';
  import { Toaster } from 'svelte-french-toast';
  import { currentLocale } from '@/lib/i18n';

  let {
    breadcrumbs = [],
    children,
  }: {
    breadcrumbs?: BreadcrumbItem[];
    children?: Snippet;
  } = $props();
</script>

<AppLayout {breadcrumbs}>
  <!-- A language switch is an Inertia visit to the same component: remount the page so text it computed once is rebuilt. -->
  {#key currentLocale()}
    {@render children?.()}
  {/key}

  <Toaster position="top-right" toastOptions={{ duration: 4000 }} />
</AppLayout>
