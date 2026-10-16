<script lang="ts">
  import { setLayoutProps } from '@inertiajs/svelte';
  import { t } from '@/lib/i18n';
  import { Form } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import { Field } from '@/components/ui/field';
  import OtpInput from '@/components/ui/otp-input.svelte';
  import { store } from '@/wayfinder/routes/two-factor/login';

  let useRecoveryCode = $state(false);

  function toggleRecoveryCode() {
    useRecoveryCode = !useRecoveryCode;
  }

  setLayoutProps({
    title: t('auth.verifikasi_dua_langkah'),
    description:
      t('auth.konfirmasi_akses_ke_akun_anda_dengan'),
  });
</script>

<AppHead title={t('auth.verifikasi_dua_langkah')} />

<div class="d-flex flex-column gap-3">
  <div class="text-center small text-muted">
    {#if useRecoveryCode}
      {t('auth.konfirmasi_kode_pemulihan')}
    {:else}
      {t('auth.konfirmasi_kode_aplikasi')}
    {/if}
  </div>

  {#key useRecoveryCode}
  <Form {...store.form()} novalidate class="d-flex flex-column gap-3">
    {#snippet children({ errors, processing })}
      {#if useRecoveryCode}
        <Field.Group>
          <Field.Label for="recovery_code">{t('auth.kode_pemulihan')}</Field.Label>
          <Field.Input placeholder={t('auth.masukkan_kode_pemulihan')}
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
          <Field.Label for="code">{t('auth.kode_autentikasi')}</Field.Label>
          <OtpInput name="code" id="code" autofocus invalid={!!errors.code} />
          <Field.Feedback message={errors.code} />
        </Field.Group>
      {/if}

      <button
        type="submit"
        class="btn btn-primary w-100"
        disabled={processing}
        data-test="two-factor-login-button"
      >
        {t('auth.masuk')}
      </button>
    {/snippet}
  </Form>
  {/key}

  <div class="text-center small text-muted">
    <span>{t('auth.atau_anda_dapat')}</span>
    <button
      type="button"
      class="btn btn-link p-0 align-baseline small text-body"
      data-test="two-factor-toggle-button"
      onclick={toggleRecoveryCode}
    >
      {useRecoveryCode
        ? 'masuk menggunakan kode autentikasi'
        : 'masuk menggunakan kode pemulihan'}
    </button>
  </div>
</div>
