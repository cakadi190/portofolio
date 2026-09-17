<script lang="ts">
  import type { HTMLInputAttributes } from 'svelte/elements';
  import { cn } from '@/lib/utils';

  let {
    class: className = '',
    type = 'text',
    value = $bindable(),
    invalid = false,
    ...rest
  }: HTMLInputAttributes & { invalid?: boolean } = $props();
</script>

<input
  {type}
  bind:value
  class={cn('form-control', invalid && 'is-invalid', className)}
  {...rest}
/>
