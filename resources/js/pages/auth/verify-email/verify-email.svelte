<script lang="ts">
  import { setLayoutProps } from '@inertiajs/svelte';
  import { t } from '@/lib/i18n';
  import { Form } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import LogoutAction from '@/components/logout-action.svelte';
  import { send } from '@/wayfinder/routes/verification';

  let {
    status = '',
  }: {
    status?: string;
  } = $props();

  const verificationLinkSent = $derived(status === 'verification-link-sent');

  setLayoutProps({
    title: t('auth.verifikasi_email'),
    description:
      t('auth.silakan_verifikasi_alamat_email_anda_dengan'),
  });
</script>

<AppHead title={t('auth.verifikasi_email')} />

<div class="d-flex flex-column gap-3">
  <div class="text-center small text-muted">
    {t('auth.terima_kasih_telah_mendaftar_sebelum_memulai')}
  </div>

  {#if verificationLinkSent}
    <div class="text-center small fw-medium text-success">
      {t('auth.tautan_verifikasi_baru_telah_dikirim_ke')}
    </div>
  {/if}

  <Form {...send.form()} class="d-flex flex-column gap-3">
    {#snippet children({ processing })}
      <button
        type="submit"
        class="btn btn-primary w-100"
        disabled={processing}
        data-test="resend-verification-button"
      >
        {t('auth.kirim_ulang_email_verifikasi')}
      </button>
    {/snippet}
  </Form>

  <div class="text-center small text-muted">
    <LogoutAction class="btn btn-link p-0 text-body">{t('auth.keluar')}</LogoutAction>
  </div>
</div>
