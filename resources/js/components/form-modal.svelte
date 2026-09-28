<script lang="ts">
  import type { Snippet } from 'svelte';
  import { portal } from '@/lib/dom';

  let {
    open = $bindable(false),
    title,
    subtitle,
    size,
    children,
  }: {
    open?: boolean;
    title: string;
    subtitle?: string;
    size?: 'sm' | 'lg' | 'xl';
    children: Snippet;
  } = $props();

  const labelId = `form-modal-${crypto.randomUUID()}`;

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
</script>

<div
  use:portal
  bind:this={element}
  class="modal fade"
  tabindex="-1"
  aria-modal="true"
  aria-labelledby={labelId}
  aria-hidden="true"
>
  <div class="modal-dialog modal-dialog-centered {size ? `modal-${size}` : ''}">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title" id={labelId}>{title}</h5>
          {#if subtitle}<p class="text-muted mb-0 small">{subtitle}</p>{/if}
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"
        ></button>
      </div>
      <div class="modal-body">
        {@render children()}
      </div>
    </div>
  </div>
</div>
