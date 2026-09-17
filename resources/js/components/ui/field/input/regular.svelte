<script lang="ts">
  import type { HTMLInputAttributes } from 'svelte/elements';
  import { cn } from '@/lib/utils';

  let {
    class: className = '',
    type = 'text',
    value = $bindable(),
    ...rest
  }: HTMLInputAttributes = $props();
</script>

<input {type} bind:value class={cn('form-control', className)} {...rest} />
