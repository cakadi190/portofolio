<script module lang="ts">
  export const layout = {
    title: 'Lupa kata sandi',
    description:
      'Masukkan email Anda untuk menerima tautan atur ulang kata sandi',
  };
</script>

<script lang="ts">
  import { Form } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import TextLink from '@/components/text-link.svelte';
  import { Field } from '@/components/ui/field';
  import { login } from '@/routes';
  import { email } from '@/routes/password';

  let {
    status = '',
  }: {
    status?: string;
  } = $props();
</script>

<AppHead title="Lupa kata sandi" />

{#if status}
  <div class="mb-4 text-center small fw-medium text-success">
    {status}
  </div>
{/if}

<div class="d-flex flex-column gap-4">
  <Form {...email.form()} novalidate>
    {#snippet children({ errors, processing })}
      <Field.Group>
        <Field.Label for="email">Alamat email</Field.Label>
        <Field.Input
          id="email"
          type="email"
          name="email"
          autocomplete="off"
          placeholder="email@contoh.com"
          invalid={!!errors.email}
        />
        <Field.Feedback message={errors.email} />
      </Field.Group>

      <div class="mt-4 d-flex align-items-center justify-content-start">
        <button
          type="submit"
          class="btn btn-primary w-100"
          disabled={processing}
          data-test="email-password-reset-link-button"
        >
          Kirim tautan atur ulang kata sandi
        </button>
      </div>
    {/snippet}
  </Form>

  <div class="text-center small text-muted">
    <span>Atau, kembali untuk</span>
    <TextLink href={login()}>masuk</TextLink>
  </div>
</div>
