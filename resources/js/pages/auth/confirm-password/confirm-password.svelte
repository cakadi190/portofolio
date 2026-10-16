<script lang="ts">
  import { setLayoutProps } from '@inertiajs/svelte';
  import { t } from '@/lib/i18n';
  import { Form } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import { Field } from '@/components/ui/field';
  import { store } from '@/wayfinder/routes/password/confirm';

  setLayoutProps({
    title: t('auth.konfirmasi_kata_sandi'),
    description:
      t('auth.ini_adalah_area_yang_aman_silakan'),
  });
</script>

<AppHead title={t('auth.konfirmasi_kata_sandi')} />

<Form {...store.form()} resetOnSuccess={['password']} novalidate>
  {#snippet children({ errors, processing })}
    <div class="d-flex flex-column gap-3">
      <Field.Group>
        <Field.Label for="password">{t('auth.kata_sandi')}</Field.Label>
        <Field.Input.Password placeholder="••••••••"
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
        {t('auth.konfirmasi_kata_sandi')}
      </button>
    </div>
  {/snippet}
</Form>
