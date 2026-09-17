<script lang="ts">
  import type { HTMLInputAttributes } from 'svelte/elements';
  import type { Snippet } from 'svelte';
  import { cn } from '@/lib/utils';

  let {
    class: className = '',
    checked = $bindable(),
    invalid = false,
    inline = false,
    switchStyle = false,
    label,
    children,
    wrapperClass = '',
    ...rest
  }: HTMLInputAttributes & {
    invalid?: boolean;
    inline?: boolean;
    switchStyle?: boolean;
    label?: Snippet;
    children?: Snippet;
    wrapperClass?: string;
  } = $props();
</script>

<div
  class={cn(
    'form-check',
    inline && 'form-check-inline',
    switchStyle && 'form-switch',
    wrapperClass,
  )}
>
  <input
    type="checkbox"
    bind:checked
    class={cn('form-check-input', invalid && 'is-invalid', className)}
    {...rest}
  />
  {#if label || children}
    <label class="form-check-label" for={rest.id}>
      {@render (label ?? children)?.()}
    </label>
  {/if}
</div>
