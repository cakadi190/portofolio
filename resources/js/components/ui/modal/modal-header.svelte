<script lang="ts">
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    let {
        children,
        onclose,
        class: className = '',
        ...rest
    }: {
        children?: Snippet;
        /** Renders Bootstrap's `.btn-close`; omit to leave the header dismiss-free. */
        onclose?: () => void;
        class?: string;
        [key: string]: unknown;
    } = $props();
</script>

<div class={cn('modal-header', className)} {...rest}>
    {@render children?.()}
    {#if onclose}
        <button
            type="button"
            class="btn-close"
            aria-label="Close"
            data-bs-dismiss="modal"
            onclick={onclose}
        ></button>
    {/if}
</div>
