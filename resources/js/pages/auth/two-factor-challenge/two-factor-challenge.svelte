<script module lang="ts">
  export const layout = {
    title: 'Verifikasi dua langkah',
    description:
      'Konfirmasi akses ke akun Anda dengan memasukkan kode autentikasi',
  };
</script>

<script lang="ts">
  import { Form } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import TextLink from '@/components/text-link.svelte';
  import { Field } from '@/components/ui/field';
  import { store } from '@/wayfinder/routes/two-factor/login';

  let useRecoveryCode = $state(false);

  function toggleRecoveryCode() {
    useRecoveryCode = !useRecoveryCode;
  }
</script>

<AppHead title="Verifikasi dua langkah" />

<div class="d-flex flex-column gap-3">
  <div class="text-center small text-muted">
    {#if useRecoveryCode}
      Silakan konfirmasi akses ke akun Anda dengan memasukkan salah satu kode
      pemulihan darurat Anda.
    {:else}
      Silakan konfirmasi akses ke akun Anda dengan memasukkan kode autentikasi
      yang disediakan oleh aplikasi autentikator Anda.
    {/if}
  </div>

  <Form {...store.form()} novalidate class="d-flex flex-column gap-3">
    {#snippet children({ errors, processing })}
      {#if useRecoveryCode}
        <Field.Group>
          <Field.Label for="recovery_code">Kode pemulihan</Field.Label>
          <Field.Input placeholder="Masukkan kode pemulihan"
            id="recovery_code"
            type="text"
            name="recovery_code"
            autocomplete="one-time-code"
            autofocus
            invalid={!!errors.recovery_code}
          />
          <Field.Feedback message={errors.recovery_code} />
        </Field.Group>
      {:else}
        <Field.Group>
          <Field.Label for="code">Kode autentikasi</Field.Label>
          <Field.Input placeholder="Masukkan kode autentikasi"
            id="code"
            type="text"
            inputmode="numeric"
            name="code"
            autocomplete="one-time-code"
            autofocus
            invalid={!!errors.code}
          />
          <Field.Feedback message={errors.code} />
        </Field.Group>
      {/if}

      <button
        type="submit"
        class="btn btn-primary w-100"
        disabled={processing}
        data-test="two-factor-login-button"
      >
        Masuk
      </button>
    {/snippet}
  </Form>

  <div class="text-center small text-muted">
    <span>Atau, Anda dapat</span>
    <TextLink
      href="#"
      onclick={(event: MouseEvent) => {
        event.preventDefault();
        toggleRecoveryCode();
      }}
    >
      {useRecoveryCode
        ? 'masuk menggunakan kode autentikasi'
        : 'masuk menggunakan kode pemulihan'}
    </TextLink>
  </div>
</div>
