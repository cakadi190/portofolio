<script lang="ts">
  import type { HTMLAttributes } from 'svelte/elements';
  import type { Snippet } from 'svelte';
  import { cn } from '@/lib/utils';

  let {
    position = 'center',
    class: className = '',
    children,
    ...rest
  }: HTMLAttributes<HTMLDivElement> & {
    position?: 'start' | 'center' | 'end';
    children?: Snippet;
  } = $props();
</script>

<div
  class={cn('d-flex align-items-center gap-2 text-muted small', className)}
  {...rest}
>
  {#if position !== 'start'}
    <hr class="flex-grow-1" />
  {/if}
  {@render children?.()}
  {#if position !== 'end'}
    <hr class="flex-grow-1" />
  {/if}
</div>
