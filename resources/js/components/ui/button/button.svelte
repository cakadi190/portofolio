<script lang="ts">
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    type Variant =
        | 'default'
        | 'secondary'
        | 'ghost'
        | 'destructive'
        | 'outline'
        | 'link';
    type Size = 'default' | 'sm' | 'lg' | 'icon';
    type AsChildProps = {
        class?: string;
        onClick?: (event: MouseEvent) => void;
        [key: string]: any;
    };

    const base = 'btn d-inline-flex align-items-center justify-content-center gap-2';

    const variants: Record<Variant, string> = {
        default: 'btn-primary',
        secondary: 'btn-secondary',
        ghost: 'btn-ghost',
        destructive: 'btn-danger',
        outline: 'btn-outline-secondary',
        link: 'btn-link',
    };

    const sizes: Record<Size, string> = {
        default: '',
        sm: 'btn-sm',
        lg: 'btn-lg',
        icon: 'btn-sm p-2',
    };

    let {
        children,
        asChild = false,
        variant = 'default',
        size = 'default',
        class: className = '',
        type = 'button',
        ...rest
    }: {
        children?: Snippet<[AsChildProps]>;
        asChild?: boolean;
        variant?: Variant;
        size?: Size;
        class?: string;
        type?: 'button' | 'submit' | 'reset';
        [key: string]: unknown;
    } = $props();

    const classes = () => cn(base, variants[variant], sizes[size], className);
</script>

{#if asChild}
    {@render children?.({ class: classes(), ...rest })}
{:else}
    <button class={classes()} type={type} {...rest}>
        {@render children?.({})}
    </button>
{/if}
