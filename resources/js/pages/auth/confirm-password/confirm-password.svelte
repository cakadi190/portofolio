<script module lang="ts">
  export const layout = {
    title: 'Konfirmasi kata sandi',
    description:
      'Ini adalah area yang aman. Silakan konfirmasi kata sandi Anda sebelum melanjutkan',
  };
</script>

<script lang="ts">
  import { Form } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import { Field } from '@/components/ui/field';
  import { store } from '@/wayfinder/routes/password/confirm';
</script>

<AppHead title="Konfirmasi kata sandi" />

<Form {...store.form()} resetOnSuccess={['password']} novalidate>
  {#snippet children({ errors, processing })}
    <div class="d-flex flex-column gap-3">
      <Field.Group>
        <Field.Label for="password">Kata sandi</Field.Label>
        <Field.Input.Password
          id="password"
          name="password"
          autocomplete="current-password"
          autofocus
          invalid={!!errors.password}
        />
        <Field.Feedback message={errors.password} />
      </Field.Group>

      <button
        type="submit"
        class="btn btn-primary w-100"
        disabled={processing}
        data-test="confirm-password-button"
      >
        Konfirmasi kata sandi
      </button>
    </div>
  {/snippet}
</Form>
