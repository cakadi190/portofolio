<script lang="ts">
  import type { Editor } from '@tiptap/core';
  import { getMarkRange } from '@tiptap/core';
  import Check from '@lucide/svelte/icons/check';
  import ChevronRight from '@lucide/svelte/icons/chevron-right';
  import Copy from '@lucide/svelte/icons/copy';
  import CornerDownLeft from '@lucide/svelte/icons/corner-down-left';
  import Globe from '@lucide/svelte/icons/globe';
  import Pencil from '@lucide/svelte/icons/pencil';
  import Unlink from '@lucide/svelte/icons/unlink';
  import { copyText } from '@/lib/code-card';
  import { LINK_HIGHLIGHT_META } from '@/lib/editor-blocks';
  import { t } from '@/lib/i18n';
  import {
    isPreviewable,
    linkDisplay,
    normalizeLinkUrl,
  } from '@/lib/link-url';
  import { linkPreview } from '@/wayfinder/routes/admin';

  type Mode = 'closed' | 'add' | 'preview' | 'edit';
  type Range = { from: number; to: number };
  type Preview = { title?: string; image?: string; siteName?: string };

  let { editor, host }: { editor: Editor; host: HTMLElement | undefined } =
    $props();

  let mode = $state<Mode>('closed');
  let range = $state<Range | null>(null);
  let position = $state({ top: 0, left: 0 });

  let href = $state('');
  let text = $state('');
  let newTab = $state(true);
  let nofollow = $state(false);
  let advancedOpen = $state(false);
  let error = $state('');
  let copied = $state(false);

  let preview = $state<Preview | null>(null);
  let loadingPreview = $state(false);
  const previews = new Map<string, Preview | null>();

  let popover = $state<HTMLDivElement>();
  let primaryInput = $state<HTMLInputElement>();

  /** The link mark around the selection, if the whole selection sits inside one. */
  function linkAt(): { range: Range; attrs: Record<string, unknown> } | null {
    const { state } = editor;
    const type = state.schema.marks.link;

    if (!type) {
      return null;
    }

    const found = getMarkRange(state.selection.$from, type);

    if (!found || state.selection.to > found.to) {
      return null;
    }

    return { range: found, attrs: editor.getAttributes('link') };
  }

  function highlight(next: Range | null): void {
    editor.view.dispatch(
      editor.state.tr.setMeta(LINK_HIGHLIGHT_META, next).setMeta('addToHistory', false),
    );
  }

  /** Places the popover just under the targeted text, inside the document column. */
  function place(): void {
    if (!host || !range) {
      return;
    }

    const bounds = host.getBoundingClientRect();
    const start = editor.view.coordsAtPos(range.from);
    const end = editor.view.coordsAtPos(range.to);
    const width = popover?.offsetWidth ?? 320;

    position = {
      top: Math.max(start.bottom, end.bottom) - bounds.top + 8,
      left: Math.max(
        8,
        Math.min(start.left - bounds.left, bounds.width - width - 8),
      ),
    };
  }

  function close(refocus = true): void {
    if (mode === 'add' || mode === 'edit') {
      highlight(null);
    }

    mode = 'closed';
    range = null;
    error = '';
    advancedOpen = false;

    if (refocus) {
      editor.commands.focus();
    }
  }

  async function loadPreview(url: string): Promise<void> {
    preview = null;

    if (!isPreviewable(url)) {
      return;
    }

    if (previews.has(url)) {
      preview = previews.get(url) ?? null;

      return;
    }

    loadingPreview = true;

    try {
      const response = await fetch(linkPreview().url, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-XSRF-TOKEN': decodeURIComponent(
            document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/)?.[1] ?? '',
          ),
        },
        credentials: 'same-origin',
        body: JSON.stringify({ url }),
      });
      const data = response.ok ? ((await response.json()) as Preview) : null;

      previews.set(url, data);

      if (href === url) {
        preview = data;
      }
    } catch {
      previews.set(url, null);
    } finally {
      loadingPreview = false;
    }
  }

  function readLink(found: { range: Range; attrs: Record<string, unknown> }): void {
    range = found.range;
    href = String(found.attrs.href ?? '');
    text = editor.state.doc.textBetween(found.range.from, found.range.to);
    newTab = found.attrs.target === '_blank';
    nofollow = String(found.attrs.rel ?? '').includes('nofollow');
  }

  function showPreview(found: { range: Range; attrs: Record<string, unknown> }): void {
    readLink(found);
    mode = 'preview';
    copied = false;
    void loadPreview(href);
    queueMicrotask(place);
  }

  /** Toolbar button and Ctrl+K: preview an existing link, or start a new one on the selection. */
  export function start(): void {
    const found = linkAt();

    if (found) {
      showPreview(found);

      return;
    }

    const { from, to } = editor.state.selection;

    range = { from, to };
    href = '';
    text = '';
    error = '';
    mode = 'add';
    highlight(from === to ? null : range);
    queueMicrotask(() => {
      place();
      primaryInput?.focus();
    });
  }

  function startEdit(): void {
    const found = linkAt();

    if (found) {
      readLink(found);
    }

    error = '';
    advancedOpen = false;
    mode = 'edit';
    highlight(range);
    queueMicrotask(() => {
      place();
      primaryInput?.select();
    });
  }

  function attrsFor(url: string): {
    href: string;
    target: string | null;
    rel: string | null;
  } {
    const rel = [newTab ? 'noopener noreferrer' : '', nofollow ? 'nofollow' : '']
      .filter(Boolean)
      .join(' ');

    return { href: url, target: newTab ? '_blank' : null, rel: rel || null };
  }

  function submitAdd(event: Event): void {
    event.preventDefault();

    const url = normalizeLinkUrl(href);

    if (!url || !range) {
      error = t('link.invalid');

      return;
    }

    newTab = /^https?:\/\//i.test(url);

    const attrs = attrsFor(url);
    const target = range;

    highlight(null);

    if (target.from === target.to) {
      editor
        .chain()
        .focus()
        .insertContentAt(target.from, {
          type: 'text',
          text: url,
          marks: [{ type: 'link', attrs }],
        })
        .run();
    } else {
      editor
        .chain()
        .focus()
        .setTextSelection(target)
        .setLink(attrs)
        .setTextSelection(target.to)
        .run();
    }

    mode = 'closed';
    range = null;
  }

  function submitEdit(event: Event): void {
    event.preventDefault();

    const url = normalizeLinkUrl(href);
    const label = text.trim();

    if (!url || !range) {
      error = t('link.invalid');

      return;
    }

    const attrs = attrsFor(url);
    const target = range;
    const current = editor.state.doc.textBetween(target.from, target.to);

    highlight(null);

    if (label !== '' && label !== current) {
      editor
        .chain()
        .focus()
        .insertContentAt(target, {
          type: 'text',
          text: label,
          marks: [{ type: 'link', attrs }],
        })
        .run();
    } else {
      editor.chain().focus().setTextSelection(target).setLink(attrs).run();
    }

    mode = 'closed';
    range = null;
  }

  function removeLink(): void {
    if (!range) {
      return;
    }

    const target = range;

    mode = 'closed';
    range = null;
    editor.chain().focus().setTextSelection(target).unsetLink().run();
  }

  async function copyHref(): Promise<void> {
    copied = await copyText(href);
    window.setTimeout(() => (copied = false), 1800);
  }

  // Show the preview whenever the caret rests inside a link; hide it when it leaves.
  $effect(() => {
    const onSelection = (): void => {
      if (mode === 'add' || mode === 'edit' || !editor.isFocused) {
        return;
      }

      const found = linkAt();

      if (found) {
        const same = range && range.from === found.range.from && range.to === found.range.to;

        if (mode !== 'preview' || !same) {
          showPreview(found);
        }
      } else if (mode === 'preview') {
        close(false);
      }
    };
    const onTransaction = (): void => {
      if (mode !== 'closed') {
        queueMicrotask(place);
      }
    };

    editor.on('selectionUpdate', onSelection);
    editor.on('transaction', onTransaction);
    window.addEventListener('resize', place);
    window.addEventListener('scroll', place, true);

    return () => {
      editor.off('selectionUpdate', onSelection);
      editor.off('transaction', onTransaction);
      window.removeEventListener('resize', place);
      window.removeEventListener('scroll', place, true);
    };
  });

  function onWindowPointerDown(event: PointerEvent): void {
    if (mode === 'closed') {
      return;
    }

    const target = event.target as HTMLElement;

    if (
      popover?.contains(target) ||
      target.closest('[data-rte-link-trigger]') ||
      (mode === 'preview' && target.closest('.ProseMirror'))
    ) {
      return;
    }

    close(false);
  }

  function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape' && mode !== 'closed') {
      event.preventDefault();
      event.stopPropagation();
      close();
    }
  }
</script>

<svelte:window onpointerdown={onWindowPointerDown} />

{#if mode !== 'closed'}
  <!-- svelte-ignore a11y_no_static_element_interactions -->
  <div
    bind:this={popover}
    class="rte-link-pop"
    class:is-wide={mode === 'edit'}
    style="top: {position.top}px; left: {position.left}px"
    role="dialog"
    aria-label={t(mode === 'edit' ? 'link.edit' : 'link.url')}
    tabindex="-1"
    onkeydown={onKeydown}
  >
    {#if mode === 'add'}
      <form class="rte-link-add" onsubmit={submitAdd}>
        <input
          bind:this={primaryInput}
          bind:value={href}
          type="text"
          class="rte-link-input"
          class:is-invalid={!!error}
          placeholder={t('link.search_placeholder')}
          aria-label={t('link.url')}
          autocomplete="off"
          spellcheck="false"
          oninput={() => (error = '')}
        />
        <button
          type="submit"
          class="rte-link-submit"
          title={t('link.submit')}
          aria-label={t('link.submit')}
          disabled={href.trim() === ''}><CornerDownLeft size={16} /></button
        >
      </form>
      {#if error}<p class="rte-link-error">{error}</p>{/if}
    {:else if mode === 'edit'}
      <form class="rte-link-edit" onsubmit={submitEdit}>
        <label class="rte-link-field">
          <span>{t('link.text')}</span>
          <input
            bind:value={text}
            type="text"
            class="rte-link-input"
            autocomplete="off"
          />
        </label>
        <label class="rte-link-field">
          <span>{t('link.url')}</span>
          <input
            bind:this={primaryInput}
            bind:value={href}
            type="text"
            class="rte-link-input"
            class:is-invalid={!!error}
            autocomplete="off"
            spellcheck="false"
            oninput={() => (error = '')}
          />
        </label>
        {#if error}<p class="rte-link-error">{error}</p>{/if}

        <button
          type="button"
          class="rte-link-advanced"
          aria-expanded={advancedOpen}
          onclick={() => (advancedOpen = !advancedOpen)}
        >
          <ChevronRight size={14} class={advancedOpen ? 'is-open' : ''} />
          {t('link.advanced')}
        </button>
        {#if advancedOpen}
          <div class="rte-link-options">
            <label class="rte-switch">
              <input type="checkbox" role="switch" bind:checked={newTab} />
              <span class="rte-switch-track" aria-hidden="true"></span>
              <span>{t('link.new_tab')}</span>
            </label>
            <label class="rte-switch">
              <input type="checkbox" role="switch" bind:checked={nofollow} />
              <span class="rte-switch-track" aria-hidden="true"></span>
              <span>{t('link.nofollow')}</span>
            </label>
          </div>
        {/if}

        <div class="rte-link-actions">
          <button type="button" class="rte-link-cancel" onclick={() => close()}>
            {t('link.cancel')}
          </button>
          <button type="submit" class="btn btn-sm btn-primary">
            {t('link.apply')}
          </button>
        </div>
      </form>
    {:else}
      <div class="rte-link-preview">
        <span class="rte-link-icon" aria-hidden="true">
          {#if preview?.image}
            <img src={preview.image} alt="" loading="lazy" />
          {:else}
            <Globe size={18} />
          {/if}
        </span>
        <div class="rte-link-info">
          <a
            class="rte-link-title"
            {href}
            target="_blank"
            rel="noopener noreferrer"
            title={t('link.open')}
          >
            {preview?.title || (loadingPreview ? t('link.loading') : text || linkDisplay(href))}
          </a>
          <span class="rte-link-url">{linkDisplay(href)}</span>
        </div>
        <div class="rte-link-tools">
          <button
            type="button"
            title={t('link.edit')}
            aria-label={t('link.edit')}
            onclick={startEdit}><Pencil size={16} /></button
          >
          <button
            type="button"
            title={t('link.remove')}
            aria-label={t('link.remove')}
            onclick={removeLink}><Unlink size={16} /></button
          >
          <button
            type="button"
            title={t('link.copy')}
            aria-label={t('link.copy')}
            onclick={copyHref}
            >{#if copied}<Check size={16} />{:else}<Copy size={16} />{/if}</button
          >
        </div>
      </div>
    {/if}
  </div>
{/if}

<style>
  .rte-link-pop {
    position: absolute;
    z-index: 6;
    width: 22rem;
    max-width: calc(100% - 1rem);
    padding: 0.5rem;
    border: 1px solid var(--rte-line, rgb(0 0 0 / 0.14));
    border-radius: 0.5rem;
    background: var(--neo-body-bg);
    color: var(--neo-body-color);
    box-shadow: 0 0.5rem 1.5rem rgb(0 0 0 / 0.18);
    font-size: 0.875rem;
  }
  .rte-link-pop.is-wide {
    width: 20rem;
    padding: 0.875rem;
  }

  .rte-link-add {
    display: flex;
    align-items: center;
    gap: 0.25rem;
  }

  .rte-link-input {
    width: 100%;
    padding: 0.5rem 0.625rem;
    border: 1px solid var(--rte-line, rgb(0 0 0 / 0.2));
    border-radius: 0.25rem;
    background: transparent;
    color: inherit;
    font: inherit;
  }
  .rte-link-input:focus {
    border-color: var(--neo-primary);
    outline: 1px solid var(--neo-primary);
  }
  .rte-link-input.is-invalid {
    border-color: var(--neo-form-invalid-border-color, #dc3545);
  }

  .rte-link-submit {
    display: inline-flex;
    flex: none;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    border: 0;
    border-radius: 0.25rem;
    background: transparent;
    color: var(--neo-secondary-color);
  }
  .rte-link-submit:not(:disabled):hover {
    background: var(--neo-primary);
    color: #fff;
  }
  .rte-link-submit:disabled {
    opacity: 0.4;
  }

  .rte-link-error {
    margin: 0.375rem 0.125rem 0;
    color: var(--neo-form-invalid-color, #dc3545);
    font-size: 0.8125rem;
  }

  .rte-link-edit {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }
  .rte-link-field {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }
  .rte-link-field > span {
    color: var(--neo-secondary-color);
    font-size: 0.6875rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }

  .rte-link-advanced {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    align-self: flex-start;
    padding: 0;
    border: 0;
    background: none;
    color: inherit;
    font-weight: 500;
  }
  .rte-link-advanced :global(.is-open) {
    transform: rotate(90deg);
  }
  .rte-link-options {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .rte-link-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.5rem;
  }
  .rte-link-cancel {
    padding: 0.25rem 0.75rem;
    border: 0;
    background: none;
    color: var(--neo-primary);
    font-weight: 500;
  }

  .rte-link-preview {
    display: flex;
    align-items: center;
    gap: 0.625rem;
  }
  .rte-link-icon {
    display: inline-flex;
    flex: none;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    overflow: hidden;
    border-radius: 0.25rem;
    background: var(--neo-tertiary-bg);
    color: var(--neo-secondary-color);
  }
  .rte-link-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
  .rte-link-info {
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
    min-width: 0;
  }
  .rte-link-title {
    overflow: hidden;
    color: var(--neo-link-color, var(--neo-primary));
    font-weight: 500;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .rte-link-url {
    overflow: hidden;
    color: var(--neo-secondary-color);
    font-size: 0.75rem;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .rte-link-tools {
    display: flex;
    flex: none;
    gap: 0.125rem;
  }
  .rte-link-tools button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border: 0;
    border-radius: 0.25rem;
    background: transparent;
    color: var(--neo-body-color);
  }
  .rte-link-tools button:hover {
    background: var(--neo-tertiary-bg);
  }

  .rte-switch {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    font-size: 0.8125rem;
    cursor: pointer;
  }
  .rte-switch input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
  }
  .rte-switch-track {
    position: relative;
    flex: none;
    width: 2rem;
    height: 1.125rem;
    border: 1px solid var(--neo-body-color);
    border-radius: 99rem;
    transition:
      background-color 0.15s,
      border-color 0.15s;
  }
  .rte-switch-track::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 0.1875rem;
    width: 0.625rem;
    height: 0.625rem;
    border-radius: 50%;
    background: var(--neo-body-color);
    transform: translateY(-50%);
    transition:
      transform 0.15s,
      background-color 0.15s;
  }
  .rte-switch input:checked + .rte-switch-track {
    border-color: var(--neo-primary);
    background: var(--neo-primary);
  }
  .rte-switch input:checked + .rte-switch-track::after {
    background: #fff;
    transform: translate(0.875rem, -50%);
  }
  .rte-switch input:focus-visible + .rte-switch-track {
    outline: 2px solid var(--neo-primary);
    outline-offset: 2px;
  }
</style>
