<script lang="ts">
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    let {
        children,
        class: className = '',
        ...rest
    }: { children?: Snippet; class?: string; [key: string]: unknown } = $props();
</script>

<div class={cn('modal-footer', className)} {...rest}>
    {@render children?.()}
</div>
