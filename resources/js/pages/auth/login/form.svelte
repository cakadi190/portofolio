<script lang="ts">
  import Turnstile from '@/components/turnstile.svelte';
  import TextLink from '@/components/text-link.svelte';
  import { Field } from '@/components/ui/field';
  import { store } from '@/wayfinder/routes/login';
  import { request } from '@/wayfinder/routes/password';
  import { Form } from '@inertiajs/svelte';

  let {
    canResetPassword,
  }: {
    canResetPassword: boolean;
  } = $props();
</script>

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

      <Turnstile error={errors['cf-turnstile-response']} />

      <button
        type="submit"
        class="btn btn-primary w-100"
        disabled={processing}
        data-test="login-button"
      >
        Masuk
      </button>
    </div>
  {/snippet}
</Form>
