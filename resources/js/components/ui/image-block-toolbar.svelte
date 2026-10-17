<script lang="ts">
  import { t } from '@/lib/i18n';
  import { onMount, tick as nextTick } from 'svelte';
  import type { Editor } from '@tiptap/core';
  import AlignCenter from '@lucide/svelte/icons/align-center';
  import AlignLeft from '@lucide/svelte/icons/align-left';
  import AlignRight from '@lucide/svelte/icons/align-right';
  import ChevronDown from '@lucide/svelte/icons/chevron-down';
  import ChevronUp from '@lucide/svelte/icons/chevron-up';
  import Copy from '@lucide/svelte/icons/copy';
  import EllipsisVertical from '@lucide/svelte/icons/ellipsis-vertical';
  import ImageIcon from '@lucide/svelte/icons/image';
  import Link from '@lucide/svelte/icons/link';
  import Plus from '@lucide/svelte/icons/plus';
  import Trash2 from '@lucide/svelte/icons/trash-2';
  import Type from '@lucide/svelte/icons/type';
  import { safeHref } from '@/lib/editor-blocks';

  /**
   * Gutenberg-style controls for the selected image: a blue outline with
   * drag handles that resize the width (in 5% steps), and a floating toolbar
   * above it (move, align, link, alt text, replace, more options).
   */
  let {
    editor,
    host,
    block,
    revision,
    canMove,
    onpatch,
    onreplace,
    onmove,
    onduplicate,
    onremove,
    onaddparagraph,
  }: {
    editor: Editor;
    host: HTMLElement;
    block: { pos: number; attrs: Record<string, any> };
    /** Changes whenever the document or selection changes, so the box is re-measured. */
    revision: number;
    canMove: (direction: -1 | 1) => boolean;
    onpatch: (patch: Record<string, unknown>) => void;
    onreplace: () => void;
    onmove: (direction: -1 | 1) => void;
    onduplicate: () => void;
    onremove: () => void;
    onaddparagraph: (where: 'before' | 'after') => void;
  } = $props();

  type Panel = 'align' | 'link' | 'alt' | 'more' | null;

  const MIN_WIDTH = 10;
  const alignments = [
    { value: 'left', label: t('ui.rata_kiri'), icon: AlignLeft },
    { value: 'center', label: t('ui.rata_tengah'), icon: AlignCenter },
    { value: 'right', label: t('ui.rata_kanan'), icon: AlignRight },
  ] as const;

  let panel = $state<Panel>(null);
  let box = $state({ top: 0, left: 0, width: 0, height: 0 });
  let root = $state<HTMLElement>();
  let resizing = $state(false);
  let linkDraft = $state('');
  let newTabDraft = $state(false);

  const attrs = $derived(block.attrs);
  const align = $derived<'left' | 'center' | 'right'>(attrs.align ?? 'center');
  const CurrentAlign = $derived(
    alignments.find((item) => item.value === align)?.icon ?? AlignCenter,
  );
  const hasLink = $derived(Boolean(attrs.href));

  function figure(): HTMLElement | null {
    const dom = editor.view.nodeDOM(block.pos);

    return dom instanceof HTMLElement ? dom : null;
  }

  /** Measures the figure relative to the editor host so the overlay follows it. */
  function measure(): void {
    const element = figure();

    if (!element) {
      return;
    }

    const bounds = host.getBoundingClientRect();
    const rect = (
      element.querySelector('img') ?? element
    ).getBoundingClientRect();

    box = {
      top: rect.top - bounds.top,
      left: rect.left - bounds.left,
      width: rect.width,
      height: rect.height,
    };
  }

  $effect(() => {
    void revision;
    void block;
    measure();
  });

  onMount(() => {
    const observer = new ResizeObserver(measure);
    observer.observe(host);
    const onPointerDown = (event: PointerEvent): void => {
      if (root && !root.contains(event.target as Node)) {
        panel = null;
      }
    };
    document.addEventListener('pointerdown', onPointerDown);

    return () => {
      observer.disconnect();
      document.removeEventListener('pointerdown', onPointerDown);
    };
  });

  async function open(next: Exclude<Panel, null>): Promise<void> {
    if (panel === next) {
      panel = null;

      return;
    }

    if (next === 'link') {
      linkDraft = attrs.href ?? '';
      newTabDraft = Boolean(attrs.newTab);
    }

    panel = next;
    await nextTick();
    root
      ?.querySelector<HTMLInputElement | HTMLTextAreaElement>(
        '.ibt-panel input[type="url"], .ibt-panel textarea',
      )
      ?.focus();
  }

  function applyLink(href: string): void {
    const value = href.trim();
    const safe = value === '' ? '' : safeHref(value);
    const link = safe === '#' && value !== '#' ? '' : safe;

    onpatch({ href: link, newTab: link === '' ? false : newTabDraft });
    panel = null;
  }

  function keepSelection(event: Event): void {
    if (!(
      event.target instanceof HTMLInputElement ||
      event.target instanceof HTMLTextAreaElement
    )) {
      event.preventDefault();
    }
  }

  /** Drag a handle: width follows the pointer, snapped to 5% of the column. */
  function startResize(event: PointerEvent, axis: 'right' | 'bottom'): void {
    const element = figure();
    const column = element?.parentElement;

    if (!element || !column) {
      return;
    }

    event.preventDefault();
    const handle = event.currentTarget as HTMLElement;
    handle.setPointerCapture(event.pointerId);
    resizing = true;

    const startX = event.clientX;
    const startY = event.clientY;
    const startWidth = element.getBoundingClientRect().width;
    const ratio = box.height > 0 ? box.width / box.height : 1;
    let frame = 0;

    const move = (moveEvent: PointerEvent): void => {
      cancelAnimationFrame(frame);
      frame = requestAnimationFrame(() => {
        const delta =
          axis === 'right'
            ? (moveEvent.clientX - startX) *
              (align === 'center' ? 2 : 1) *
              (align === 'right' ? -1 : 1)
            : (moveEvent.clientY - startY) *
              ratio *
              (align === 'center' ? 2 : 1);
        const percent = ((startWidth + delta) / column.clientWidth) * 100;
        const snapped = Math.min(
          100,
          Math.max(MIN_WIDTH, Math.round(percent / 5) * 5),
        );

        if (snapped !== (attrs.width ?? 100)) {
          onpatch({ width: snapped === 100 ? null : snapped });
        }
      });
    };

    const stop = (): void => {
      resizing = false;
      handle.removeEventListener('pointermove', move);
      handle.removeEventListener('pointerup', stop);
      handle.removeEventListener('pointercancel', stop);
    };

    handle.addEventListener('pointermove', move);
    handle.addEventListener('pointerup', stop);
    handle.addEventListener('pointercancel', stop);
  }

  function nudgeWidth(event: KeyboardEvent): void {
    const step =
      event.key === 'ArrowRight' ? 5 : event.key === 'ArrowLeft' ? -5 : 0;

    if (step === 0) {
      return;
    }

    event.preventDefault();
    onpatch({
      width: Math.min(100, Math.max(MIN_WIDTH, (attrs.width ?? 100) + step)),
    });
  }

  function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape' && panel) {
      event.stopPropagation();
      panel = null;
    }
  }
</script>

<div bind:this={root} class="ibt" role="presentation" onkeydown={onKeydown}>
  <div
    class="ibt-outline"
    class:is-resizing={resizing}
    style="top: {box.top}px; left: {box.left}px; width: {box.width}px; height: {box.height}px"
  >
    <button
      type="button"
      class="ibt-handle ibt-handle-right"
      role="slider"
      aria-label={t('ui.img_resize_right')}
      aria-valuemin={MIN_WIDTH}
      aria-valuemax="100"
      aria-valuenow={attrs.width ?? 100}
      onpointerdown={(event) => startResize(event, 'right')}
      onkeydown={nudgeWidth}
    ></button>
    <button
      type="button"
      class="ibt-handle ibt-handle-bottom"
      role="slider"
      aria-label={t('ui.img_resize_bottom')}
      aria-valuemin={MIN_WIDTH}
      aria-valuemax="100"
      aria-valuenow={attrs.width ?? 100}
      onpointerdown={(event) => startResize(event, 'bottom')}
      onkeydown={nudgeWidth}
    ></button>
  </div>

  {#if !resizing}
    <div
      class="ibt-bar"
      role="toolbar"
      aria-label={t('ui.img_toolbar')}
      tabindex="-1"
      style="top: {Math.max(box.top - 50, 0)}px; left: {box.left}px"
      onmousedown={keepSelection}
    >
      <div class="ibt-group">
        <span class="ibt-btn ibt-static" aria-hidden="true"
          ><ImageIcon size={18} /></span
        >
        <button
          type="button"
          class="ibt-btn"
          title={t('ui.pindah_ke_atas')}
          aria-label={t('ui.pindah_ke_atas')}
          disabled={!canMove(-1)}
          onclick={() => onmove(-1)}><ChevronUp size={16} /></button
        >
        <button
          type="button"
          class="ibt-btn"
          title={t('ui.pindah_ke_bawah')}
          aria-label={t('ui.pindah_ke_bawah')}
          disabled={!canMove(1)}
          onclick={() => onmove(1)}><ChevronDown size={16} /></button
        >
      </div>

      <div class="ibt-group">
        <div class="ibt-anchor">
          <button
            type="button"
            class="ibt-btn"
            aria-haspopup="menu"
            aria-expanded={panel === 'align'}
            title={t('ui.perataan')}
            aria-label={t('ui.perataan')}
            onclick={() => open('align')}><CurrentAlign size={18} /></button
          >
          {#if panel === 'align'}
            <div class="ibt-panel ibt-menu" role="menu">
              {#each alignments as item (item.value)}
                <button
                  type="button"
                  role="menuitemradio"
                  aria-checked={align === item.value}
                  class="ibt-item"
                  class:on={align === item.value}
                  onclick={() => {
                    onpatch({
                      align: item.value,
                      ...(item.value !== 'center' && !attrs.width
                        ? { width: 50 }
                        : {}),
                    });
                    panel = null;
                  }}
                >
                  <item.icon size={16} />{item.label}
                </button>
              {/each}
            </div>
          {/if}
        </div>

        <div class="ibt-anchor">
          <button
            type="button"
            class="ibt-btn"
            class:on={hasLink}
            aria-expanded={panel === 'link'}
            title={t('ui.img_link')}
            aria-label={t('ui.img_link')}
            onclick={() => open('link')}><Link size={18} /></button
          >
          {#if panel === 'link'}
            <form
              class="ibt-panel ibt-form"
              novalidate
              onsubmit={(event) => {
                event.preventDefault();
                applyLink(linkDraft);
              }}
            >
              <input
                type="url"
                class="form-control form-control-sm"
                placeholder={t('ui.img_link_url')}
                aria-label={t('ui.img_link_url')}
                bind:value={linkDraft}
              />
              <button
                type="button"
                class="ibt-item"
                onclick={() => applyLink(String(attrs.src ?? ''))}
                >{t('ui.img_link_file')}</button
              >
              <label class="ibt-check"
                ><input
                  type="checkbox"
                  class="form-check-input"
                  bind:checked={newTabDraft}
                />{t('ui.img_link_new_tab')}</label
              >
              <div class="ibt-actions">
                <button type="submit" class="btn btn-sm btn-primary"
                  >{t('ui.img_link_apply')}</button
                >
                {#if hasLink}
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary"
                    onclick={() => applyLink('')}
                    >{t('ui.img_link_remove')}</button
                  >
                {/if}
              </div>
            </form>
          {/if}
        </div>

        <div class="ibt-anchor">
          <button
            type="button"
            class="ibt-btn"
            class:on={Boolean(attrs.alt)}
            aria-expanded={panel === 'alt'}
            title={t('ui.teks_alternatif')}
            aria-label={t('ui.teks_alternatif')}
            onclick={() => open('alt')}><Type size={18} /></button
          >
          {#if panel === 'alt'}
            <div class="ibt-panel ibt-form">
              <label class="ibt-label" for="ibt-alt"
                >{t('ui.teks_alternatif')}</label
              >
              <textarea
                id="ibt-alt"
                class="form-control form-control-sm"
                rows="3"
                value={attrs.alt ?? ''}
                oninput={(event) => onpatch({ alt: event.currentTarget.value })}
              ></textarea>
              <p class="ibt-hint">{t('ui.img_alt_hint')}</p>
            </div>
          {/if}
        </div>
      </div>

      <div class="ibt-group">
        <button type="button" class="ibt-btn ibt-text" onclick={onreplace}
          >{t('ui.img_replace')}</button
        >
      </div>

      <div class="ibt-group">
        <div class="ibt-anchor">
          <button
            type="button"
            class="ibt-btn"
            aria-haspopup="menu"
            aria-expanded={panel === 'more'}
            title={t('ui.img_options')}
            aria-label={t('ui.img_options')}
            onclick={() => open('more')}><EllipsisVertical size={18} /></button
          >
          {#if panel === 'more'}
            <div class="ibt-panel ibt-menu ibt-menu-end" role="menu">
              <button
                type="button"
                role="menuitem"
                class="ibt-item"
                onclick={() => {
                  onduplicate();
                  panel = null;
                }}><Copy size={16} />{t('ui.img_duplicate')}</button
              >
              <button
                type="button"
                role="menuitem"
                class="ibt-item"
                onclick={() => {
                  onaddparagraph('before');
                  panel = null;
                }}><Plus size={16} />{t('ui.img_add_before')}</button
              >
              <button
                type="button"
                role="menuitem"
                class="ibt-item"
                onclick={() => {
                  onaddparagraph('after');
                  panel = null;
                }}><Plus size={16} />{t('ui.img_add_after')}</button
              >
              <hr class="ibt-sep" />
              <button
                type="button"
                role="menuitem"
                class="ibt-item ibt-danger"
                onclick={() => {
                  onremove();
                  panel = null;
                }}><Trash2 size={16} />{t('ui.img_delete')}</button
              >
            </div>
          {/if}
        </div>
      </div>
    </div>
  {/if}
</div>

<style>
  .ibt {
    --ibt-accent: var(--neo-primary, #3858e9);
    position: absolute;
    inset: 0;
    z-index: 4;
    pointer-events: none;
  }

  .ibt-outline {
    position: absolute;
    box-shadow: 0 0 0 2px var(--ibt-accent);
    pointer-events: none;
  }

  .ibt-handle {
    position: absolute;
    width: 1rem;
    height: 1rem;
    padding: 0;
    border: 2px solid var(--ibt-accent);
    border-radius: 50%;
    background: var(--neo-body-bg, #fff);
    pointer-events: auto;
    touch-action: none;
  }
  .ibt-handle:focus-visible {
    outline: 2px solid var(--neo-body-color, #000);
    outline-offset: 2px;
  }
  .ibt-handle-right {
    top: 50%;
    right: -0.5rem;
    margin-top: -0.5rem;
    cursor: ew-resize;
  }
  .ibt-handle-bottom {
    bottom: -0.5rem;
    left: 50%;
    margin-left: -0.5rem;
    cursor: ns-resize;
  }

  .ibt-bar {
    position: absolute;
    display: flex;
    align-items: stretch;
    max-width: 100%;
    background: var(--neo-body-bg, #fff);
    color: var(--neo-body-color, #1e1e1e);
    border: 1px solid var(--neo-emphasis-color, #1e1e1e);
    border-radius: 0.125rem;
    box-shadow: 0 2px 6px rgb(0 0 0 / 0.15);
    pointer-events: auto;
  }
  .ibt-group {
    display: flex;
    align-items: center;
    padding: 0 0.125rem;
  }
  .ibt-group + .ibt-group {
    border-left: 1px solid var(--neo-border-color, #ddd);
  }

  .ibt-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2.25rem;
    height: 2.5rem;
    padding: 0 0.4rem;
    border: 0;
    border-radius: 0.125rem;
    background: transparent;
    color: inherit;
  }
  .ibt-btn:hover:not(:disabled),
  .ibt-btn:focus-visible,
  .ibt-btn.on,
  .ibt-btn[aria-expanded='true'] {
    background: rgba(var(--neo-primary-rgb, 56 88 233), 0.12);
    color: var(--ibt-accent);
  }
  .ibt-btn:disabled {
    opacity: 0.35;
  }
  .ibt-static {
    pointer-events: none;
  }
  .ibt-text {
    font-size: 0.8125rem;
    font-weight: 500;
  }

  .ibt-anchor {
    position: relative;
  }
  .ibt-panel {
    position: absolute;
    top: calc(100% + 0.5rem);
    left: 0;
    z-index: 5;
    min-width: 13rem;
    padding: 0.35rem;
    background: var(--neo-body-bg, #fff);
    border: 1px solid var(--neo-border-color, #ddd);
    border-radius: 0.375rem;
    box-shadow: 0 4px 14px rgb(0 0 0 / 0.18);
  }
  .ibt-menu-end {
    left: auto;
    right: 0;
  }
  .ibt-form {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    width: 18rem;
    padding: 0.6rem;
  }
  .ibt-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.4rem 0.5rem;
    border: 0;
    border-radius: 0.25rem;
    background: transparent;
    color: inherit;
    font-size: 0.8125rem;
    text-align: left;
    white-space: nowrap;
  }
  .ibt-item:hover,
  .ibt-item:focus-visible,
  .ibt-item.on {
    background: rgba(var(--neo-primary-rgb, 56 88 233), 0.12);
    color: var(--ibt-accent);
  }
  .ibt-danger {
    color: var(--neo-danger, #cc1818);
  }
  .ibt-sep {
    margin: 0.25rem 0;
    border-color: var(--neo-border-color, #ddd);
  }
  .ibt-check {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
  }
  .ibt-label {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
  }
  .ibt-hint {
    margin: 0;
    font-size: 0.75rem;
    color: var(--neo-secondary-color, #666);
  }
  .ibt-actions {
    display: flex;
    gap: 0.5rem;
  }
</style>
