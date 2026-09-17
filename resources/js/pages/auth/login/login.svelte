<script module lang="ts">
  export const layout = {
    title: 'Masuk ke akun Anda',
    description: 'Masukkan email dan kata sandi Anda di bawah untuk masuk',
  };
</script>

<script lang="ts">
  import { Form } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import TextLink from '@/components/text-link.svelte';
  import PasskeyLogin from '@/components/passkey-login.svelte';
  import { Field } from '@/components/ui/field';
  import { register } from '@/routes';
  import { store } from '@/routes/login';
  import { request } from '@/routes/password';
  import Separator from '@/components/ui/separator.svelte';

  let {
    status = '',
    canResetPassword,
    canUsePasskeys = false,
  }: {
    status?: string;
    canResetPassword: boolean;
    canUsePasskeys?: boolean;
  } = $props();
</script>

<AppHead title="Masuk" />

{#if status}
  <div class="mb-4 text-center small fw-medium text-success">
    {status}
  </div>
{/if}

<Form
  {...store.form()}
  resetOnSuccess={['password']}
  class="d-flex flex-column gap-3"
  novalidate
>
  {#snippet children({ errors, processing })}
    <div class="d-flex flex-column gap-3">
      <Field.Group>
        <Field.Label for="email">Alamat email</Field.Label>
        <Field.Input
          id="email"
          type="email"
          name="email"
          required
          autocomplete="email"
          placeholder="email@contoh.com"
          invalid={!!errors.email}
        />
        <Field.Feedback message={errors.email} />
      </Field.Group>

      <Field.Group>
        <Field.Row>
          <Field.Label for="password">Kata sandi</Field.Label>
          {#if canResetPassword}
            <TextLink href={request()} class="small">
              Lupa kata sandi Anda?
            </TextLink>
          {/if}
        </Field.Row>
        <Field.Input.Password
          id="password"
          name="password"
          required
          autocomplete="current-password"
          placeholder="Kata sandi"
          invalid={!!errors.password}
        />
        <Field.Feedback message={errors.password} />
      </Field.Group>

      <Field.Input.Check id="remember" name="remember">
        Ingatkan saya
      </Field.Input.Check>

      <button
        type="submit"
        class="btn btn-primary w-100"
        disabled={processing}
        data-test="login-button"
      >
        Masuk
      </button>

      {#if canUsePasskeys}
        <Separator>Atau</Separator>

        <PasskeyLogin />
      {/if}
    </div>

    <div class="text-center small text-muted">
      Belum punya akun?
      <TextLink href={register()}>Daftar</TextLink>
    </div>
  {/snippet}
</Form>
