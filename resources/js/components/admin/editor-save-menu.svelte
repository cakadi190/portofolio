<script lang="ts">
  import Code from '@lucide/svelte/icons/code';
  import FileText from '@lucide/svelte/icons/file-text';
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
    label,
    processing = false,
    exists,
    draftable = true,
    isPublished = false,
    persistedPublished = false,
    mode,
    setMode,
    onStatus,
    danger,
  }: {
    label: string;
    processing?: boolean;
    /** Whether the record is already saved (edit page). */
    exists: boolean;
    /** Whether the record has a draft/published status. */
    draftable?: boolean;
    isPublished?: boolean;
    persistedPublished?: boolean;
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
  let dangerOpen = $state(false);

  const primaryLabel = $derived(
    draftable
      ? persistedPublished
        ? 'Perbarui'
        : 'Terbitkan'
      : exists
        ? 'Perbarui'
        : 'Simpan',
  );
</script>

<svelte:window
  onpointerdown={(event) => {
    if (!(event.target as HTMLElement).closest('[data-save-menu]'))
      open = false;
  }}
  onkeydown={(event) => {
    if (event.key === 'Escape') open = false;
  }}
/>

{#if draftable}
  <input type="hidden" name="is_published" value={isPublished ? '1' : '0'} />
{/if}

<div class="btn-group dropdown position-relative">
  <button
    type="submit"
    class="btn btn-sm btn-primary"
    disabled={processing}
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
      data-save-menu
      style="top: 100%; right: 0; z-index: 1060; min-width: 15rem"
    >
      <li>
        <div
          class="d-flex align-items-center gap-3 px-3 py-2 mb-2 rounded-3"
          style="background: var(--neo-surface-inset)"
        >
          <span
            class="d-flex align-items-center justify-content-center rounded-circle"
            style="width: 40px; height: 40px; background: var(--neo-surface-raised)"
          >
            <FileText size={18} aria-hidden="true" />
          </span>
          <div>
            <strong class="d-block">{label}</strong>
            {#if draftable}
              <span
                class={`badge ${isPublished ? 'text-bg-success' : 'text-bg-secondary'}`}
                >{isPublished ? 'Terbit' : 'Draf'}</span
              >
            {:else}
              <span class="small text-muted"
                >{exists ? 'Tersimpan' : 'Belum disimpan'}</span
              >
            {/if}
          </div>
        </div>
      </li>
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
            type="submit"
            role="menuitem"
            class="dropdown-item"
            onclick={() => {
              onStatus?.(false);
              open = false;
            }}
          >
            <Undo2 size={16} aria-hidden="true" />
            {persistedPublished ? 'Kembalikan ke Draf' : 'Simpan Draf'}
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
