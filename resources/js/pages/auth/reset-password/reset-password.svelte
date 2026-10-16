<script lang="ts">
  import { setLayoutProps } from '@inertiajs/svelte';
  import { t } from '@/lib/i18n';
  import CaptchaSubmitRow from '@/components/captcha-submit-row.svelte';
  import { Form } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import { Field } from '@/components/ui/field';
  import { update } from '@/wayfinder/routes/password';
  import type { App } from '@/wayfinder/types';

  let {
    token,
    user,
    passwordRules,
  }: {
    token: string;
    user: App.Models.User;
    passwordRules: string;
  } = $props();

  setLayoutProps({
    title: t('auth.atur_ulang_kata_sandi'),
    description: t('auth.silakan_masukkan_kata_sandi_baru_anda'),
  });
</script>

<AppHead title={t('auth.atur_ulang_kata_sandi')} />

<Form
  {...update.form()}
  transform={(data) => ({ ...data, token, email: user?.email })}
  resetOnSuccess={['password', 'password_confirmation']}
  novalidate
>
  {#snippet children({ errors, processing })}
    <div class="d-flex flex-column gap-3">
      <Field.Group class="d-none">
        <Field.Label for="email">{t('auth.email')}</Field.Label>
        <Field.Input
          id="email"
          type="email"
          name="email"
          autocomplete="email"
          value={user?.email}
          class="mt-1 d-block w-100"
          readonly
          invalid={!!errors.email}
        />
        <Field.Feedback message={errors.email} class="mt-2" />
      </Field.Group>

      <Field.Group>
        <Field.Label for="password">{t('auth.kata_sandi')}</Field.Label>
        <Field.Input.Password
          id="password"
          name="password"
          autocomplete="new-password"
          class="mt-1 d-block w-100"
          placeholder={t('auth.kata_sandi')}
          passwordrules={passwordRules}
          invalid={!!errors.password}
        />
        <Field.Feedback message={errors.password} />
      </Field.Group>

      <Field.Group>
        <Field.Label for="password_confirmation"
          >{t('auth.konfirmasi_kata_sandi')}</Field.Label
        >
        <Field.Input.Password
          id="password_confirmation"
          name="password_confirmation"
          autocomplete="new-password"
          class="mt-1 d-block w-100"
          placeholder={t('auth.konfirmasi_kata_sandi')}
          passwordrules={passwordRules}
          invalid={!!errors.password_confirmation}
        />
        <Field.Feedback message={errors.password_confirmation} />
      </Field.Group>

      <CaptchaSubmitRow error={errors['cf-turnstile-response']}>
        <button
          type="submit"
          class="btn btn-primary"
          disabled={processing}
          data-test="reset-password-button"
        >
          {t('auth.atur_ulang_kata_sandi')}
        </button>
      </CaptchaSubmitRow>
    </div>
  {/snippet}
</Form>
