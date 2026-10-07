<script module lang="ts">
  export const PDF_ZOOM_MIN = 0.5;
  export const PDF_ZOOM_MAX = 4;
  export const PDF_ZOOM_STEP = 0.25;
</script>

<script lang="ts">
  let {
    url,
    title,
    zoom = $bindable(1),
    onclose,
  }: {
    url: string;
    title?: string;
    zoom?: number;
    onclose?: () => void;
  } = $props();
</script>

<div data-testid="pdf-stub" data-zoom={zoom}>
  {title}: {url}
  <button type="button" onclick={() => onclose?.()}>tutup-pdf</button>
</div>
