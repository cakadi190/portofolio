<script lang="ts">
  import type { MediaItem } from '@/types/media';

  let {
    open = $bindable(false),
    onSelect,
  }: { open?: boolean; onSelect?: (media: MediaItem) => void } = $props();
</script>

{#if open}
  <div data-testid="picker-stub">
    <button
      type="button"
      onclick={() =>
        onSelect?.({ path: 'media/new.webp', name: 'new' } as MediaItem)}
    >
      pick-new
    </button>
    <button type="button" onclick={() => (open = false)}>close-picker</button>
  </div>
{/if}
