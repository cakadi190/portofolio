<script lang="ts">
  let { label, color }: { label: string; color: string } = $props();
</script>

<span class={`badge text-bg-${color}`}>{label}</span>
