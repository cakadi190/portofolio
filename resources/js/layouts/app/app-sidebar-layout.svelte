<script lang="ts">
    import type { Snippet } from 'svelte';
    import type { BreadcrumbItem } from '@/types';

    let {
        children,
    }: {
        breadcrumbs?: BreadcrumbItem[];
        children?: Snippet;
    } = $props();
</script>

{@render children?.()}
