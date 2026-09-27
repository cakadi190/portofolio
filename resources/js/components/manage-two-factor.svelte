<script lang="ts">
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
      <h2 class="h5 mb-1">Autentikasi dua faktor</h2>
      <p class="text-muted small mb-3">
        Kelola pengaturan autentikasi dua faktor untuk akun Anda.
      </p>

      {#if !twoFactorEnabled}
        <p class="small mb-3">
          Saat autentikasi dua faktor diaktifkan, Anda akan diminta memasukkan
          PIN aman saat masuk. PIN ini dapat diambil dari aplikasi TOTP di
          ponsel Anda.
        </p>

        {#if !showSetup}
          <Form {...enable.form()} onSuccess={startSetup}>
            {#snippet children({ processing })}
              <button
                type="submit"
                class="btn btn-primary"
                disabled={processing}
              >
                Aktifkan 2FA
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
                      alt="Kode QR autentikasi dua faktor"
                      class="w-100 h-100"
                    />
                  {/if}
                </div>

                <div class="flex-grow-1">
                  <p class="small text-muted mb-1">
                    Pindai kode QR dengan aplikasi autentikator, atau masukkan
                    kunci berikut secara manual:
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
                          <Field.Label for="code">Kode verifikasi</Field.Label>
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
                          Konfirmasi
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
          Anda akan diminta memasukkan PIN acak yang aman saat masuk, yang
          dapat diambil dari aplikasi TOTP di ponsel Anda.
        </p>

        <Form {...disable.form()}>
          {#snippet children({ processing })}
            <button
              type="submit"
              class="btn btn-outline-danger mb-3"
              disabled={processing}
            >
              Nonaktifkan 2FA
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
              {showRecoveryCodes ? 'Sembunyikan' : 'Lihat'} kode pemulihan
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
                    Buat ulang kode
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
              Setiap kode pemulihan hanya dapat digunakan sekali. Klik
              <strong>Buat ulang kode</strong> jika Anda memerlukan kode baru.
            </p>
          {/if}
        </div>
      {/if}
    </div>
  </div>
{/if}
