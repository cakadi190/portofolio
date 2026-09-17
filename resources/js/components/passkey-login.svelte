<script lang="ts">
  import { usePasskeyVerify } from '@laravel/passkeys/svelte';
  import { router } from '@inertiajs/svelte';
  import Fingerprint from '@lucide/svelte/icons/fingerprint';

  const passkeyVerify = usePasskeyVerify({
    onSuccess: ({ redirect }) => router.visit(redirect ?? '/dashboard'),
  });
</script>

<div class="d-flex flex-column gap-2">
  <p class="text-center text-muted mb-0">
    Masuk dengan PassKey yang tertaut dengan akun Anda.
  </p>

  <button
    type="button"
    class="btn btn-outline-primary"
    disabled={passkeyVerify.isLoading}
    onclick={() => passkeyVerify.verify()}
    data-test="login-passkey-button"
  >
    <Fingerprint size={18} />
    Masuk dengan passkey
  </button>

  {#if passkeyVerify.error}
    <div class="small text-danger text-center">
      {passkeyVerify.error}
    </div>
  {/if}
</div>
