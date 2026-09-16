<script lang="ts">
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    let {
        children,
        href,
        active = false,
        disabled = false,
        destructive = false,
        class: className = '',
        ...rest
    }: {
        children?: Snippet;
        href?: string;
        active?: boolean;
        disabled?: boolean;
        destructive?: boolean;
        class?: string;
        [key: string]: unknown;
    } = $props();

    const classes = () =>
        cn(
            'dropdown-item',
            active && 'active',
            disabled && 'disabled',
            destructive && 'text-danger',
            className,
        );
</script>

{#if href}
    <li>
        <a {href} class={classes()} aria-current={active ? 'true' : undefined} {...rest}>
            {@render children?.()}
        </a>
    </li>
{:else}
    <li>
        <button type="button" class={classes()} {disabled} {...rest}>
            {@render children?.()}
        </button>
    </li>
{/if}
