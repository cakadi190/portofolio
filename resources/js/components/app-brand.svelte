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
    linked = true,
  }: {
    href?: string;
    class?: string;
    height?: number | string;
    variant?: 'auto' | 'color' | 'white';
    /** Render a plain span when the brand already sits inside a link. */
    linked?: boolean;
  } = $props();
</script>

{#snippet artwork()}
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
{/snippet}

{#if linked}
  <Link {href} class="d-inline-flex app-brand {linkClass}">
    {@render artwork()}
  </Link>
{:else}
  <span class="d-inline-flex app-brand {linkClass}">
    {@render artwork()}
  </span>
{/if}
