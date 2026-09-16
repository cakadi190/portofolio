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
  import InputError from '@/components/input-error.svelte';
  import TextLink from '@/components/text-link.svelte';
  import { Input } from '@/components/ui/input';
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
  <Form {...email.form()}>
    {#snippet children({ errors, processing })}
      <div class="d-flex flex-column gap-2">
        <label class="form-label" for="email">Alamat email</label>
        <Input
          id="email"
          type="email"
          name="email"
          autocomplete="off"
          placeholder="email@contoh.com"
        />
        <InputError message={errors.email} />
      </div>

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
