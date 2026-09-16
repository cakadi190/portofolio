<script lang="ts">
  let {
    message = '',
    class: className = '',
  }: {
    message?: string;
    class?: string;
  } = $props();
</script>

{#if message}
  <div class={className}>
    <p class="small text-danger mb-0">{message}</p>
  </div>
{/if}
