<script lang="ts">
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    /** Bootstrap's `.dropdown-menu-start` (default) / `-end` / `-center` alignment. */
    type Align = 'start' | 'end' | 'center';

    const alignClass: Record<Align, string | undefined> = {
        start: undefined,
        end: 'dropdown-menu-end',
        center: 'dropdown-menu-center',
    };

    let {
        children,
        align = 'start',
        labelledby,
        class: className = '',
        ...rest
    }: {
        children?: Snippet;
        align?: Align;
        /** `id` of the trigger this menu belongs to, for `aria-labelledby`. */
        labelledby?: string;
        class?: string;
        [key: string]: unknown;
    } = $props();
</script>

<ul class={cn('dropdown-menu', alignClass[align], className)} aria-labelledby={labelledby} {...rest}>
    {@render children?.()}
</ul>
