<script module lang="ts">
  export const layout = {
    title: 'Masuk ke akun Anda',
    description: 'Masukkan email dan kata sandi Anda di bawah untuk masuk',
  };
</script>

<script lang="ts">
  import { Form } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import InputError from '@/components/input-error.svelte';
  import TextLink from '@/components/text-link.svelte';
  import { Input } from '@/components/ui/input';
  import { register } from '@/routes';
  import { store } from '@/routes/login';
  import { request } from '@/routes/password';

  let {
    status = '',
    canResetPassword,
  }: {
    status?: string;
    canResetPassword: boolean;
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
>
  {#snippet children({ errors, processing })}
    <div class="d-flex flex-column gap-3">
      <div class="d-flex flex-column gap-2">
        <label class="form-label" for="email">Alamat email</label>
        <Input
          id="email"
          type="email"
          name="email"
          required
          autocomplete="email"
          placeholder="email@contoh.com"
        />
        <InputError message={errors.email} />
      </div>

      <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-center justify-content-between">
          <label class="form-label" for="password">Kata sandi</label>
          {#if canResetPassword}
            <TextLink href={request()} class="small">
              Lupa kata sandi Anda?
            </TextLink>
          {/if}
        </div>
        <Input.Password
          id="password"
          name="password"
          required
          autocomplete="current-password"
          placeholder="Kata sandi"
        />
        <InputError message={errors.password} />
      </div>

      <div class="form-check">
        <input
          class="form-check-input"
          type="checkbox"
          id="remember"
          name="remember"
        />
        <label class="form-check-label" for="remember"> Ingatkan saya </label>
      </div>

      <button
        type="submit"
        class="btn btn-primary w-100"
        disabled={processing}
        data-test="login-button"
      >
        Masuk
      </button>
    </div>

    <div class="text-center small text-muted">
      Belum punya akun?
      <TextLink href={register()}>Daftar</TextLink>
    </div>
  {/snippet}
</Form>
