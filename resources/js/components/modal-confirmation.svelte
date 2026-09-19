<script lang="ts">
  import TriangleAlert from '@lucide/svelte/icons/triangle-alert';
  import type { Snippet } from 'svelte';
  import { portal } from '@/lib/dom';

  let {
    open = $bindable(false),
    title,
    description,
    actions,
    confirmLabel = 'Ya',
    denyLabel = 'Batal',
    processing = false,
    icon,
    confirmIcon,
  }: {
    open?: boolean;
    title: string;
    description: string;
    actions: {
      confirm: () => void | Promise<void>;
      deny?: () => void;
    };
    confirmLabel?: string;
    denyLabel?: string;
    processing?: boolean;
    icon?: Snippet;
    confirmIcon?: Snippet;
  } = $props();

  const labelId = `modal-confirmation-${crypto.randomUUID()}`;

  let element = $state<HTMLElement>();
  let modal: { show(): void; hide(): void } | undefined;

  $effect(() => {
    const node = element;

    if (!node) {
      return;
    }

    let disposed = false;
    const onHidden = (): void => {
      open = false;
    };

    node.addEventListener('hidden.bs.modal', onHidden);

    void import('bootstrap').then(({ Modal }) => {
      if (disposed) {
        return;
      }

      modal = Modal.getOrCreateInstance(node);

      if (open) {
        modal.show();
      }
    });

    return () => {
      disposed = true;
      node.removeEventListener('hidden.bs.modal', onHidden);
      modal = undefined;
    };
  });

  $effect(() => {
    if (open) {
      modal?.show();
    } else {
      modal?.hide();
    }
  });

  function deny(): void {
    open = false;
    actions.deny?.();
  }

  async function confirm(): Promise<void> {
    open = false;
    await actions.confirm();
  }
</script>

<div
  use:portal
  bind:this={element}
  class="modal fade modal-confirmation"
  role="alertdialog"
  aria-modal="true"
  aria-labelledby={labelId}
  tabindex="-1"
  aria-hidden="true"
>
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body d-flex gap-3">
        <div class="modal-confirmation-icon" aria-hidden="true">
          {#if icon}
            {@render icon()}
          {:else}
            <TriangleAlert size={20} />
          {/if}
        </div>
        <div>
          <h5 class="modal-title mb-1" id={labelId}>{title}</h5>
          <p class="mb-0 text-muted">{description}</p>
        </div>
      </div>
      <div class="modal-footer modal-confirmation-footer">
        <button type="button" class="btn btn-link text-body" onclick={deny}
          >{denyLabel}</button
        >
        <button
          type="button"
          class="btn btn-danger d-inline-flex align-items-center gap-2"
          disabled={processing}
          onclick={confirm}
        >
          {@render confirmIcon?.()}
          {confirmLabel}
        </button>
      </div>
    </div>
  </div>
</div>

<style>
  .modal-confirmation-icon {
    display: flex;
    flex: none;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.5rem;
    color: var(--neo-danger);
    background-color: var(--neo-danger-bg-subtle);
  }

  .modal-confirmation-footer {
    border-top: 1px solid var(--neo-border-color);
    background-color: var(--neo-tertiary-bg);
  }

  .btn-link {
    text-decoration: none;
  }
</style>
