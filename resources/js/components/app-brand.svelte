<script lang="ts">
  import { Link } from '@inertiajs/svelte';

  /**
   * `auto` swaps between the colored and white wordmark with the theme;
   * `color` and `white` pin one wordmark (e.g. `white` on a dark, fixed surface).
   */
  let {
    href = '/',
    class: linkClass = '',
    height = 32,
    variant = 'auto',
  }: {
    href?: string;
    class?: string;
    height?: number | string;
    variant?: 'auto' | 'color' | 'white';
  } = $props();
</script>

<Link {href} class="d-inline-flex app-brand {linkClass}">
  {#if variant !== 'white'}
    <img
      class="site-logo logo-light"
      class:logo-fixed={variant === 'color'}
      src="/images/brands/logo-color.svg"
      alt="Logo Catatan Cak Adi"
      {height}
      style="aspect-ratio: 765.71 / 166"
      decoding="sync"
    />
  {/if}
  {#if variant !== 'color'}
    <img
      class="site-logo logo-dark"
      class:logo-fixed={variant === 'white'}
      src="/images/brands/logo-white.svg"
      alt="Logo Catatan Cak Adi"
      {height}
      style="aspect-ratio: 765.71 / 166"
      decoding="sync"
    />
  {/if}
</Link>
