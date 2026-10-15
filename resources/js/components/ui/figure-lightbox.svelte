<script lang="ts">
  import Lightbox from '@/components/ui/lightbox.svelte';

  /**
   * Mounts the shared lightbox for a published figure (outside Svelte's
   * template, so it owns its open state) and reports once it has closed.
   */
  let { src, alt, onclose }: { src: string; alt: string; onclose: () => void } =
    $props();

  let open = $state(true);

  $effect(() => {
    if (!open) onclose();
  });
</script>

<Lightbox bind:open {src} {alt} />
