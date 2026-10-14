<script lang="ts">
  import Bell from '@lucide/svelte/icons/bell';
  import BellOff from '@lucide/svelte/icons/bell-off';
  import { onMount } from 'svelte';
  import { portal } from '@/lib/dom';

  let { count = 0 }: { count?: number } = $props();

  // The panel is portaled to <body> by JS, so rendering it during SSR only
  // leaks its text into the navbar until hydration (FOUC).
  let mounted = $state(false);

  onMount(() => {
    mounted = true;
  });
</script>

<button
  type="button"
  class="nav-link square position-relative"
  data-bs-toggle="offcanvas"
  data-bs-target="#adminNotificationOffcanvas"
  aria-controls="adminNotificationOffcanvas"
  aria-label="Notifikasi"
>
  <Bell size={16} aria-hidden="true" />

  {#if count > 0}
    <span
      class="position-absolute badge rounded-pill bg-danger notification-badge end-0 top-0"
    >
      {count}
      <span class="visually-hidden">notifikasi belum dibaca</span>
    </span>
  {/if}
</button>

{#if mounted}
<div
  use:portal
  class="offcanvas offcanvas-end notification-offcanvas"
  tabindex="-1"
  id="adminNotificationOffcanvas"
  aria-labelledby="adminNotificationOffcanvasLabel"
>
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title" id="adminNotificationOffcanvasLabel">
      Notifikasi
    </h5>
    <button
      type="button"
      class="btn-close"
      data-bs-dismiss="offcanvas"
      aria-label="Tutup"
    ></button>
  </div>
  <div class="offcanvas-body p-0">
    <div
      class="d-flex flex-column align-items-center justify-content-center text-body-secondary h-100 gap-2 p-4 text-center"
    >
      <BellOff size={32} aria-hidden="true" />
      <span>Belum ada notifikasi.</span>
    </div>
  </div>
</div>
{/if}
