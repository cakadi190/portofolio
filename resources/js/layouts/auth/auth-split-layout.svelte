<script lang="ts">
  import { Link } from '@inertiajs/svelte';
  import type { Snippet } from 'svelte';
  import AppLogoIcon from '@/components/app-logo-icon.svelte';
  import { home } from '@/routes';

  let {
    title = '',
    description = '',
    children,
  }: {
    title?: string;
    description?: string;
    children?: Snippet;
  } = $props();
</script>

<div class="auth-layout auth-layout-split"></div>

<style lang="scss">
</style>
