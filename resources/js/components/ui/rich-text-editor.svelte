<script lang="ts">
  import AlignCenter from '@lucide/svelte/icons/align-center';
  import AlignLeft from '@lucide/svelte/icons/align-left';
  import AlignRight from '@lucide/svelte/icons/align-right';
  import Bold from '@lucide/svelte/icons/bold';
  import Code from '@lucide/svelte/icons/code';
  import Heading1 from '@lucide/svelte/icons/heading-1';
  import Heading2 from '@lucide/svelte/icons/heading-2';
  import Heading3 from '@lucide/svelte/icons/heading-3';
  import ImageIcon from '@lucide/svelte/icons/image';
  import Italic from '@lucide/svelte/icons/italic';
  import Link from '@lucide/svelte/icons/link';
  import List from '@lucide/svelte/icons/list';
  import ListOrdered from '@lucide/svelte/icons/list-ordered';
  import Minus from '@lucide/svelte/icons/minus';
  import Quote from '@lucide/svelte/icons/quote';
  import Redo2 from '@lucide/svelte/icons/redo-2';
  import SquareCode from '@lucide/svelte/icons/square-code';
  import Strikethrough from '@lucide/svelte/icons/strikethrough';
  import Underline from '@lucide/svelte/icons/underline';
  import Undo2 from '@lucide/svelte/icons/undo-2';
  import { Editor } from '@tiptap/core';
  import CharacterCount from '@tiptap/extension-character-count';
  import Image from '@tiptap/extension-image';
  import Placeholder from '@tiptap/extension-placeholder';
  import TextAlign from '@tiptap/extension-text-align';
  import StarterKit from '@tiptap/starter-kit';
  import { onMount } from 'svelte';
  import MediaPickerModal from '@/components/media/media-picker-modal.svelte';
  import { uploadMedia } from '@/lib/media';
  import type { MediaItem } from '@/types/media';

  /**
   * WordPress-style rich text editor on Tiptap. Keeps a hidden
   * `<input {name}>` holding the HTML so Inertia's `<Form>` submits it like
   * any other field. Images come from the media library: the toolbar button
   * opens the picker, while drag & drop and paste upload straight into it.
   */
  let {
    name,
    value = '',
    placeholder = 'Mulai menulis cerita Anda...',
    invalid = false,
    minimal = false,
  }: {
    name: string;
    value?: string | null;
    placeholder?: string;
    invalid?: boolean;
    /** Public-safe mode: basic formatting only, no images, links, headings or HTML tab. */
    minimal?: boolean;
  } = $props();

  let element = $state<HTMLDivElement>();
  let editor = $state.raw<Editor>();
  // svelte-ignore state_referenced_locally
  let html = $state(value ?? '');
  let mode = $state<'visual' | 'html'>('visual');
  let uploading = $state(false);
  let words = $state(0);
  // Bumped on every editor transaction so toolbar state re-evaluates.
  let tick = $state(0);
  let pickerOpen = $state(false);

  function active(
    nameOrAttrs: string | Record<string, unknown>,
    attrs?: Record<string, unknown>,
  ): boolean {
    void tick;
    return editor?.isActive(nameOrAttrs as string, attrs) ?? false;
  }

  function insertImage(media: MediaItem): void {
    if (media.url) {
      editor
        ?.chain()
        .focus()
        .setImage({ src: media.url, alt: media.alt ?? media.name })
        .run();
    }
  }

  async function uploadImage(file: File): Promise<void> {
    if (!file.type.startsWith('image/')) return;

    uploading = true;

    try {
      insertImage(await uploadMedia(file));
    } catch (exception) {
      window.alert(
        exception instanceof Error
          ? exception.message
          : 'Gambar gagal diunggah.',
      );
    } finally {
      uploading = false;
    }
  }

  function setLink(): void {
    if (!editor) return;

    const previous = editor.getAttributes('link').href as string | undefined;
    const url = window.prompt(
      'Alamat tautan (kosongkan untuk menghapus)',
      previous ?? 'https://',
    );

    if (url === null) return;

    if (url.trim() === '') {
      editor.chain().focus().extendMarkRange('link').unsetLink().run();
    } else {
      editor
        .chain()
        .focus()
        .extendMarkRange('link')
        .setLink({ href: url.trim() })
        .run();
    }
  }

  function setMode(next: 'visual' | 'html'): void {
    if (next === 'visual' && editor && mode === 'html') {
      editor.commands.setContent(html, { emitUpdate: false });
      words = editor.storage.characterCount.words();
    }

    mode = next;
  }

  onMount(() => {
    if (!element) return;

    const instance = new Editor({
      element,
      content: value ?? '',
      extensions: [
        StarterKit.configure(
          minimal
            ? {
                link: false,
                heading: false,
                code: false,
                codeBlock: false,
                horizontalRule: false,
              }
            : { link: { openOnClick: false } },
        ),
        ...(minimal ? [] : [Image]),
        Placeholder.configure({ placeholder }),
        ...(minimal
          ? []
          : [TextAlign.configure({ types: ['heading', 'paragraph'] })]),
        CharacterCount,
      ],
      editorProps: {
        attributes: { class: 'rte-content', spellcheck: 'true' },
        handleDrop: (_view, event) => {
          const file = event.dataTransfer?.files?.[0];
          if (!file?.type.startsWith('image/')) return false;
          event.preventDefault();
          void uploadImage(file);
          return true;
        },
        handlePaste: (_view, event) => {
          const file = event.clipboardData?.files?.[0];
          if (!file?.type.startsWith('image/')) return false;
          event.preventDefault();
          void uploadImage(file);
          return true;
        },
      },
      onUpdate: ({ editor: current }) => {
        html = current.isEmpty ? '' : current.getHTML();
        words = current.storage.characterCount.words();
      },
      onTransaction: () => {
        tick++;
      },
    });

    editor = instance;
    words = instance.storage.characterCount.words();

    return () => instance.destroy();
  });
</script>

<div class="rte" class:is-invalid={invalid} class:rte-minimal={minimal}>
  <input type="hidden" {name} value={html} />

  <div class="rte-top">
    <div class="rte-toolbar" role="toolbar" aria-label="Pemformatan teks">
      {#if mode === 'visual' && editor}
        {#if !minimal}
          <select
            class="form-select form-select-sm rte-select"
            aria-label="Gaya paragraf"
            onchange={(event) => {
              const level = Number(event.currentTarget.value);
              if (level === 0) editor?.chain().focus().setParagraph().run();
              else
                editor
                  ?.chain()
                  .focus()
                  .toggleHeading({ level: level as 1 | 2 | 3 })
                  .run();
            }}
            value={active('heading', { level: 1 })
              ? 1
              : active('heading', { level: 2 })
                ? 2
                : active('heading', { level: 3 })
                  ? 3
                  : 0}
          >
            <option value="0">Paragraf</option>
            <option value="1">Judul 1</option>
            <option value="2">Judul 2</option>
            <option value="3">Judul 3</option>
          </select>

          <span class="rte-sep"></span>
        {/if}

        <button
          type="button"
          class="rte-btn"
          class:on={active('bold')}
          title="Tebal"
          onclick={() => editor?.chain().focus().toggleBold().run()}
          ><Bold size={16} /></button
        >
        <button
          type="button"
          class="rte-btn"
          class:on={active('italic')}
          title="Miring"
          onclick={() => editor?.chain().focus().toggleItalic().run()}
          ><Italic size={16} /></button
        >
        <button
          type="button"
          class="rte-btn"
          class:on={active('underline')}
          title="Garis bawah"
          onclick={() => editor?.chain().focus().toggleUnderline().run()}
          ><Underline size={16} /></button
        >
        <button
          type="button"
          class="rte-btn"
          class:on={active('strike')}
          title="Coret"
          onclick={() => editor?.chain().focus().toggleStrike().run()}
          ><Strikethrough size={16} /></button
        >
        {#if !minimal}
          <button
            type="button"
            class="rte-btn"
            class:on={active('code')}
            title="Kode inline"
            onclick={() => editor?.chain().focus().toggleCode().run()}
            ><Code size={16} /></button
          >
        {/if}

        {#if !minimal}
          <span class="rte-sep"></span>

          <button
            type="button"
            class="rte-btn"
            class:on={active('heading', { level: 1 })}
            title="Judul 1"
            onclick={() =>
              editor?.chain().focus().toggleHeading({ level: 1 }).run()}
            ><Heading1 size={16} /></button
          >
          <button
            type="button"
            class="rte-btn"
            class:on={active('heading', { level: 2 })}
            title="Judul 2"
            onclick={() =>
              editor?.chain().focus().toggleHeading({ level: 2 }).run()}
            ><Heading2 size={16} /></button
          >
          <button
            type="button"
            class="rte-btn"
            class:on={active('heading', { level: 3 })}
            title="Judul 3"
            onclick={() =>
              editor?.chain().focus().toggleHeading({ level: 3 }).run()}
            ><Heading3 size={16} /></button
          >

          <span class="rte-sep"></span>
        {/if}
        <span class="rte-sep"></span>

        <button
          type="button"
          class="rte-btn"
          class:on={active('bulletList')}
          title="Daftar poin"
          onclick={() => editor?.chain().focus().toggleBulletList().run()}
          ><List size={16} /></button
        >
        <button
          type="button"
          class="rte-btn"
          class:on={active('orderedList')}
          title="Daftar angka"
          onclick={() => editor?.chain().focus().toggleOrderedList().run()}
          ><ListOrdered size={16} /></button
        >
        <button
          type="button"
          class="rte-btn"
          class:on={active('blockquote')}
          title="Kutipan"
          onclick={() => editor?.chain().focus().toggleBlockquote().run()}
          ><Quote size={16} /></button
        >
        {#if !minimal}
          <button
            type="button"
            class="rte-btn"
            class:on={active('codeBlock')}
            title="Blok kode"
            onclick={() => editor?.chain().focus().toggleCodeBlock().run()}
            ><SquareCode size={16} /></button
          >
          <button
            type="button"
            class="rte-btn"
            title="Garis pemisah"
            onclick={() => editor?.chain().focus().setHorizontalRule().run()}
            ><Minus size={16} /></button
          >

          <span class="rte-sep"></span>

          <button
            type="button"
            class="rte-btn"
            class:on={active({ textAlign: 'left' })}
            title="Rata kiri"
            onclick={() => editor?.chain().focus().setTextAlign('left').run()}
            ><AlignLeft size={16} /></button
          >
          <button
            type="button"
            class="rte-btn"
            class:on={active({ textAlign: 'center' })}
            title="Rata tengah"
            onclick={() => editor?.chain().focus().setTextAlign('center').run()}
            ><AlignCenter size={16} /></button
          >
          <button
            type="button"
            class="rte-btn"
            class:on={active({ textAlign: 'right' })}
            title="Rata kanan"
            onclick={() => editor?.chain().focus().setTextAlign('right').run()}
            ><AlignRight size={16} /></button
          >

          <span class="rte-sep"></span>

          <button
            type="button"
            class="rte-btn"
            class:on={active('link')}
            title="Tautan"
            onclick={setLink}><Link size={16} /></button
          >
          <button
            type="button"
            class="rte-btn"
            title="Sisipkan gambar dari pustaka media"
            disabled={uploading}
            onclick={() => (pickerOpen = true)}><ImageIcon size={16} /></button
          >
        {/if}
        <span class="rte-sep"></span>

        <button
          type="button"
          class="rte-btn"
          title="Urungkan"
          disabled={!editor.can().undo()}
          onclick={() => editor?.chain().focus().undo().run()}
          ><Undo2 size={16} /></button
        >
        <button
          type="button"
          class="rte-btn"
          title="Ulangi"
          disabled={!editor.can().redo()}
          onclick={() => editor?.chain().focus().redo().run()}
          ><Redo2 size={16} /></button
        >
      {/if}
    </div>

    {#if !minimal}
      <div class="rte-tabs" role="tablist">
        <button
          type="button"
          role="tab"
          aria-selected={mode === 'visual'}
          class="rte-tab"
          class:on={mode === 'visual'}
          onclick={() => setMode('visual')}>Visual</button
        >
        <button
          type="button"
          role="tab"
          aria-selected={mode === 'html'}
          class="rte-tab"
          class:on={mode === 'html'}
          onclick={() => setMode('html')}>HTML</button
        >
      </div>
    {/if}
  </div>

  <div class="rte-body" hidden={mode !== 'visual'} bind:this={element}></div>

  {#if mode === 'html'}
    <textarea
      class="rte-html"
      spellcheck="false"
      aria-label="Kode HTML"
      bind:value={html}></textarea>
  {/if}

  <div class="rte-footer">
    <span>{words} kata</span>
    {#if uploading}<span>Mengunggah gambar...</span>{/if}
  </div>
</div>

{#if !minimal}
  <MediaPickerModal
    bind:open={pickerOpen}
    accept="image"
    title="Sisipkan Gambar"
    onSelect={insertImage}
  />
{/if}

<style>
  .rte {
    border: 1px solid var(--neo-border-color);
    border-radius: 0.5rem;
    background: var(--neo-body-bg);
    overflow: hidden;
  }
  .rte.is-invalid {
    border-color: var(--neo-form-invalid-border-color, #dc3545);
  }
  .rte-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 0.5rem;
    border-bottom: 1px solid var(--neo-border-color);
    background: var(--neo-tertiary-bg);
    position: sticky;
    top: 0;
    z-index: 2;
  }
  .rte-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.125rem;
    padding: 0.375rem;
    flex: 1;
    min-height: 2.75rem;
  }
  .rte-select {
    width: auto;
  }
  .rte-sep {
    width: 1px;
    height: 1.25rem;
    margin: 0 0.25rem;
    background: var(--neo-border-color);
  }
  .rte-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border: 0;
    border-radius: 0.375rem;
    background: transparent;
    color: var(--neo-body-color);
  }
  .rte-btn:hover:not(:disabled) {
    background: var(--neo-secondary-bg);
  }
  .rte-btn.on {
    background: var(--neo-primary);
    color: #fff;
  }
  .rte-btn:disabled {
    opacity: 0.4;
  }
  .rte-tabs {
    display: flex;
    align-self: stretch;
  }
  .rte-tab {
    border: 0;
    border-left: 1px solid var(--neo-border-color);
    background: transparent;
    padding: 0 1rem;
    font-size: 0.875rem;
    color: var(--neo-secondary-color);
  }
  .rte-tab.on {
    background: var(--neo-body-bg);
    color: var(--neo-body-color);
    font-weight: 600;
  }
  .rte-body {
    min-height: 26rem;
  }
  .rte-minimal .rte-body,
  .rte-minimal .rte-body :global(.rte-content) {
    min-height: 9rem;
  }
  .rte-body :global(.rte-content) {
    min-height: 26rem;
    padding: 1.25rem 1.5rem;
    outline: none;
    line-height: 1.75;
  }
  .rte-body :global(.rte-content > * + *) {
    margin-top: 0.85em;
  }
  .rte-body :global(.rte-content h1) {
    font-size: 2rem;
  }
  .rte-body :global(.rte-content h2) {
    font-size: 1.6rem;
  }
  .rte-body :global(.rte-content h3) {
    font-size: 1.3rem;
  }
  .rte-body :global(.rte-content img) {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
  }
  .rte-body :global(.rte-content img.ProseMirror-selectednode) {
    outline: 2px solid var(--neo-primary);
  }
  .rte-body :global(.rte-content blockquote) {
    margin-left: 0;
    padding-left: 1rem;
    border-left: 4px solid var(--neo-border-color);
    color: var(--neo-secondary-color);
  }
  .rte-body :global(.rte-content pre) {
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    background: var(--neo-tertiary-bg);
    font-family: var(--neo-font-monospace);
  }
  .rte-body :global(.rte-content code) {
    padding: 0.1rem 0.3rem;
    border-radius: 0.25rem;
    background: var(--neo-tertiary-bg);
  }
  .rte-body :global(.rte-content pre code) {
    padding: 0;
    background: none;
  }
  .rte-body :global(.rte-content a) {
    color: var(--neo-link-color);
    text-decoration: underline;
  }
  .rte-body :global(.rte-content p.is-editor-empty:first-child::before) {
    content: attr(data-placeholder);
    float: left;
    height: 0;
    color: var(--neo-secondary-color);
    pointer-events: none;
  }
  .rte-html {
    display: block;
    width: 100%;
    min-height: 26rem;
    padding: 1rem 1.25rem;
    border: 0;
    outline: none;
    resize: vertical;
    background: var(--neo-body-bg);
    color: var(--neo-body-color);
    font-family: var(--neo-font-monospace);
    font-size: 0.875rem;
  }
  .rte-footer {
    display: flex;
    justify-content: space-between;
    padding: 0.375rem 0.75rem;
    border-top: 1px solid var(--neo-border-color);
    background: var(--neo-tertiary-bg);
    font-size: 0.8125rem;
    color: var(--neo-secondary-color);
  }
</style>
