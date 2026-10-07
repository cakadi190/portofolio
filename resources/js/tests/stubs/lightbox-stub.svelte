<script lang="ts">
  let {
    open = $bindable(false),
    index = $bindable(0),
  }: { open?: boolean; index?: number; images?: unknown[] } = $props();
</script>

{#if open}
  <div data-testid="lightbox-stub">{index}</div>
{/if}
