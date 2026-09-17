<script module lang="ts">
  export const layout = {
    title: 'Buat akun',
    description: 'Masukkan detail Anda di bawah untuk membuat akun',
  };
</script>

<script lang="ts">
  import { Form } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import TextLink from '@/components/text-link.svelte';
  import { Field } from '@/components/ui/field';
  import { login } from '@/routes';
  import { store } from '@/routes/register';

  let { passwordRules }: { passwordRules: string } = $props();
</script>

<AppHead title="Daftar" />

<Form
  {...store.form()}
  resetOnSuccess={['password', 'password_confirmation']}
  class="d-flex flex-column gap-4"
  novalidate
>
  {#snippet children({ errors, processing })}
    <div class="d-flex flex-column gap-3">
      <Field.Group>
        <Field.Label for="name">Nama</Field.Label>
        <Field.Input
          id="name"
          type="text"
          required
          autocomplete="name"
          name="name"
          placeholder="Nama lengkap"
          invalid={!!errors.name}
        />
        <Field.Feedback message={errors.name} />
      </Field.Group>

      <Field.Group>
        <Field.Label for="email">Alamat email</Field.Label>
        <Field.Input
          id="email"
          type="email"
          required
          autocomplete="email"
          name="email"
          placeholder="email@contoh.com"
          invalid={!!errors.email}
        />
        <Field.Feedback message={errors.email} />
      </Field.Group>

      <Field.Group>
        <Field.Label for="password">Kata sandi</Field.Label>
        <Field.Input.Password
          confirmed="password_confirmation"
          id="password"
          required
          autocomplete="new-password"
          name="password"
          placeholder="Kata sandi"
          passwordrules={passwordRules}
          invalid={!!errors.password}
        />
        <Field.Feedback message={errors.password} />
      </Field.Group>

      <!-- <Field.Group>
        <Field.Label for="password_confirmation"
          >Konfirmasi kata sandi</Field.Label
        >
        <Field.Input.Password
          id="password_confirmation"
          required
          autocomplete="new-password"
          name="password_confirmation"
          placeholder="Konfirmasi kata sandi"
          passwordrules={passwordRules}
          invalid={!!errors.password_confirmation}
        />
        <Field.Feedback message={errors.password_confirmation} />
      </Field.Group> -->

      <button
        type="submit"
        class="btn btn-primary mt-2 w-100"
        disabled={processing}
        data-test="register-user-button"
      >
        Buat akun
      </button>
    </div>

    <div class="text-center small text-muted">
      Sudah punya akun?
      <TextLink href={login()} class="text-decoration-underline">
        Masuk
      </TextLink>
    </div>
  {/snippet}
</Form>
