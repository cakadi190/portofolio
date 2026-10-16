<script lang="ts">
  import { t } from '@/lib/i18n';
  import { Form } from '@inertiajs/svelte';
  import Eye from '@lucide/svelte/icons/eye';
  import EyeOff from '@lucide/svelte/icons/eye-off';
  import RefreshCw from '@lucide/svelte/icons/refresh-cw';
  import { onDestroy } from 'svelte';
  import { Field } from '@/components/ui/field';
  import { twoFactorAuthState } from '@/lib/two-factor-auth.svelte';
  import {
    confirm,
    disable,
    enable,
    regenerateRecoveryCodes,
  } from '@/wayfinder/routes/two-factor';

  let {
    canManageTwoFactor = false,
    requiresConfirmation = false,
    twoFactorEnabled = false,
  }: {
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
  } = $props();

  const twoFactorAuth = twoFactorAuthState();

  let showSetup = $state(false);
  let showRecoveryCodes = $state(false);
  let code = $state('');

  const qrCodeDataUrl = $derived(
    twoFactorAuth.state.qrCodeSvg
      ? `data:image/svg+xml;utf8,${encodeURIComponent(twoFactorAuth.state.qrCodeSvg)}`
      : '',
  );

  async function startSetup() {
    showSetup = true;

    if (!twoFactorAuth.hasSetupData()) {
      await twoFactorAuth.fetchSetupData();
    }
  }

  async function toggleRecoveryCodes() {
    if (!showRecoveryCodes && !twoFactorAuth.state.recoveryCodesList.length) {
      await twoFactorAuth.fetchRecoveryCodes();
    }

    showRecoveryCodes = !showRecoveryCodes;
  }

  onDestroy(() => twoFactorAuth.clearTwoFactorAuthData());
</script>

{#if canManageTwoFactor}
  <div class="card mb-4">
    <div class="card-body">
      <h2 class="h5 mb-1">{t('security.autentikasi_dua_faktor')}</h2>
      <p class="text-muted small mb-3">
        {t('security.kelola_pengaturan_autentikasi_dua_faktor_untuk')}
      </p>

      {#if !twoFactorEnabled}
        <p class="small mb-3">
          {t('security.saat_autentikasi_dua_faktor_diaktifkan_anda')}
        </p>

        {#if !showSetup}
          <Form {...enable.form()} onSuccess={startSetup}>
            {#snippet children({ processing })}
              <button
                type="submit"
                class="btn btn-primary"
                disabled={processing}
              >
                {t('security.aktifkan_2fa')}
              </button>
            {/snippet}
          </Form>
        {:else}
          <div class="border rounded p-3">
            {#if twoFactorAuth.state.errors.length}
              <div class="text-danger small mb-2">
                {twoFactorAuth.state.errors.join(', ')}
              </div>
            {:else}
              <div class="d-flex flex-column flex-md-row gap-3 align-items-start">
                <div class="d-flex align-items-center justify-content-center bg-white p-2 rounded border" style="width: 180px; height: 180px;">
                  {#if !twoFactorAuth.state.qrCodeSvg}
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                  {:else}
                    <img
                      src={qrCodeDataUrl}
                      alt={t('security.kode_qr_autentikasi_dua_faktor')}
                      class="w-100 h-100"
                    />
                  {/if}
                </div>

                <div class="flex-grow-1">
                  <p class="small text-muted mb-1">
                    {t('security.pindai_kode_qr_dengan_aplikasi_autentikator')}
                  </p>
                  <code class="d-block bg-body-secondary p-2 rounded mb-3">
                    {twoFactorAuth.state.manualSetupKey ?? '...'}
                  </code>

                  {#if requiresConfirmation}
                    <Form
                      {...confirm.form()}
                      resetOnError
                      resetOnSuccess={['code']}
                      onFinish={() => (code = '')}
                    >
                      {#snippet children({ errors, processing })}
                        <input type="hidden" name="code" value={code} />
                        <Field.Group>
                          <Field.Label for="code">{t('security.kode_verifikasi')}</Field.Label>
                          <Field.Input
                            id="code"
                            bind:value={code}
                            inputmode="numeric"
                            maxlength={6}
                            autocomplete="one-time-code"
                            placeholder="000000"
                            invalid={!!errors['confirmTwoFactorAuthentication.code'] || !!errors.code}
                          />
                          <Field.Feedback
                            message={errors['confirmTwoFactorAuthentication.code'] ?? errors.code}
                          />
                        </Field.Group>

                        <button
                          type="submit"
                          class="btn btn-primary mt-2"
                          disabled={processing || code.length < 6}
                        >
                          {t('security.konfirmasi')}
                        </button>
                      {/snippet}
                    </Form>
                  {/if}
                </div>
              </div>
            {/if}
          </div>
        {/if}
      {:else}
        <p class="small mb-3">
          {t('security.anda_akan_diminta_memasukkan_pin_acak')}
        </p>

        <Form {...disable.form()}>
          {#snippet children({ processing })}
            <button
              type="submit"
              class="btn btn-outline-danger mb-3"
              disabled={processing}
            >
              {t('security.nonaktifkan_2fa')}
            </button>
          {/snippet}
        </Form>

        <div class="border rounded p-3">
          <div class="d-flex flex-column flex-sm-row gap-2 align-items-sm-center justify-content-between">
            <button
              type="button"
              class="btn btn-outline-secondary btn-sm"
              onclick={toggleRecoveryCodes}
            >
              {#if showRecoveryCodes}
                <EyeOff size={16} />
              {:else}
                <Eye size={16} />
              {/if}
              {showRecoveryCodes ? t('admin.sembunyikan') : t('admin.lihat')} kode pemulihan
            </button>

            {#if showRecoveryCodes && twoFactorAuth.state.recoveryCodesList.length}
              <Form
                {...regenerateRecoveryCodes.form()}
                options={{ preserveScroll: true }}
                onSuccess={() => twoFactorAuth.fetchRecoveryCodes()}
              >
                {#snippet children({ processing })}
                  <button
                    type="submit"
                    class="btn btn-outline-secondary btn-sm"
                    disabled={processing}
                  >
                    <RefreshCw size={16} />
                    {t('security.buat_ulang_kode')}
                  </button>
                {/snippet}
              </Form>
            {/if}
          </div>

          {#if showRecoveryCodes}
            <div class="mt-3 bg-body-secondary rounded p-3 font-monospace small">
              {#if !twoFactorAuth.state.recoveryCodesList.length}
                <div class="spinner-border spinner-border-sm" role="status"></div>
              {:else}
                {#each twoFactorAuth.state.recoveryCodesList as recoveryCode (recoveryCode)}
                  <div>{recoveryCode}</div>
                {/each}
              {/if}
            </div>
            <p class="text-muted small mt-2 mb-0">
              {t('security.setiap_kode_pemulihan_hanya_dapat_digunakan')}
              <strong>{t('security.buat_ulang_kode')}</strong> {t('security.jika_anda_memerlukan_kode_baru')}
            </p>
          {/if}
        </div>
      {/if}
    </div>
  </div>
{/if}
