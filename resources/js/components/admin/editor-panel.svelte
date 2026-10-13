<script lang="ts">
  import type { Snippet } from 'svelte';

  /** Collapsible settings panel for the editor sidebar. */
  let {
    title,
    open = true,
    children,
  }: { title: string; open?: boolean; children?: Snippet } = $props();
</script>

<details class="gb-panel" {open}>
  <summary>{title}</summary>
  <div class="gb-panel-body">
    {@render children?.()}
  </div>
</details>
