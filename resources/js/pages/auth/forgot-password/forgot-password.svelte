<script lang="ts">
  import { setLayoutProps } from '@inertiajs/svelte';
  import { t } from '@/lib/i18n';
  import CaptchaSubmitRow from '@/components/captcha-submit-row.svelte';
  import { Form } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import TextLink from '@/components/text-link.svelte';
  import { Field } from '@/components/ui/field';
  import { login } from '@/wayfinder/routes';
  import { email } from '@/wayfinder/routes/password';

  let {
    status = '',
  }: {
    status?: string;
  } = $props();

  setLayoutProps({
    title: t('auth.lupa_kata_sandi'),
    description:
      t('auth.masukkan_email_anda_untuk_menerima_tautan'),
  });
</script>

<AppHead title={t('auth.lupa_kata_sandi')} />

{#if status}
  <div class="mb-4 text-center small fw-medium text-success">
    {status}
  </div>
{/if}

<div class="d-flex flex-column gap-3">
  <Form {...email.form()} novalidate class="gap-3 d-flex flex-column">
    {#snippet children({ errors, processing })}
      <Field.Group>
        <Field.Label for="email">{t('auth.alamat_email')}</Field.Label>
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

      <CaptchaSubmitRow error={errors['cf-turnstile-response']}>
        <button
          type="submit"
          class="btn btn-primary"
          disabled={processing}
          data-test="email-password-reset-link-button"
        >
          {t('auth.kirim_tautan_atur_ulang_kata_sandi')}
        </button>
      </CaptchaSubmitRow>
    {/snippet}
  </Form>

  <div class="text-center small text-muted">
    <span>{t('auth.atau_kembali_untuk')}</span>
    <TextLink href={login()}>masuk</TextLink>
  </div>
</div>
