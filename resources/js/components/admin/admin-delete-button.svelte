<script lang="ts">
  import Trash2 from '@lucide/svelte/icons/trash-2';
  import { router } from '@inertiajs/svelte';
  import ModalConfirmation from '@/components/modal-confirmation.svelte';

  let {
    href,
    label = 'Hapus data ini?',
    description = 'Tindakan ini tidak dapat dibatalkan.',
  }: {
    href: string;
    label?: string;
    description?: string;
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

<button
  type="button"
  class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1"
  onclick={() => (open = true)}
>
  <Trash2 size={14} />
  Hapus
</button>

<ModalConfirmation
  bind:open
  title={label}
  {description}
  {processing}
  confirmLabel="Hapus"
  actions={{ confirm: destroy }}
/>
