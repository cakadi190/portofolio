<script lang="ts">
  import type { HTMLAttributes } from 'svelte/elements';
  import { cn } from '@/lib/utils';

  let {
    message = '',
    class: className = '',
    ...rest
  }: HTMLAttributes<HTMLDivElement> & { message?: string } = $props();
</script>

{#if message}
  <div class={cn('invalid-feedback mt-0 d-block', className)} {...rest}>
    {message}
  </div>
{/if}
