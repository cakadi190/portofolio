<script lang="ts">
    import { cn } from '@/lib/utils';
    import LoaderCircle from '@lucide/svelte/icons/loader-circle';

    let {
        class: className = '',
        ...rest
    }: {
        class?: string;
        [key: string]: unknown;
    } = $props();
</script>

<LoaderCircle
    class={cn(className)}
    style="animation: spin 1s linear infinite;"
    {...rest}
/>
