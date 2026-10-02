<script lang="ts">
  import type { Snippet } from 'svelte';

  let {
    nav,
    children,
  }: {
    nav: Snippet;
    children: Snippet;
  } = $props();
</script>

<div class="row g-4">
  <aside class="col-lg-3 col-xl-2">
    <div
      class="nav nav-pills flex-lg-column flex-nowrap overflow-x-auto settings-nav"
      role="tablist"
    >
      {@render nav()}
    </div>
  </aside>

  <div class="col-lg-9 col-xl-10">
    {@render children()}
  </div>
</div>

<style>
  .settings-nav {
    scrollbar-width: none;
    -ms-overflow-style: none;
  }

  .settings-nav::-webkit-scrollbar {
    display: none;
  }

  .settings-nav :global(.nav-link) {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    white-space: nowrap;
    text-align: start;
  }
</style>
