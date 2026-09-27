<script lang="ts">
  import type { Snippet } from 'svelte';
  import BackToTop from '@/components/back-to-top.svelte';
  import SiteFooter from '@/components/site-footer.svelte';
  import SiteNavbar from '@/components/site-navbar.svelte';
  import type { BreadcrumbItem } from '@/types';

  let {
    children,
  }: {
    breadcrumbs?: BreadcrumbItem[];
    children?: Snippet;
  } = $props();
</script>

<div id="site-shell">
  <SiteNavbar />

  {@render children?.()}

  <SiteFooter />
  <BackToTop />
</div>
