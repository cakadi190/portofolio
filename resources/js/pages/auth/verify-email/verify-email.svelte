<script module lang="ts">
  export const layout = {
    title: 'Verifikasi email',
    description:
      'Silakan verifikasi alamat email Anda dengan mengklik tautan yang telah kami kirimkan',
  };
</script>

<script lang="ts">
  import { Form } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import TextLink from '@/components/text-link.svelte';
  import { logout } from '@/routes';
  import { send } from '@/routes/verification';

  let {
    status = '',
  }: {
    status?: string;
  } = $props();

  const verificationLinkSent = $derived(status === 'verification-link-sent');
</script>

<AppHead title="Verifikasi email" />

<div class="d-flex flex-column gap-3">
  <div class="text-center small text-muted">
    Terima kasih telah mendaftar! Sebelum memulai, mohon verifikasi alamat email
    Anda dengan mengklik tautan yang baru saja kami kirimkan. Jika Anda tidak
    menerima email tersebut, kami akan dengan senang hati mengirimkan yang baru.
  </div>

  {#if verificationLinkSent}
    <div class="text-center small fw-medium text-success">
      Tautan verifikasi baru telah dikirim ke alamat email yang Anda daftarkan
      saat pendaftaran.
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
        Kirim ulang email verifikasi
      </button>
    {/snippet}
  </Form>

  <div class="text-center small text-muted">
    <TextLink href={logout()} method="post" as="button">Keluar</TextLink>
  </div>
</div>
