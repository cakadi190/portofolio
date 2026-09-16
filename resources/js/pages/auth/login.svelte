<script module lang="ts">
  export const layout = {
    title: "Log in to your account",
    description: "Enter your email and password below to log in",
  };
</script>

<script lang="ts">
  import { Form } from "@inertiajs/svelte";
  import AppHead from "@/components/app-head.svelte";
  import InputError from "@/components/input-error.svelte";
  import PasswordInput from "@/components/password-input.svelte";
  import TextLink from "@/components/text-link.svelte";
  import { Button } from "@/components/ui/button";
  import { Checkbox } from "@/components/ui/checkbox";
  import { Input } from "@/components/ui/input";
  import { Label } from "@/components/ui/label";
  import { Spinner } from "@/components/ui/spinner";
  import { register } from "@/routes";
  import { store } from "@/routes/login";
  import { request } from "@/routes/password";

  let {
    status = "",
    canResetPassword,
  }: {
    status?: string;
    canResetPassword: boolean;
  } = $props();
</script>

<AppHead title="Log in" />

{#if status}
  <div class="mb-4 text-center small fw-medium text-success">
    {status}
  </div>
{/if}

<Form
  {...store.form()}
  resetOnSuccess={["password"]}
  class="d-flex flex-column gap-4"
>
  {#snippet children({ errors, processing })}
    <div class="d-flex flex-column gap-4">
      <div class="d-flex flex-column gap-2">
        <Label for="email">Email address</Label>
        <Input
          id="email"
          type="email"
          name="email"
          required
          autocomplete="email"
          placeholder="email@example.com"
        />
        <InputError message={errors.email} />
      </div>

      <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-center justify-content-between">
          <Label for="password">Password</Label>
          {#if canResetPassword}
            <TextLink href={request()} class="small">
              Forgot your password?
            </TextLink>
          {/if}
        </div>
        <PasswordInput
          id="password"
          name="password"
          required
          autocomplete="current-password"
          placeholder="Password"
        />
        <InputError message={errors.password} />
      </div>

      <div class="d-flex align-items-center justify-content-between">
        <Label for="remember" class="d-flex align-items-center gap-3">
          <Checkbox id="remember" name="remember" />
          <span>Remember me</span>
        </Label>
      </div>

      <Button
        type="submit"
        class="mt-3 w-100"
        disabled={processing}
        data-test="login-button"
      >
        {#if processing}<Spinner />{/if}
        Log in
      </Button>
    </div>

    <div class="text-center small text-muted">
      Don't have an account?
      <TextLink href={register()}>Sign up</TextLink>
    </div>
  {/snippet}
</Form>
