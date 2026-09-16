<script lang="ts">
    import type { Snippet } from 'svelte';
    import type { Tooltip as BsTooltip } from 'bootstrap';
    import { cn } from '@/lib/utils';

    type Placement = 'top' | 'right' | 'bottom' | 'left' | 'auto';

    let {
        children,
        text,
        placement = 'top',
        as = 'span',
        class: className = '',
        ...rest
    }: {
        children?: Snippet;
        text: string;
        placement?: Placement;
        as?: string;
        class?: string;
        [key: string]: unknown;
    } = $props();

    let el: HTMLElement | undefined = $state();
    let instance: BsTooltip | undefined;

    // Mirrors Batamtix Core's `initTooltip()` (`resources/js/libs/bootstrap.ts`),
    // scoped to this component's own element instead of a data-attribute scan,
    // so the instance is created and disposed alongside the Svelte component
    // lifecycle — no re-init needed after an Inertia navigation.
    $effect(() => {
        if (!el) {
            return;
        }

        let disposed = false;
        const node = el;

        void import('bootstrap').then(({ Tooltip }) => {
            if (disposed) {
                return;
            }

            instance = Tooltip.getOrCreateInstance(node, {
                title: text,
                placement,
            });
        });

        return () => {
            disposed = true;
            instance?.dispose();
            instance = undefined;
        };
    });

    // Keeps the tooltip's text in sync if `text` changes after mount, without
    // tearing down and recreating the Popper instance.
    $effect(() => {
        instance?.setContent({ '.tooltip-inner': text });
    });
</script>

<svelte:element this={as} bind:this={el} class={cn(className)} {...rest}>
    {@render children?.()}
</svelte:element>
