<script lang="ts">
  import { router } from '@inertiajs/svelte';
  import LogOut from '@lucide/svelte/icons/log-out';
  import type { Snippet } from 'svelte';
  import ModalConfirmation from '@/components/modal-confirmation.svelte';
  import { logout } from '@/wayfinder/routes';

  let {
    as = 'button',
    href = logout().url,
    title = 'Apakah Anda Sangat Yakin?',
    description = 'Apakah Anda yakin akan keluar? Pastikan semua aksi yang Anda lakukan saat ini sudah tersimpan untuk menghindari kehilangan data setelah aksi ini.',
    children,
    ...rest
  }: {
    as?: keyof HTMLElementTagNameMap;
    href?: string;
    title?: string;
    description?: string;
    children?: Snippet;
    [key: string]: unknown;
  } = $props();

  let open = $state(false);
  let processing = $state(false);

  function submit(): void {
    processing = true;
    router.post(href, {}, { onFinish: () => (processing = false) });
  }
</script>

<svelte:element
  this={as}
  {...as === 'button' ? { type: 'button' } : {}}
  {...rest}
  onclick={() => (open = true)}
>
  {@render children?.()}
</svelte:element>

<ModalConfirmation
  bind:open
  {title}
  {description}
  confirmLabel="Keluar"
  {processing}
  actions={{ confirm: submit }}
>
  {#snippet confirmIcon()}
    <LogOut size={16} aria-hidden="true" />
  {/snippet}
</ModalConfirmation>
