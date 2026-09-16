<script lang="ts">
    import { cn } from '@/lib/utils';

    let {
        checked = $bindable(false),
        disabled = false,
        class: className = '',
        id,
        name,
        value,
        ...rest
    }: {
        checked?: boolean;
        disabled?: boolean;
        class?: string;
        id?: string;
        name?: string;
        value?: string;
        [key: string]: unknown;
    } = $props();
</script>

<input
    type="checkbox"
    class={cn('form-check-input', className)}
    {disabled}
    {id}
    {name}
    {value}
    bind:checked
    {...rest}
/>
