<script lang="ts">
  import AlignCenter from '@lucide/svelte/icons/align-center';
  import AlignLeft from '@lucide/svelte/icons/align-left';
  import AlignRight from '@lucide/svelte/icons/align-right';
  import ArrowDown from '@lucide/svelte/icons/arrow-down';
  import ArrowUp from '@lucide/svelte/icons/arrow-up';
  import Bold from '@lucide/svelte/icons/bold';
  import ClipboardPaste from '@lucide/svelte/icons/clipboard-paste';
  import Copy from '@lucide/svelte/icons/copy';
  import CopyPlus from '@lucide/svelte/icons/copy-plus';
  import Info from '@lucide/svelte/icons/info';
  import MousePointerClick from '@lucide/svelte/icons/mouse-pointer-click';
  import Replace from '@lucide/svelte/icons/replace';
  import Scissors from '@lucide/svelte/icons/scissors';
  import Trash from '@lucide/svelte/icons/trash';
  import Code from '@lucide/svelte/icons/code';
  import Heading1 from '@lucide/svelte/icons/heading-1';
  import Heading2 from '@lucide/svelte/icons/heading-2';
  import Heading3 from '@lucide/svelte/icons/heading-3';
  import ImageIcon from '@lucide/svelte/icons/image';
  import ChevronDown from '@lucide/svelte/icons/chevron-down';
  import Italic from '@lucide/svelte/icons/italic';
  import Link from '@lucide/svelte/icons/link';
  import List from '@lucide/svelte/icons/list';
  import ListOrdered from '@lucide/svelte/icons/list-ordered';
  import Minus from '@lucide/svelte/icons/minus';
  import Pilcrow from '@lucide/svelte/icons/pilcrow';
  import Plus from '@lucide/svelte/icons/plus';
  import Quote from '@lucide/svelte/icons/quote';
  import Redo2 from '@lucide/svelte/icons/redo-2';
  import SquareCode from '@lucide/svelte/icons/square-code';
  import Strikethrough from '@lucide/svelte/icons/strikethrough';
  import Underline from '@lucide/svelte/icons/underline';
  import Undo2 from '@lucide/svelte/icons/undo-2';
  import { Editor, type ChainedCommands } from '@tiptap/core';
  import CharacterCount from '@tiptap/extension-character-count';
  import Placeholder from '@tiptap/extension-placeholder';
  import TextAlign from '@tiptap/extension-text-align';
  import { NodeSelection } from '@tiptap/pm/state';
  import StarterKit from '@tiptap/starter-kit';
  import { onMount } from 'svelte';
  import MediaPickerModal from '@/components/media/media-picker-modal.svelte';
  import {
    ButtonBlock,
    CALLOUT_TYPES,
    Callout,
    FigureImage,
    safeHref,
  } from '@/lib/editor-blocks';
  import { uploadMedia } from '@/lib/media';
  import type { MediaItem } from '@/types/media';

  /**
   * WordPress-style rich text editor on Tiptap. Keeps a hidden
   * `<input {name}>` holding the HTML so Inertia's `<Form>` submits it like
   * any other field. Images come from the media library: the toolbar button
   * opens the picker, while drag & drop and paste upload straight into it.
   * Gutenberg-style blocks (positioned images, callouts, buttons) are edited
   * from the block bar, and a right-click context menu offers quick actions.
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
  let toolbarOpen = $state(false);
  let host = $state<HTMLDivElement>();
  let blockInfo = $state.raw<BlockInfo | null>(null);
  let pickerMode = $state<'insert' | 'replace'>('insert');
  let ctx = $state({ open: false, x: 0, y: 0 });
  let ctxEl = $state<HTMLDivElement>();

  type BlockItem = {
    label: string;
    hint: string;
    icon: typeof Pilcrow;
    keywords: string;
    run?: (chain: ChainedCommands) => ChainedCommands;
    picker?: boolean;
    action?: () => void;
  };

  type BlockKind = 'image' | 'buttonBlock' | 'callout';
  type BlockInfo = {
    kind: BlockKind;
    pos: number;
    attrs: Record<string, any>;
  };
  type ContextItem = {
    label: string;
    icon: typeof Pilcrow;
    action: () => void;
    disabled?: boolean;
    on?: boolean;
    danger?: boolean;
  };

  const calloutLabels: Record<string, string> = {
    info: 'Info',
    success: 'Sukses',
    warning: 'Peringatan',
    danger: 'Bahaya',
  };

  const imageWidths = [25, 50, 75, 100];

  const blockItems: BlockItem[] = [
    {
      label: 'Paragraf',
      hint: 'Teks biasa',
      icon: Pilcrow,
      keywords: 'paragraph teks',
      run: (c) => c.setParagraph(),
    },
    {
      label: 'Judul 1',
      hint: 'Judul besar',
      icon: Heading1,
      keywords: 'heading h1',
      run: (c) => c.setHeading({ level: 1 }),
    },
    {
      label: 'Judul 2',
      hint: 'Subjudul',
      icon: Heading2,
      keywords: 'heading h2',
      run: (c) => c.setHeading({ level: 2 }),
    },
    {
      label: 'Judul 3',
      hint: 'Subjudul kecil',
      icon: Heading3,
      keywords: 'heading h3',
      run: (c) => c.setHeading({ level: 3 }),
    },
    {
      label: 'Gambar',
      hint: 'Dari pustaka media',
      icon: ImageIcon,
      keywords: 'image foto gambar',
      picker: true,
    },
    {
      label: 'Callout',
      hint: 'Kotak catatan berwarna',
      icon: Info,
      keywords: 'callout note catatan info peringatan kotak',
      run: (c) => c.wrapIn('callout', { type: 'info' }),
    },
    {
      label: 'Tombol',
      hint: 'Tautan ajakan bertindak',
      icon: MousePointerClick,
      keywords: 'button tombol cta link tautan',
      action: insertButton,
    },
    {
      label: 'Daftar poin',
      hint: 'Daftar tak berurut',
      icon: List,
      keywords: 'bullet list daftar',
      run: (c) => c.toggleBulletList(),
    },
    {
      label: 'Daftar angka',
      hint: 'Daftar berurut',
      icon: ListOrdered,
      keywords: 'ordered number list daftar',
      run: (c) => c.toggleOrderedList(),
    },
    {
      label: 'Kutipan',
      hint: 'Sorot kutipan',
      icon: Quote,
      keywords: 'quote blockquote',
      run: (c) => c.toggleBlockquote(),
    },
    {
      label: 'Blok kode',
      hint: 'Cuplikan kode',
      icon: SquareCode,
      keywords: 'code kode',
      run: (c) => c.toggleCodeBlock(),
    },
    {
      label: 'Garis pemisah',
      hint: 'Pemisah bagian',
      icon: Minus,
      keywords: 'divider hr garis',
      run: (c) => c.setHorizontalRule(),
    },
  ];

  let inserter = $state({ visible: false, top: 0, left: 0 });
  let menu = $state({
    open: false,
    source: 'slash' as 'slash' | 'button',
    top: 0,
    left: 0,
    query: '',
    index: 0,
    from: 0,
    to: 0,
  });

  const filteredBlocks = $derived(
    blockItems.filter((item) =>
      `${item.label} ${item.keywords}`
        .toLowerCase()
        .includes(menu.query.toLowerCase()),
    ),
  );

  function closeMenu(): void {
    menu.open = false;
  }

  /** Tracks the caret line to place the "+" inserter and the "/" command menu. */
  function syncBlockUi(): void {
    if (minimal || !editor || !host || mode !== 'visual') return;

    const { selection } = editor.state;
    const { $from: caret } = selection;

    if (!selection.empty || caret.parent.type.name !== 'paragraph') {
      inserter.visible = false;
      if (menu.source === 'slash') closeMenu();
      return;
    }

    const text = caret.parent.textContent;
    const bounds = host.getBoundingClientRect();
    const coords = editor.view.coordsAtPos(caret.pos);
    const top = coords.bottom - bounds.top;
    const left = Math.max(0, coords.left - bounds.left);

    inserter.visible = text === '' && caret.depth === 1;
    inserter.top = coords.top - bounds.top;

    const match = /^\/([\p{L}\d]*)$/u.exec(text);

    if (match) {
      if (!menu.open || menu.source !== 'slash') menu.index = 0;
      if (menu.query !== match[1]) menu.index = 0;
      menu.open = true;
      menu.source = 'slash';
      menu.query = match[1];
      menu.from = caret.start();
      menu.to = caret.end();
      menu.top = top + 6;
      menu.left = Math.min(left, Math.max(0, bounds.width - 260));
    } else if (menu.source === 'slash') {
      closeMenu();
    }
  }

  function openButtonMenu(): void {
    if (!editor || !host) return;

    const bounds = host.getBoundingClientRect();
    const coords = editor.view.coordsAtPos(editor.state.selection.from);

    Object.assign(menu, {
      open: true,
      source: 'button',
      query: '',
      index: 0,
      top: coords.bottom - bounds.top + 6,
      left: Math.min(
        Math.max(0, coords.left - bounds.left),
        Math.max(0, bounds.width - 260),
      ),
    });
    editor.commands.focus();
  }

  function runBlock(item: BlockItem): void {
    if (!editor) return;

    const chain = editor.chain().focus();

    if (menu.source === 'slash')
      chain.deleteRange({ from: menu.from, to: menu.to });

    (item.run ? item.run(chain) : chain).run();
    closeMenu();

    if (item.action) item.action();

    if (item.picker) openPicker('insert');
  }

  function openPicker(mode: 'insert' | 'replace'): void {
    pickerMode = mode;
    pickerOpen = true;
  }

  /** Reads the block under the caret that the block bar can edit. */
  function readBlock(): BlockInfo | null {
    if (minimal || !editor) return null;

    const { selection } = editor.state;

    if (selection instanceof NodeSelection) {
      const kind = selection.node.type.name;

      return kind === 'image' || kind === 'buttonBlock'
        ? { kind, pos: selection.from, attrs: selection.node.attrs }
        : null;
    }

    for (let depth = selection.$from.depth; depth > 0; depth--) {
      const node = selection.$from.node(depth);

      if (node.type.name === 'callout') {
        return {
          kind: 'callout',
          pos: selection.$from.before(depth),
          attrs: node.attrs,
        };
      }
    }

    return null;
  }

  function patchBlock(patch: Record<string, unknown>): void {
    const target = blockInfo;

    if (!editor || !target) return;

    editor
      .chain()
      .command(({ tr }) => {
        const node = tr.doc.nodeAt(target.pos);

        if (!node) return false;

        tr.setNodeMarkup(target.pos, undefined, { ...node.attrs, ...patch });

        return true;
      })
      .run();
  }

  function insertButton(): void {
    if (!editor) return;

    editor.chain().focus().insertContent({ type: 'buttonBlock' }).run();

    const before = editor.state.selection.$from.nodeBefore;

    if (before?.type.name === 'buttonBlock') {
      editor.commands.setNodeSelection(
        editor.state.selection.from - before.nodeSize,
      );
    }
  }

  /** Top-level block containing the selection, used for move/duplicate/delete. */
  function topBlock(): { pos: number; size: number } | null {
    if (!editor) return null;

    const { selection } = editor.state;

    if (selection instanceof NodeSelection && selection.$from.depth === 0) {
      return { pos: selection.from, size: selection.node.nodeSize };
    }

    if (selection.$from.depth < 1) return null;

    return {
      pos: selection.$from.before(1),
      size: selection.$from.node(1).nodeSize,
    };
  }

  function deleteBlock(range = topBlock()): void {
    if (!editor || !range) return;

    editor
      .chain()
      .focus()
      .deleteRange({ from: range.pos, to: range.pos + range.size })
      .run();
  }

  function duplicateBlock(): void {
    const range = topBlock();

    if (!editor || !range) return;

    const node = editor.state.doc.nodeAt(range.pos);

    if (node) {
      editor
        .chain()
        .focus()
        .insertContentAt(range.pos + range.size, node.toJSON())
        .run();
    }
  }

  function moveBlock(direction: -1 | 1): void {
    const range = topBlock();

    if (!editor || !range) return;

    const { doc } = editor.state;
    const node = doc.nodeAt(range.pos);
    const neighbour =
      direction < 0
        ? doc.resolve(range.pos).nodeBefore
        : doc.nodeAt(range.pos + range.size);

    if (!node || !neighbour) return;

    const target =
      direction < 0
        ? range.pos - neighbour.nodeSize
        : range.pos + neighbour.nodeSize;

    editor
      .chain()
      .focus()
      .command(({ tr }) => {
        tr.delete(range.pos, range.pos + range.size).insert(target, node);

        return true;
      })
      .run();
  }

  function canMove(direction: -1 | 1): boolean {
    const range = topBlock();

    if (!editor || !range) return false;

    return direction < 0
      ? editor.state.doc.resolve(range.pos).nodeBefore !== null
      : range.pos + range.size < editor.state.doc.content.size;
  }

  function closeContext(): void {
    ctx.open = false;
  }

  function openContext(event: MouseEvent): void {
    if (!editor || mode !== 'visual') return;

    event.preventDefault();
    closeMenu();

    const { state, view } = editor;
    const { from, to } = state.selection;
    const hit = view.posAtCoords({ left: event.clientX, top: event.clientY });

    if (hit && (hit.pos < from || hit.pos > to)) {
      const node = hit.inside >= 0 ? state.doc.nodeAt(hit.inside) : null;

      if (node?.isAtom && node.type.spec.selectable !== false && !node.isText) {
        editor.commands.setNodeSelection(hit.inside);
      } else {
        editor.commands.setTextSelection(hit.pos);
      }
    }

    editor.commands.focus();
    Object.assign(ctx, { open: true, x: event.clientX, y: event.clientY });
  }

  async function pasteFromClipboard(): Promise<void> {
    try {
      editor?.view.pasteText(await navigator.clipboard.readText());
    } catch {
      window.alert('Izin papan klip ditolak. Gunakan Ctrl+V untuk menempel.');
    }
  }

  function clipboardCommand(command: 'cut' | 'copy'): void {
    editor?.view.focus();
    document.execCommand(command);
  }

  const contextGroups = $derived.by<ContextItem[][]>(() => {
    void tick;

    if (!ctx.open || !editor) return [];

    const instance = editor;
    const hasSelection =
      !instance.state.selection.empty ||
      instance.state.selection instanceof NodeSelection;
    const groups: ContextItem[][] = [
      [
        {
          label: 'Potong',
          icon: Scissors,
          disabled: !hasSelection,
          action: () => clipboardCommand('cut'),
        },
        {
          label: 'Salin',
          icon: Copy,
          disabled: !hasSelection,
          action: () => clipboardCommand('copy'),
        },
        { label: 'Tempel', icon: ClipboardPaste, action: pasteFromClipboard },
      ],
      [
        {
          label: 'Tebal',
          icon: Bold,
          on: active('bold'),
          action: () => instance.chain().focus().toggleBold().run(),
        },
        {
          label: 'Miring',
          icon: Italic,
          on: active('italic'),
          action: () => instance.chain().focus().toggleItalic().run(),
        },
        {
          label: 'Garis bawah',
          icon: Underline,
          on: active('underline'),
          action: () => instance.chain().focus().toggleUnderline().run(),
        },
        {
          label: 'Coret',
          icon: Strikethrough,
          on: active('strike'),
          action: () => instance.chain().focus().toggleStrike().run(),
        },
      ],
    ];

    if (minimal) return groups;

    groups[1].push({
      label: active('link') ? 'Ubah tautan' : 'Tautan',
      icon: Link,
      on: active('link'),
      action: setLink,
    });

    const turnInto = blockItems
      .filter((item) =>
        ['Paragraf', 'Judul 2', 'Judul 3', 'Kutipan', 'Callout'].includes(
          item.label,
        ),
      )
      .map<ContextItem>((item) => ({
        label: `Ubah ke ${item.label.toLowerCase()}`,
        icon: item.icon,
        action: () => item.run && item.run(instance.chain().focus()).run(),
      }));

    groups.push(turnInto, [
      {
        label: 'Sisipkan blok...',
        icon: Plus,
        action: () => setTimeout(openButtonMenu),
      },
    ]);

    if (blockInfo?.kind === 'image') {
      groups.push([
        ...(['left', 'center', 'right'] as const).map<ContextItem>((align) => ({
          label: { left: 'Rata kiri', center: 'Rata tengah', right: 'Rata kanan' }[
            align
          ],
          icon: { left: AlignLeft, center: AlignCenter, right: AlignRight }[
            align
          ],
          on: blockInfo?.attrs.align === align,
          action: () => patchBlock({ align }),
        })),
        {
          label: 'Ganti gambar...',
          icon: Replace,
          action: () => openPicker('replace'),
        },
      ]);
    }

    groups.push([
      { label: 'Duplikat blok', icon: CopyPlus, action: duplicateBlock },
      {
        label: 'Pindah ke atas',
        icon: ArrowUp,
        disabled: !canMove(-1),
        action: () => moveBlock(-1),
      },
      {
        label: 'Pindah ke bawah',
        icon: ArrowDown,
        disabled: !canMove(1),
        action: () => moveBlock(1),
      },
      {
        label: 'Hapus blok',
        icon: Trash,
        danger: true,
        action: () => deleteBlock(),
      },
    ]);

    return groups;
  });

  function runContext(item: ContextItem): void {
    if (item.disabled) return;

    closeContext();
    item.action();
  }

  $effect(() => {
    if (!ctx.open || !ctxEl) return;

    const bounds = ctxEl.getBoundingClientRect();
    const x = Math.max(8, Math.min(ctx.x, window.innerWidth - bounds.width - 8));
    const y = Math.max(
      8,
      Math.min(ctx.y, window.innerHeight - bounds.height - 8),
    );

    if (x !== ctx.x || y !== ctx.y) Object.assign(ctx, { x, y });
  });

  function handleMenuKey(event: KeyboardEvent): boolean {
    if (!menu.open) return false;

    const total = filteredBlocks.length;

    if (event.key === 'Escape') {
      closeMenu();
    } else if (total > 0 && event.key === 'ArrowDown') {
      menu.index = (menu.index + 1) % total;
    } else if (total > 0 && event.key === 'ArrowUp') {
      menu.index = (menu.index - 1 + total) % total;
    } else if (total > 0 && (event.key === 'Enter' || event.key === 'Tab')) {
      runBlock(filteredBlocks[menu.index]);
    } else {
      return false;
    }

    event.preventDefault();

    return true;
  }

  function active(
    nameOrAttrs: string | Record<string, unknown>,
    attrs?: Record<string, unknown>,
  ): boolean {
    void tick;
    return editor?.isActive(nameOrAttrs as string, attrs) ?? false;
  }

  function pickMedia(media: MediaItem): void {
    if (pickerMode === 'replace' && media.url) {
      patchBlock({ src: media.url, alt: media.alt ?? media.name });
    } else {
      insertImage(media);
    }
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
        ...(minimal ? [] : [FigureImage, Callout, ButtonBlock]),
        Placeholder.configure({ placeholder }),
        ...(minimal
          ? []
          : [TextAlign.configure({ types: ['heading', 'paragraph'] })]),
        CharacterCount,
      ],
      editorProps: {
        attributes: {
          class: 'rte-content wysiwyg-content-wrapper',
          spellcheck: 'true',
        },
        handleKeyDown: (_view, event) => handleMenuKey(event),
        handleDOMEvents: {
          contextmenu: (_view, event) => {
            openContext(event);

            return true;
          },
        },
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
        blockInfo = readBlock();
        syncBlockUi();
      },
      onBlur: () => {
        if (menu.source === 'slash') closeMenu();
      },
    });

    editor = instance;
    words = instance.storage.characterCount.words();

    return () => instance.destroy();
  });
</script>

<svelte:window
  onpointerdown={(event) => {
    if (ctx.open && !ctxEl?.contains(event.target as Node)) closeContext();
  }}
  onkeydown={(event) => {
    if (ctx.open && event.key === 'Escape') closeContext();
  }}
  onresize={closeContext}
  onscroll={closeContext}
/>

<div
  class="rte"
  class:is-invalid={invalid}
  class:rte-minimal={minimal}
  bind:this={host}
>
  <input type="hidden" {name} value={html} />

  <div class="rte-top">
    <div
      class="rte-toolbar"
      class:open={toolbarOpen}
      id="rte-toolbar-{name}"
      role="toolbar"
      aria-label="Pemformatan teks"
    >
      {#if mode === 'visual' && editor}
        {#if !minimal}
          <select
            class="form-select form-select-sm rte-select rte-adv"
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
            value={String(
              [1, 2, 3].find((level) => active('heading', { level })) ?? 0,
            )}
          >
            <option value="0">Paragraf</option>
            <option value="1">Judul 1</option>
            <option value="2">Judul 2</option>
            <option value="3">Judul 3</option>
          </select>

          <span class="rte-sep rte-adv"></span>
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
            class="rte-btn rte-adv"
            class:on={active('code')}
            title="Kode inline"
            onclick={() => editor?.chain().focus().toggleCode().run()}
            ><Code size={16} /></button
          >
        {/if}

        {#if !minimal}
          <span class="rte-sep rte-adv"></span>

          <button
            type="button"
            class="rte-btn rte-adv"
            class:on={active('heading', { level: 1 })}
            title="Judul 1"
            onclick={() =>
              editor?.chain().focus().toggleHeading({ level: 1 }).run()}
            ><Heading1 size={16} /></button
          >
          <button
            type="button"
            class="rte-btn rte-adv"
            class:on={active('heading', { level: 2 })}
            title="Judul 2"
            onclick={() =>
              editor?.chain().focus().toggleHeading({ level: 2 }).run()}
            ><Heading2 size={16} /></button
          >
          <button
            type="button"
            class="rte-btn rte-adv"
            class:on={active('heading', { level: 3 })}
            title="Judul 3"
            onclick={() =>
              editor?.chain().focus().toggleHeading({ level: 3 }).run()}
            ><Heading3 size={16} /></button
          >

          <span class="rte-sep rte-adv"></span>
        {/if}
        <span class="rte-sep rte-adv"></span>

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
            class="rte-btn rte-adv"
            class:on={active('codeBlock')}
            title="Blok kode"
            onclick={() => editor?.chain().focus().toggleCodeBlock().run()}
            ><SquareCode size={16} /></button
          >
          <button
            type="button"
            class="rte-btn rte-adv"
            title="Garis pemisah"
            onclick={() => editor?.chain().focus().setHorizontalRule().run()}
            ><Minus size={16} /></button
          >

          <span class="rte-sep rte-adv"></span>

          <button
            type="button"
            class="rte-btn rte-adv"
            class:on={active({ textAlign: 'left' })}
            title="Rata kiri"
            onclick={() => editor?.chain().focus().setTextAlign('left').run()}
            ><AlignLeft size={16} /></button
          >
          <button
            type="button"
            class="rte-btn rte-adv"
            class:on={active({ textAlign: 'center' })}
            title="Rata tengah"
            onclick={() => editor?.chain().focus().setTextAlign('center').run()}
            ><AlignCenter size={16} /></button
          >
          <button
            type="button"
            class="rte-btn rte-adv"
            class:on={active({ textAlign: 'right' })}
            title="Rata kanan"
            onclick={() => editor?.chain().focus().setTextAlign('right').run()}
            ><AlignRight size={16} /></button
          >

          <span class="rte-sep rte-adv"></span>

          <button
            type="button"
            class="rte-btn rte-adv"
            class:on={active('link')}
            title="Tautan"
            onclick={setLink}><Link size={16} /></button
          >
          <button
            type="button"
            class="rte-btn rte-adv"
            title="Sisipkan gambar dari pustaka media"
            disabled={uploading}
            onclick={() => openPicker('insert')}><ImageIcon size={16} /></button
          >
        {/if}
        <span class="rte-sep rte-adv"></span>

        {#if !minimal}
          <button
            type="button"
            class="rte-btn rte-adv"
            title="Sisipkan blok"
            onclick={openButtonMenu}><Plus size={16} /></button
          >
        {/if}

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
        {#if !minimal}
          <button
            type="button"
            class="rte-toggle"
            aria-expanded={toolbarOpen}
            aria-controls="rte-toolbar-{name}"
            onclick={() => (toolbarOpen = !toolbarOpen)}
            ><span>Lainnya</span><ChevronDown size={16} /></button
          >
        {/if}
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

    {#if blockInfo && mode === 'visual'}
      <div class="rte-blockbar" role="toolbar" aria-label="Pengaturan blok">
        {#if blockInfo.kind === 'image'}
          <span class="rte-bar-title">Gambar</span>
          {#each [{ align: 'left', label: 'Rata kiri', icon: AlignLeft }, { align: 'center', label: 'Rata tengah', icon: AlignCenter }, { align: 'right', label: 'Rata kanan', icon: AlignRight }] as option (option.align)}
            <button
              type="button"
              class="rte-btn"
              class:on={blockInfo.attrs.align === option.align}
              title={option.label}
              onclick={() =>
                patchBlock({
                  align: option.align,
                  width:
                    option.align !== 'center' && !blockInfo?.attrs.width
                      ? 50
                      : blockInfo?.attrs.width,
                })}><option.icon size={16} /></button
            >
          {/each}
          <select
            class="form-select form-select-sm rte-select"
            aria-label="Lebar gambar"
            value={String(blockInfo.attrs.width ?? '')}
            onchange={(event) =>
              patchBlock({ width: Number(event.currentTarget.value) || null })}
          >
            <option value="">Lebar otomatis</option>
            {#each imageWidths as width (width)}
              <option value={String(width)}>{width}%</option>
            {/each}
          </select>
          <input
            class="form-control form-control-sm rte-field"
            placeholder="Teks alternatif"
            aria-label="Teks alternatif"
            value={blockInfo.attrs.alt ?? ''}
            oninput={(event) => patchBlock({ alt: event.currentTarget.value })}
          />
          <input
            class="form-control form-control-sm rte-field"
            placeholder="Keterangan gambar"
            aria-label="Keterangan gambar"
            value={blockInfo.attrs.caption ?? ''}
            oninput={(event) =>
              patchBlock({ caption: event.currentTarget.value })}
          />
          <button
            type="button"
            class="rte-btn"
            title="Ganti gambar"
            onclick={() => openPicker('replace')}><Replace size={16} /></button
          >
        {:else if blockInfo.kind === 'buttonBlock'}
          <span class="rte-bar-title">Tombol</span>
          <input
            class="form-control form-control-sm rte-field"
            placeholder="Teks tombol"
            aria-label="Teks tombol"
            value={blockInfo.attrs.text ?? ''}
            oninput={(event) => patchBlock({ text: event.currentTarget.value })}
          />
          <input
            class="form-control form-control-sm rte-field"
            placeholder="https://..."
            aria-label="Alamat tautan tombol"
            value={blockInfo.attrs.href ?? ''}
            onchange={(event) => {
              const href = safeHref(event.currentTarget.value);
              event.currentTarget.value = href;
              patchBlock({ href });
            }}
          />
          <select
            class="form-select form-select-sm rte-select"
            aria-label="Gaya tombol"
            value={blockInfo.attrs.variant}
            onchange={(event) => patchBlock({ variant: event.currentTarget.value })}
          >
            <option value="solid">Solid</option>
            <option value="outline">Garis tepi</option>
          </select>
          {#each [{ align: 'left', label: 'Rata kiri', icon: AlignLeft }, { align: 'center', label: 'Rata tengah', icon: AlignCenter }, { align: 'right', label: 'Rata kanan', icon: AlignRight }] as option (option.align)}
            <button
              type="button"
              class="rte-btn"
              class:on={blockInfo.attrs.align === option.align}
              title={option.label}
              onclick={() => patchBlock({ align: option.align })}
              ><option.icon size={16} /></button
            >
          {/each}
        {:else}
          <span class="rte-bar-title">Callout</span>
          {#each CALLOUT_TYPES as type (type)}
            <button
              type="button"
              class="rte-chip is-{type}"
              class:on={blockInfo.attrs.type === type}
              onclick={() => patchBlock({ type })}>{calloutLabels[type]}</button
            >
          {/each}
          <button
            type="button"
            class="rte-chip"
            title="Keluarkan isi dari callout"
            onclick={() => editor?.chain().focus().lift('callout').run()}
            >Lepas kotak</button
          >
        {/if}
        <button
          type="button"
          class="rte-btn rte-bar-delete"
          title="Hapus blok"
          onclick={() =>
            blockInfo &&
            deleteBlock({
              pos: blockInfo.pos,
              size: editor?.state.doc.nodeAt(blockInfo.pos)?.nodeSize ?? 0,
            })}><Trash size={16} /></button
        >
      </div>
    {/if}
  </div>

  <div class="rte-body" hidden={mode !== 'visual'} bind:this={element}></div>

  {#if !minimal && mode === 'visual'}
    {#if inserter.visible && !menu.open}
      <button
        type="button"
        class="rte-inserter"
        title="Sisipkan blok"
        aria-label="Sisipkan blok"
        style="top: {inserter.top}px"
        onmousedown={(event) => event.preventDefault()}
        onclick={openButtonMenu}><Plus size={16} /></button
      >
    {/if}

    {#if menu.open}
      <div
        class="rte-menu"
        role="listbox"
        aria-label="Sisipkan blok"
        style="top: {menu.top}px; left: {menu.left}px"
        onmousedown={(event) => event.preventDefault()}
      >
        {#each filteredBlocks as item, position (item.label)}
          <button
            type="button"
            role="option"
            aria-selected={position === menu.index}
            class="rte-menu-item"
            class:on={position === menu.index}
            onmouseenter={() => (menu.index = position)}
            onclick={() => runBlock(item)}
          >
            <item.icon size={18} />
            <span><strong>{item.label}</strong><small>{item.hint}</small></span>
          </button>
        {:else}
          <p class="rte-menu-empty">Blok tidak ditemukan</p>
        {/each}
      </div>
    {/if}
  {/if}

  {#if mode === 'html'}
    <textarea
      class="rte-html"
      spellcheck="false"
      aria-label="Kode HTML"
      bind:value={html}></textarea>
  {/if}

  {#if ctx.open && contextGroups.length > 0}
    <div
      class="rte-context"
      role="menu"
      aria-label="Menu konteks editor"
      style="top: {ctx.y}px; left: {ctx.x}px"
      bind:this={ctxEl}
      onmousedown={(event) => event.preventDefault()}
      oncontextmenu={(event) => event.preventDefault()}
    >
      {#each contextGroups as group, groupIndex (groupIndex)}
        {#if groupIndex > 0}<hr class="rte-context-sep" />{/if}
        {#each group as item (item.label)}
          <button
            type="button"
            role="menuitem"
            class="rte-context-item"
            class:on={item.on}
            class:danger={item.danger}
            disabled={item.disabled}
            onclick={() => runContext(item)}
          >
            <item.icon size={16} />
            <span>{item.label}</span>
          </button>
        {/each}
      {/each}
    </div>
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
    onSelect={pickMedia}
  />
{/if}

<style>
  .rte {
    border: 1px solid var(--neo-border-color);
    border-radius: 0.5rem;
    background: var(--neo-body-bg);
    position: relative;
  }
  .rte.is-invalid {
    border-color: var(--neo-form-invalid-border-color, #dc3545);
  }
  .rte-top {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: flex-start;
    gap: 0.5rem;
    border-bottom: 1px solid var(--neo-border-color);
    background: var(--neo-tertiary-bg);
    border-radius: 0.5rem 0.5rem 0 0;
    position: sticky;
    top: 0;
    z-index: 5;
  }
  .rte-blockbar {
    display: flex;
    flex: 0 0 100%;
    flex-wrap: wrap;
    align-items: center;
    order: 3;
    gap: 0.375rem;
    padding: 0.375rem;
    border-top: 1px solid var(--neo-border-color);
    background: var(--neo-body-bg);
    border-radius: 0 0 0 0;
  }
  .rte-bar-title {
    padding: 0 0.5rem;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--neo-secondary-color);
  }
  .rte-field {
    width: 11rem;
  }
  .rte-bar-delete {
    margin-left: auto;
    color: var(--neo-danger, #dc3545);
  }
  .rte-chip {
    height: 2rem;
    padding: 0 0.75rem;
    border: 1px solid var(--neo-border-color);
    border-radius: 999px;
    background: transparent;
    color: var(--neo-body-color);
    font-size: 0.8125rem;
  }
  .rte-chip.on {
    background: var(--neo-primary);
    border-color: var(--neo-primary);
    color: #fff;
  }
  .rte-context {
    position: fixed;
    z-index: 1080;
    min-width: 13.5rem;
    max-height: calc(100vh - 1rem);
    overflow-y: auto;
    padding: 0.25rem;
    border: 1px solid var(--neo-border-color);
    border-radius: 0.5rem;
    background: var(--neo-body-bg);
    box-shadow: 0 0.5rem 1.5rem rgb(0 0 0 / 0.2);
  }
  .rte-context-sep {
    margin: 0.25rem 0;
    border: 0;
    border-top: 1px solid var(--neo-border-color);
    opacity: 1;
  }
  .rte-context-item {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    width: 100%;
    padding: 0.375rem 0.625rem;
    border: 0;
    border-radius: 0.375rem;
    background: transparent;
    color: var(--neo-body-color);
    font-size: 0.875rem;
    text-align: left;
  }
  .rte-context-item:hover:not(:disabled) {
    background: var(--neo-secondary-bg);
  }
  .rte-context-item.on {
    color: var(--neo-primary);
    font-weight: 600;
  }
  .rte-context-item.danger {
    color: var(--neo-danger, #dc3545);
  }
  .rte-context-item:disabled {
    opacity: 0.4;
  }
  .rte-toggle {
    display: inline-flex;
    order: 1;
    align-items: center;
    gap: 0.25rem;
    margin-left: 0.25rem;
    padding: 0 0.625rem;
    height: 2rem;
    border: 1px solid var(--neo-border-color);
    border-radius: 0.375rem;
    background: var(--neo-body-bg);
    color: var(--neo-body-color);
    font-size: 0.8125rem;
  }
  .rte-toggle :global(svg) {
    transition: transform 0.15s;
  }
  .rte-toggle[aria-expanded='true'] :global(svg) {
    transform: rotate(180deg);
  }
  .rte-adv {
    order: 2;
  }
  .rte-toolbar:not(.open) .rte-adv {
    display: none;
  }
  :global(.admin-shell) .rte-top {
    top: 69px;
  }
  .rte-inserter {
    position: absolute;
    left: 0.25rem;
    z-index: 3;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.5rem;
    height: 1.5rem;
    margin-top: 0.1rem;
    border: 1px solid var(--neo-border-color);
    border-radius: 50%;
    background: var(--neo-body-bg);
    color: var(--neo-secondary-color);
  }
  .rte-inserter:hover {
    background: var(--neo-primary);
    border-color: var(--neo-primary);
    color: #fff;
  }
  .rte-menu {
    position: absolute;
    z-index: 6;
    width: 16rem;
    max-height: 18rem;
    overflow-y: auto;
    padding: 0.25rem;
    border: 1px solid var(--neo-border-color);
    border-radius: 0.5rem;
    background: var(--neo-body-bg);
    box-shadow: 0 0.5rem 1.5rem rgb(0 0 0 / 0.15);
  }
  .rte-menu-item {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    width: 100%;
    padding: 0.4rem 0.5rem;
    border: 0;
    border-radius: 0.375rem;
    background: transparent;
    color: var(--neo-body-color);
    text-align: left;
  }
  .rte-menu-item.on {
    background: var(--neo-secondary-bg);
  }
  .rte-menu-item span {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
  }
  .rte-menu-item small,
  .rte-menu-empty {
    color: var(--neo-secondary-color);
    font-size: 0.75rem;
  }
  .rte-menu-empty {
    margin: 0;
    padding: 0.5rem;
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
  }
  .rte-body :global(.rte-content .ProseMirror-selectednode) {
    outline: 2px solid var(--neo-primary);
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
    border-radius: 0 0 0.5rem 0.5rem;
    background: var(--neo-tertiary-bg);
    font-size: 0.8125rem;
    color: var(--neo-secondary-color);
  }
</style>
