<script lang="ts">
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    /**
     * `down`/`up`/`start`/`end` map to Bootstrap's `.dropdown`/`.dropup`/
     * `.dropstart`/`.dropend` wrapper classes — see
     * https://getbootstrap.com/docs/5.3/components/dropdowns/#directions
     */
    type Direction = 'down' | 'up' | 'start' | 'end';

    const directionClass: Record<Direction, string> = {
        down: 'dropdown',
        up: 'dropup',
        start: 'dropstart',
        end: 'dropend',
    };

    let {
        children,
        direction = 'down',
        centered = false,
        class: className = '',
        ...rest
    }: {
        children?: Snippet;
        /** Which side of the toggle the menu opens on. */
        direction?: Direction;
        /**
         * Centers the menu under (or above, for `direction="up"`) the toggle
         * instead of aligning to its start edge — Bootstrap's
         * `.dropdown-center` / `.dropup-center` modifier. Only meaningful for
         * `direction="down"` and `direction="up"`.
         */
        centered?: boolean;
        class?: string;
        [key: string]: unknown;
    } = $props();

    const centeredClass = (): string | undefined => {
        if (!centered) {
            return undefined;
        }

        return direction === 'up' ? 'dropup-center' : 'dropdown-center';
    };
</script>

<div class={cn(directionClass[direction], centeredClass(), className)} {...rest}>
    {@render children?.()}
</div>
