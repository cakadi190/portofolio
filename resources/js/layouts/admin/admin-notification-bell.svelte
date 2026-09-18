<script lang="ts">
  import Bell from '@lucide/svelte/icons/bell';

  let { count = 0 }: { count?: number } = $props();
</script>

<button
  type="button"
  class="nav-link square position-relative"
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
