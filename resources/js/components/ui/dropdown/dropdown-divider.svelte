<script lang="ts">
    import { cn } from '@/lib/utils';

    let { class: className = '', ...rest }: { class?: string; [key: string]: unknown } = $props();
</script>

<li><hr class={cn('dropdown-divider', className)} {...rest} /></li>
