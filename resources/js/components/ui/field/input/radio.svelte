<script lang="ts">
  import type { HTMLInputAttributes } from 'svelte/elements';
  import type { Snippet } from 'svelte';
  import { cn } from '@/lib/utils';

  let {
    class: className = '',
    group = $bindable(),
    invalid = false,
    inline = false,
    label,
    children,
    wrapperClass = '',
    ...rest
  }: Omit<HTMLInputAttributes, 'checked'> & {
    group?: HTMLInputAttributes['value'];
    invalid?: boolean;
    inline?: boolean;
    label?: Snippet;
    children?: Snippet;
    wrapperClass?: string;
  } = $props();
</script>

<div class={cn('form-check', inline && 'form-check-inline', wrapperClass)}>
  <input
    type="radio"
    bind:group
    class={cn('form-check-input', invalid && 'is-invalid', className)}
    {...rest}
  />
  {#if label || children}
    <label class="form-check-label" for={rest.id}>
      {@render (label ?? children)?.()}
    </label>
  {/if}
</div>
