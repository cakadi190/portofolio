<script lang="ts">
  import { t } from '@/lib/i18n';
  import { usePasskeyVerify } from '@laravel/passkeys/svelte';
  import { router } from '@inertiajs/svelte';
  import Fingerprint from '@lucide/svelte/icons/fingerprint';

  const passkeyVerify = usePasskeyVerify({
    onSuccess: ({ redirect }) => router.visit(redirect ?? '/admin'),
  });
</script>

<div class="d-flex flex-column gap-2">
  <p class="text-center text-muted mb-0">
    {t('auth.masuk_dengan_passkey_yang_tertaut_dengan')}
  </p>

  <button
    type="button"
    class="btn btn-outline-primary"
    disabled={passkeyVerify.isLoading}
    onclick={() => passkeyVerify.verify()}
    data-test="login-passkey-button"
  >
    <Fingerprint size={18} />
    {t('auth.masuk_dengan_passkey')}
  </button>

  {#if passkeyVerify.error}
    <div class="small text-danger text-center">
      {passkeyVerify.error}
    </div>
  {/if}
</div>
