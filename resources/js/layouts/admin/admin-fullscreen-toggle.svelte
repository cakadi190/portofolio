<script lang="ts">
  import Maximize from '@lucide/svelte/icons/maximize';
  import Minimize from '@lucide/svelte/icons/minimize';

  let isFullscreen = $state(false);
  let supported = $state(true);

  $effect(() => {
    supported = document.fullscreenEnabled ?? false;

    const sync = () => {
      isFullscreen = document.fullscreenElement !== null;
    };

    sync();
    document.addEventListener('fullscreenchange', sync);

    return () => document.removeEventListener('fullscreenchange', sync);
  });

  function toggle(): void {
    const action = isFullscreen
      ? document.exitFullscreen()
      : document.documentElement.requestFullscreen();

    action.catch((error: unknown) => {
      console.error('[admin-fullscreen-toggle] toggle failed:', error);
    });
  }
</script>

{#if supported}
  <button
    type="button"
    class="nav-link nav-fs"
    aria-label={isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen'}
    aria-pressed={isFullscreen}
    onclick={toggle}
  >
    {#if isFullscreen}
      <Minimize size={16} aria-hidden="true" />
    {:else}
      <Maximize size={16} aria-hidden="true" />
    {/if}
  </button>
{/if}
