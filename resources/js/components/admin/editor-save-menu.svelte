<script lang="ts">
  import { overlayIn, overlayOut } from '@/lib/overlay-motion';
  import Code from '@lucide/svelte/icons/code';
  import PenLine from '@lucide/svelte/icons/pen-line';
  import Trash2 from '@lucide/svelte/icons/trash-2';
  import Undo2 from '@lucide/svelte/icons/undo-2';
  import ModalConfirmation from '@/components/modal-confirmation.svelte';

  /**
   * Save split-button for the editor top bar: the primary action submits the
   * form, the dropdown holds the editor-mode switch (mobile only), the draft
   * action and, when `onTrash` is given, a danger "move to trash" action.
   */
  let {
    processing = false,
    exists,
    draftable = true,
    isPublished = false,
    mode,
    setMode,
    onStatus,
    danger,
  }: {
    processing?: boolean;
    /** Whether the record is already saved (edit page). */
    exists: boolean;
    /** Whether the record has a draft/published status. */
    draftable?: boolean;
    isPublished?: boolean;
    mode: 'visual' | 'html';
    setMode: (next: 'visual' | 'html') => void;
    onStatus?: (published: boolean) => void;
    /** Destructive action shown at the bottom of the menu, behind a confirmation. */
    danger?: {
      label: string;
      title: string;
      description: string;
      confirmLabel: string;
      processing?: boolean;
      run: () => void;
    };
  } = $props();

  let open = $state(false);
  let root = $state<HTMLDivElement | null>(null);
  let dangerOpen = $state(false);

  const primaryLabel = $derived(
    draftable
      ? isPublished
        ? 'Perbarui'
        : 'Terbitkan'
      : exists
        ? 'Perbarui'
        : 'Simpan',
  );

  /** Ctrl/Cmd+S saves keeping the current status; Ctrl/Cmd+Shift+S publishes a draft. */
  function submitFrom(published: boolean | null): void {
    if (processing) {
      return;
    }

    if (published !== null) {
      onStatus?.(published);
    }

    root?.closest('form')?.requestSubmit();
  }

  function onShortcut(event: KeyboardEvent): void {
    if (!(event.ctrlKey || event.metaKey) || event.altKey) {
      return;
    }

    const key = event.key.toLowerCase();

    if (key !== 's') {
      return;
    }

    if (!event.shiftKey) {
      event.preventDefault();
      submitFrom(draftable ? isPublished : null);
    } else if (draftable && !isPublished) {
      event.preventDefault();
      submitFrom(true);
    }
  }
</script>

<svelte:window
  onpointerdown={(event) => {
    if (!(event.target as HTMLElement).closest('[data-save-menu]'))
      open = false;
  }}
  onkeydown={(event) => {
    if (event.key === 'Escape') open = false;
    onShortcut(event);
  }}
/>

{#if draftable}
  <input type="hidden" name="is_published" value={isPublished ? '1' : '0'} />
{/if}

<div class="btn-group dropdown position-relative" bind:this={root}>
  <button
    type="submit"
    class="btn btn-sm btn-primary"
    disabled={processing}
    title={draftable && !isPublished
      ? 'Terbitkan (Ctrl+Shift+S)'
      : 'Simpan (Ctrl+S)'}
    onclick={() => onStatus?.(true)}
  >
    {primaryLabel}
  </button>
  <button
    type="button"
    class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split"
    aria-label="Pilihan simpan"
    aria-haspopup="menu"
    aria-expanded={open}
    data-save-menu
    disabled={processing}
    onclick={() => (open = !open)}
  ></button>
  {#if open}
    <ul
      class="dropdown-menu dropdown-menu-end show"
      role="menu"
      in:overlayIn={{ side: 'bottom', origin: 'top right' }}
      out:overlayOut={{ origin: 'top right' }}
      data-save-menu
      style="top: 100%; right: 0; z-index: 1060; min-width: 15rem"
    >
      <li class="d-lg-none">
        <button
          type="button"
          role="menuitem"
          class="dropdown-item"
          onclick={() => {
            setMode(mode === 'visual' ? 'html' : 'visual');
            open = false;
          }}
        >
          {#if mode === 'visual'}
            <Code size={16} aria-hidden="true" /> Editor HTML
          {:else}
            <PenLine size={16} aria-hidden="true" /> Editor Visual
          {/if}
        </button>
      </li>
      {#if draftable}
        <li>
          <button
            type="button"
            role="menuitem"
            class="dropdown-item"
            onclick={() => {
              submitFrom(false);
              open = false;
            }}
          >
            <Undo2 size={16} aria-hidden="true" />
            {isPublished ? 'Kembalikan ke Draf' : 'Simpan Draf'}
          </button>
        </li>
      {/if}
      {#if danger && exists}
        <li><hr class="dropdown-divider" /></li>
        <li>
          <button
            type="button"
            role="menuitem"
            class="dropdown-item dropdown-item-danger"
            onclick={() => {
              dangerOpen = true;
              open = false;
            }}
          >
            <Trash2 size={16} aria-hidden="true" />
            {danger?.label}
          </button>
        </li>
      {/if}
    </ul>
  {/if}
</div>

{#if danger}
  <ModalConfirmation
    bind:open={dangerOpen}
    title={danger.title}
    description={danger.description}
    processing={danger.processing ?? false}
    confirmLabel={danger.confirmLabel}
    actions={{ confirm: danger.run }}
  />
{/if}
