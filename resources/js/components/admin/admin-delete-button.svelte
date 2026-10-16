<script lang="ts">
  import { t } from '@/lib/i18n';
  import Trash2 from '@lucide/svelte/icons/trash-2';
  import { router } from '@inertiajs/svelte';
  import ModalConfirmation from '@/components/modal-confirmation.svelte';

  let {
    href,
    label = t('admin.hapus_data_ini'),
    description = t('admin.tindakan_ini_tidak_dapat_dibatalkan'),
    buttonLabel = t('security.hapus'),
    confirmLabel = t('security.hapus'),
    class:
      className = 'btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1',
  }: {
    href: string;
    label?: string;
    description?: string;
    buttonLabel?: string;
    confirmLabel?: string;
    class?: string;
  } = $props();

  let open = $state(false);
  let processing = $state(false);

  function destroy(): void {
    processing = true;

    router.delete(href, {
      preserveScroll: true,
      onFinish: () => {
        processing = false;
      },
    });
  }
</script>

<button type="button" class={className} onclick={() => (open = true)}>
  <Trash2 size={14} />
  {buttonLabel}
</button>

<ModalConfirmation
  bind:open
  title={label}
  {description}
  {processing}
  {confirmLabel}
  actions={{ confirm: destroy }}
/>
