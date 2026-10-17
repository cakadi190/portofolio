<script lang="ts">
  import type { MediaItem } from '@/types/media';

  let {
    open = $bindable(false),
    onSelect,
    onSelectMultiple,
  }: {
    open?: boolean;
    onSelect?: (media: MediaItem) => void;
    onSelectMultiple?: (items: MediaItem[]) => void;
  } = $props();
</script>

{#if open}
  <div data-testid="picker-stub">
    <button
      type="button"
      onclick={() => {
        const picked = { path: 'media/new.webp', name: 'new' } as MediaItem;

        onSelect?.(picked);
        onSelectMultiple?.([picked]);
      }}
    >
      pick-new
    </button>
    <button type="button" onclick={() => (open = false)}>close-picker</button>
  </div>
{/if}
