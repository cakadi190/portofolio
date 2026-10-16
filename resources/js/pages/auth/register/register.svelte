<script lang="ts">
  import { setLayoutProps } from '@inertiajs/svelte';
  import { t } from '@/lib/i18n';
  import CaptchaSubmitRow from '@/components/captcha-submit-row.svelte';
  import { Form, Link } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import TextLink from '@/components/text-link.svelte';
  import { Field } from '@/components/ui/field';
  import { login } from '@/wayfinder/routes';
  import { store } from '@/wayfinder/routes/register';

  let { passwordRules }: { passwordRules: string } = $props();

  setLayoutProps({
    title: t('auth.buat_akun'),
    description: t('auth.masukkan_detail_anda_di_bawah_untuk'),
  });
</script>

<AppHead title={t('auth.daftar')} />

<Form
  {...store.form()}
  resetOnSuccess={['password', 'password_confirmation']}
  class="d-flex flex-column gap-3"
  novalidate
>
  {#snippet children({ errors, processing })}
    <div class="d-flex flex-column gap-3">
      <Field.Group>
        <Field.Label for="name">{t('auth.nama')}</Field.Label>
        <Field.Input
          id="name"
          type="text"
          required
          autocomplete="name"
          name="name"
          placeholder={t('auth.nama_lengkap')}
          invalid={!!errors.name}
        />
        <Field.Feedback message={errors.name} />
      </Field.Group>

      <Field.Group>
        <Field.Label for="email">{t('auth.alamat_email')}</Field.Label>
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
        <Field.Label for="password">{t('auth.kata_sandi')}</Field.Label>
        <Field.Input.Password
          confirmed="password_confirmation"
          id="password"
          required
          autocomplete="new-password"
          name="password"
          placeholder={t('auth.kata_sandi')}
          passwordrules={passwordRules}
          invalid={!!errors.password}
        />
        <Field.Feedback message={errors.password} />
      </Field.Group>

      <Field.Input.Check id="agreement" name="agreement">
        {t('auth.saya_setuju_dengan')} <Link href="/policy/agreement"
          >{t('auth.syarat_dan_ketentuan')}</Link
        > {t('auth.dari_catatan_cak_adi')}
      </Field.Input.Check>

      <CaptchaSubmitRow error={errors['cf-turnstile-response']}>
        <button
          type="submit"
          class="btn btn-primary"
          disabled={processing}
          data-test="register-user-button"
        >
          {t('auth.buat_akun')}
        </button>
      </CaptchaSubmitRow>
    </div>

    <div class="text-center small text-muted">
      {t('auth.sudah_punya_akun')}
      <TextLink href={login()} class="text-decoration-underline">
        {t('auth.masuk')}
      </TextLink>
    </div>
  {/snippet}
</Form>
