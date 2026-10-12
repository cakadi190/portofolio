<script lang="ts">
  import type { Snippet } from 'svelte';

  let { children }: { children?: Snippet } = $props();
</script>

<div class="resume-layout">
  {@render children?.()}
</div>

<style>
  .resume-layout {
    height: 100dvh;
    overflow: hidden;
  }
</style>
