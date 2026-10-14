<script lang="ts">
  import type { Snippet } from 'svelte';

  /** Renders the list only; there is no Inertia page to scroll in unit tests. */
  let { children }: { children?: Snippet } = $props();
</script>

{@render children?.()}
