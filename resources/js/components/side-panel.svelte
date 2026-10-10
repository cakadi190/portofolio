<script lang="ts">
  import { type Snippet } from 'svelte';
  import { portal } from '@/lib/dom';

  let {
    open = $bindable(false),
    title,
    subtitle,
    children,
  }: {
    open?: boolean;
    title: string;
    subtitle?: string;
    children: Snippet;
  } = $props();

  const labelId = `side-panel-${crypto.randomUUID()}`;

  let element = $state<HTMLElement>();
  let panel: { show(): void; hide(): void } | undefined;

  $effect(() => {
    const node = element;

    if (!node) {
      return;
    }

    let disposed = false;
    const onHidden = (): void => {
      open = false;
    };

    node.addEventListener('hidden.bs.offcanvas', onHidden);

    void import('bootstrap').then(({ Offcanvas }) => {
      if (disposed) {
        return;
      }

      panel = Offcanvas.getOrCreateInstance(node);

      if (open) {
        panel.show();
      }
    });

    return () => {
      disposed = true;
      node.removeEventListener('hidden.bs.offcanvas', onHidden);
      panel = undefined;
    };
  });

  $effect(() => {
    if (open) {
      panel?.show();
    } else {
      panel?.hide();
    }
  });
</script>

<div
  use:portal
  bind:this={element}
  class="offcanvas offcanvas-end"
  tabindex="-1"
  aria-labelledby={labelId}
>
  <div class="offcanvas-header align-items-start">
    <div>
      <h5 class="offcanvas-title" id={labelId}>{title}</h5>
      {#if subtitle}<p class="text-muted mb-0 small">{subtitle}</p>{/if}
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
  </div>
  <div class="offcanvas-body">
    {@render children()}
  </div>
</div>
