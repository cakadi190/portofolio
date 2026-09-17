<script module lang="ts">
  export const layout = {
    title: 'Atur ulang kata sandi',
    description: 'Silakan masukkan kata sandi baru Anda di bawah ini',
  };
</script>

<script lang="ts">
  import { Form } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import { Field } from '@/components/ui/field';
  import { update } from '@/routes/password';

  let {
    token,
    email,
    passwordRules,
  }: {
    token: string;
    email: string;
    passwordRules: string;
  } = $props();
</script>

<AppHead title="Atur ulang kata sandi" />

<Form
  {...update.form()}
  transform={(data) => ({ ...data, token, email })}
  resetOnSuccess={['password', 'password_confirmation']}
  novalidate
>
  {#snippet children({ errors, processing })}
    <div class="d-flex flex-column gap-3">
      <Field.Group>
        <Field.Label for="email">Email</Field.Label>
        <Field.Input
          id="email"
          type="email"
          name="email"
          autocomplete="email"
          value={email}
          class="mt-1 d-block w-100"
          readonly
          invalid={!!errors.email}
        />
        <Field.Feedback message={errors.email} class="mt-2" />
      </Field.Group>

      <Field.Group>
        <Field.Label for="password">Kata sandi</Field.Label>
        <Field.Input.Password
          id="password"
          name="password"
          autocomplete="new-password"
          class="mt-1 d-block w-100"
          placeholder="Kata sandi"
          passwordrules={passwordRules}
          invalid={!!errors.password}
        />
        <Field.Feedback message={errors.password} />
      </Field.Group>

      <Field.Group>
        <Field.Label for="password_confirmation"
          >Konfirmasi kata sandi</Field.Label
        >
        <Field.Input.Password
          id="password_confirmation"
          name="password_confirmation"
          autocomplete="new-password"
          class="mt-1 d-block w-100"
          placeholder="Konfirmasi kata sandi"
          passwordrules={passwordRules}
          invalid={!!errors.password_confirmation}
        />
        <Field.Feedback message={errors.password_confirmation} />
      </Field.Group>

      <button
        type="submit"
        class="btn btn-primary mt-3 w-100"
        disabled={processing}
        data-test="reset-password-button"
      >
        Atur ulang kata sandi
      </button>
    </div>
  {/snippet}
</Form>
