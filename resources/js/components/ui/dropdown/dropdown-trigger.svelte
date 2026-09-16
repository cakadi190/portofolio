<script lang="ts">
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    type AsChildProps = {
        class?: string;
        'data-bs-toggle': 'dropdown';
        'aria-expanded': 'false';
        [key: string]: unknown;
    };

    let {
        children,
        asChild = false,
        id,
        uncaret = false,
        autoClose = true,
        class: className = '',
        ...rest
    }: {
        children?: Snippet<[AsChildProps]>;
        /** Renders the trigger's own markup via `children`, e.g. to reuse `<Button>`. */
        asChild?: boolean;
        id?: string;
        /** Hides Bootstrap's caret — `.dropdown-toggle::after`. */
        uncaret?: boolean;
        /**
         * `true`/`false` map to Bootstrap's default and `data-bs-auto-close="false"`;
         * `'inside'`/`'outside'` map to the other two `data-bs-auto-close` values.
         * See https://getbootstrap.com/docs/5.3/components/dropdowns/#auto-close-behavior
         */
        autoClose?: boolean | 'inside' | 'outside';
        class?: string;
        [key: string]: unknown;
    } = $props();

    const toggleProps = (): AsChildProps => ({
        class: cn('dropdown-toggle', uncaret && 'dropdown-uncaret', className),
        'data-bs-toggle': 'dropdown',
        'data-bs-auto-close': String(autoClose) as string,
        'aria-expanded': 'false',
        id,
    });
</script>

{#if asChild}
    {@render children?.(toggleProps())}
{:else}
    <button type="button" {...toggleProps()} {...rest}>
        {@render children?.(toggleProps())}
    </button>
{/if}
