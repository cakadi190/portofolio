<script lang="ts">
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    let {
        children,
        id,
        class: className = '',
        ...rest
    }: { children?: Snippet; id?: string; class?: string; [key: string]: unknown } = $props();
</script>

<h1 class={cn('modal-title fs-5', className)} {id} {...rest}>
    {@render children?.()}
</h1>
