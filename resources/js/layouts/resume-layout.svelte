<script lang="ts">
  import { onMount, type Snippet } from 'svelte';

  let { children }: { children?: Snippet } = $props();

  // The anti-FOUC <style> in app.blade.php locks html/body scrolling for the whole
  // document; hand that over to this layout so leaving the viewer restores scrolling.
  onMount(() => {
    document.getElementById('pdf-viewer-preboot')?.remove();

    const { documentElement, body } = document;
    const previous = [documentElement.style.overflow, body.style.overflow];
    documentElement.style.overflow = 'hidden';
    body.style.overflow = 'hidden';

    return () => {
      documentElement.style.overflow = previous[0];
      body.style.overflow = previous[1];
    };
  });
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
