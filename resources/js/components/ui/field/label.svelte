<script lang="ts">
  import type { HTMLLabelAttributes } from 'svelte/elements';
  import type { Snippet } from 'svelte';
  import { cn } from '@/lib/utils';

  let {
    class: className = '',
    children,
    ...rest
  }: HTMLLabelAttributes & { children?: Snippet } = $props();
</script>

<label class={cn('form-label mb-0', className)} {...rest}>
  {@render children?.()}
</label>
