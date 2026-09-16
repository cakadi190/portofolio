<script lang="ts">
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    let {
        children,
        class: className = '',
        ...rest
    }: { children?: Snippet; class?: string; [key: string]: unknown } = $props();
</script>

<li>
    <h6 class={cn('dropdown-header', className)} {...rest}>
        {@render children?.()}
    </h6>
</li>
