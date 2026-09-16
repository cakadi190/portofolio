<script lang="ts">
    import type { Snippet } from 'svelte';
    import type { BreadcrumbItem } from '@/types';

    let {
        breadcrumbs = [],
        children,
    }: {
        breadcrumbs?: BreadcrumbItem[];
        children?: Snippet;
    } = $props();

    void breadcrumbs;
</script>

{@render children?.()}
