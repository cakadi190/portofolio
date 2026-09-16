<script module lang="ts">
    export const layout = {
        title: 'Forgot password',
        description: 'Enter your email to receive a password reset link',
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/app-head.svelte';
    import InputError from '@/components/input-error.svelte';
    import TextLink from '@/components/text-link.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { login } from '@/routes';
    import { email } from '@/routes/password';

    let {
        status = '',
    }: {
        status?: string;
    } = $props();
</script>

<AppHead title="Forgot password" />

{#if status}
    <div class="mb-4 text-center small fw-medium text-success">
        {status}
    </div>
{/if}

<div class="d-flex flex-column gap-4">
    <Form {...email.form()}>
        {#snippet children({ errors, processing })}
            <div class="d-flex flex-column gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="off"
                    placeholder="email@example.com"
                />
                <InputError message={errors.email} />
            </div>

            <div class="my-4 d-flex align-items-center justify-content-start">
                <Button
                    type="submit"
                    class="w-100"
                    disabled={processing}
                    data-test="email-password-reset-link-button"
                >
                    {#if processing}<Spinner />{/if}
                    Email password reset link
                </Button>
            </div>
        {/snippet}
    </Form>

    <div class="text-center small text-muted">
        <span>Or, return to</span>
        <TextLink href={login()}>log in</TextLink>
    </div>
</div>
