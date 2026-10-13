<script lang="ts">
  import AlignCenter from '@lucide/svelte/icons/align-center';
  import AlignLeft from '@lucide/svelte/icons/align-left';
  import AlignRight from '@lucide/svelte/icons/align-right';
  import ArrowDown from '@lucide/svelte/icons/arrow-down';
  import ArrowUp from '@lucide/svelte/icons/arrow-up';
  import Bold from '@lucide/svelte/icons/bold';
  import Info from '@lucide/svelte/icons/info';
  import MousePointerClick from '@lucide/svelte/icons/mouse-pointer-click';
  import Replace from '@lucide/svelte/icons/replace';
  import Trash from '@lucide/svelte/icons/trash';
  import Heading1 from '@lucide/svelte/icons/heading-1';
  import Heading2 from '@lucide/svelte/icons/heading-2';
  import Heading3 from '@lucide/svelte/icons/heading-3';
  import ImageIcon from '@lucide/svelte/icons/image';
  import ChevronDown from '@lucide/svelte/icons/chevron-down';
  import ChevronLeft from '@lucide/svelte/icons/chevron-left';
  import EllipsisVertical from '@lucide/svelte/icons/ellipsis-vertical';
  import PanelLeftOpen from '@lucide/svelte/icons/panel-left-open';
  import PanelRight from '@lucide/svelte/icons/panel-right';
  import Search from '@lucide/svelte/icons/search';
  import X from '@lucide/svelte/icons/x';
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
  import Undo2 from '@lucide/svelte/icons/undo-2';
  import { Editor, type ChainedCommands } from '@tiptap/core';
  import CharacterCount from '@tiptap/extension-character-count';
  import Placeholder from '@tiptap/extension-placeholder';
  import TextAlign from '@tiptap/extension-text-align';
  import { NodeSelection } from '@tiptap/pm/state';
  import StarterKit from '@tiptap/starter-kit';
  import { Link as InertiaLink } from '@inertiajs/svelte';
  import { onMount, type Snippet } from 'svelte';
  import { useAdminSidebarState } from '@/layouts/admin/sidebar-state.svelte';
  import HtmlCodeEditor from '@/components/ui/html-code-editor.svelte';
  import MediaPickerModal from '@/components/media/media-picker-modal.svelte';
  import {
    ButtonBlock,
    CALLOUT_TYPES,
    Callout,
    CODE_THEMES,
    CodeBlockHighlight,
    FigureImage,
    safeHref,
  } from '@/lib/editor-blocks';
  import { normalizeCodeTitle } from '@/lib/code-card';
  import { highlightSource, languageNames } from '@/lib/highlight';
  import Select from '@/components/ui/select.svelte';
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
    shell = false,
    backHref = '',
    documentTitle = '',
    rootLabel = 'Dokumen',
    header,
    settings,
    actions,
    after,
  }: {
    name: string;
    value?: string | null;
    placeholder?: string;
    invalid?: boolean;
    /** Public-safe mode: basic formatting only, no images, links, headings or HTML tab. */
    minimal?: boolean;
    /** Gutenberg-style full-screen editor: top bar, canvas and a settings sidebar. */
    shell?: boolean;
    /** Target of the back arrow in the shell top bar. */
    backHref?: string;
    /** Title shown in the centre of the shell top bar. */
    documentTitle?: string;
    /** First crumb of the breadcrumb under the canvas. */
    rootLabel?: string;
    /** Rendered above the content (e.g. the title field). */
    header?: Snippet;
    /** Content of the "document" tab of the shell sidebar. */
    settings?: Snippet;
    /** Buttons on the right of the shell top bar (e.g. save). */
    actions?: Snippet<
      [{ mode: 'visual' | 'html'; setMode: (next: 'visual' | 'html') => void }]
    >;
    /** Rendered below the content (e.g. validation feedback). */
    after?: Snippet;
  } = $props();

  /** Admin sidebar toggle, since the shell replaces the admin navbar. */
  // svelte-ignore state_referenced_locally
  const adminSidebar = shell
    ? (() => {
        try {
          return useAdminSidebarState();
        } catch {
          return null;
        }
      })()
    : null;

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
  // Floating language picker anchored to the code block under the caret.
  let codeBadge = $state({ visible: false, top: 0, right: 0 });
  let pickerMode = $state<'insert' | 'replace'>('insert');
  let ctx = $state({
    open: false,
    x: 0,
    y: 0,
    kind: 'context' as 'context' | 'type' | 'align',
  });
  let ctxEl = $state<HTMLDivElement>();
  let menuEl = $state<HTMLDivElement>();
  let focused = $state(false);
  let sidebarOpen = $state(true);
  let sidebarTab = $state<'document' | 'block'>('document');
  let blockTb = $state({ visible: false, top: 0, left: 0 });

  type BlockItem = {
    label: string;
    hint: string;
    icon: typeof Pilcrow;
    keywords: string;
    run?: (chain: ChainedCommands) => ChainedCommands;
    picker?: boolean;
    action?: () => void;
  };

  type BlockKind = 'image' | 'buttonBlock' | 'callout' | 'codeBlock';
  type BlockInfo = {
    kind: BlockKind;
    pos: number;
    attrs: Record<string, any>;
  };
  type ContextItem = {
    label: string;
    icon?: typeof Pilcrow;
    shortcut?: string;
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
    dark: 'Gelap',
  };

  const blockLabels: Record<string, string> = {
    paragraph: 'Paragraf',
    bulletList: 'Daftar poin',
    orderedList: 'Daftar angka',
    blockquote: 'Kutipan',
    codeBlock: 'Blok kode',
    callout: 'Callout',
    image: 'Gambar',
    buttonBlock: 'Tombol',
    horizontalRule: 'Garis pemisah',
  };
  const blockDescriptions: Record<string, string> = {
    paragraph: 'Mulai dengan komponen dasar dari semua cerita.',
    heading: 'Beri judul pada bagian tulisan Anda.',
    bulletList: 'Buat daftar poin biasa.',
    orderedList: 'Buat daftar bernomor.',
    blockquote: 'Beri penekanan visual pada kutipan.',
    codeBlock: 'Tampilkan cuplikan kode dengan pewarnaan sintaks.',
    callout: 'Kotak catatan berwarna untuk info penting.',
    image: 'Sisipkan gambar dari pustaka media.',
    buttonBlock: 'Tautan ajakan bertindak berbentuk tombol.',
    horizontalRule: 'Pisahkan bagian tulisan dengan garis.',
  };
  const blockIcons: Record<string, typeof Pilcrow> = {
    paragraph: Pilcrow,
    heading: Heading2,
    bulletList: List,
    orderedList: ListOrdered,
    blockquote: Quote,
    codeBlock: SquareCode,
    callout: Info,
    image: ImageIcon,
    buttonBlock: MousePointerClick,
    horizontalRule: Minus,
  };
  const alignKeys = ['left', 'center', 'right'] as const;
  const headingLevels = [1, 2, 3] as const;
  const alignLabels = {
    left: 'Rata kiri',
    center: 'Rata tengah',
    right: 'Rata kanan',
  } as const;
  const alignIcons = {
    left: AlignLeft,
    center: AlignCenter,
    right: AlignRight,
  } as const;

  const imageWidths = [25, 50, 75, 100];
  const headingItems = [
    { value: '0', label: 'Paragraf' },
    { value: '1', label: 'Judul 1' },
    { value: '2', label: 'Judul 2' },
    { value: '3', label: 'Judul 3' },
  ];
  const widthItems = imageWidths.map((width) => ({
    value: String(width),
    label: `${width}%`,
  }));
  const buttonVariantItems = [
    { value: 'solid', label: 'Solid' },
    { value: 'outline', label: 'Garis tepi' },
  ];
  const languageItems = [
    ...languageNames().map((name) => ({ value: name, label: name })),
    { value: 'plaintext', label: 'Teks biasa' },
  ];
  const themeItems = CODE_THEMES.filter((theme) => theme.value !== '').map(
    (theme) => ({
      value: theme.value,
      label: `Tema: ${theme.label}`,
    }),
  );
  const defaultThemeLabel = `Tema: ${CODE_THEMES.find((theme) => theme.value === '')?.label ?? 'Otomatis'}`;

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
    source: 'slash' as 'slash' | 'button' | 'top',
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

  /** Top-level block under the selection, for the inspector and breadcrumb. */
  const currentBlock = $derived.by(() => {
    void tick;

    const range = editor ? topBlock() : null;
    const node = range ? editor?.state.doc.nodeAt(range.pos) : null;

    if (!range || !node) return null;

    const kind = node.type.name;

    return {
      kind,
      pos: range.pos,
      label:
        kind === 'heading'
          ? `Judul ${node.attrs.level}`
          : (blockLabels[kind] ?? kind),
    };
  });

  /** Places the floating block toolbar above the block under the caret. */
  function syncToolbar(): void {
    const range = editor ? topBlock() : null;
    const dom = range ? editor?.view.nodeDOM(range.pos) : null;

    if (
      !editor ||
      mode !== 'visual' ||
      !focused ||
      !(dom instanceof HTMLElement)
    ) {
      blockTb.visible = false;

      return;
    }

    const rect = dom.getBoundingClientRect();
    const floor = shell ? 70 : 72;

    const isLaidOut = rect.height > 0;

    if (
      isLaidOut &&
      (rect.bottom < floor + 8 || rect.top > window.innerHeight)
    ) {
      blockTb.visible = false;

      return;
    }

    blockTb.visible = true;
    blockTb.top = Math.max(rect.top - 52, floor + 4);
    blockTb.left = Math.max(8, Math.min(rect.left, window.innerWidth - 360));
  }

  function openToolbarMenu(
    kind: 'type' | 'align' | 'context',
    event: MouseEvent,
  ): void {
    if (ctx.open && ctx.kind === kind) {
      closeContext();

      return;
    }

    const rect = (event.currentTarget as HTMLElement).getBoundingClientRect();

    closeMenu();
    Object.assign(ctx, { open: true, kind, x: rect.left, y: rect.bottom + 4 });
  }

  function openTopInserter(event: MouseEvent): void {
    if (menu.open && menu.source === 'top') {
      closeMenu();

      return;
    }

    const rect = (event.currentTarget as HTMLElement).getBoundingClientRect();

    Object.assign(menu, {
      open: true,
      source: 'top',
      query: '',
      index: 0,
      top: rect.bottom + 6,
      left: rect.left,
    });
  }

  function applyBlock(item: BlockItem): void {
    if (!editor) return;

    const chain = editor.chain().focus();

    (item.run ? item.run(chain) : chain).run();

    if (item.action) item.action();

    if (item.picker) openPicker('insert');
  }

  function isAligned(align: 'left' | 'center' | 'right'): boolean {
    void tick;

    if (blockInfo?.kind === 'image' || blockInfo?.kind === 'buttonBlock') {
      return blockInfo.attrs.align === align;
    }

    return editor?.isActive({ textAlign: align }) ?? false;
  }

  function setAlign(align: 'left' | 'center' | 'right'): void {
    if (blockInfo?.kind === 'image' || blockInfo?.kind === 'buttonBlock') {
      patchBlock({
        align,
        ...(blockInfo.kind === 'image' &&
        align !== 'center' &&
        !blockInfo.attrs.width
          ? { width: 50 }
          : {}),
      });

      return;
    }

    editor?.chain().focus().setTextAlign(align).run();
  }

  function addParagraph(where: 'before' | 'after'): void {
    const range = topBlock();

    if (!editor || !range) return;

    const at = where === 'before' ? range.pos : range.pos + range.size;

    editor
      .chain()
      .insertContentAt(at, { type: 'paragraph' })
      .focus(at + 1)
      .run();
  }

  /** Tracks the caret line to place the "+" inserter and the "/" command menu. */
  function syncCodeBadge(): void {
    const target = blockInfo;

    if (!editor || !host || mode !== 'visual' || target?.kind !== 'codeBlock') {
      codeBadge.visible = false;

      return;
    }

    const dom = editor.view.nodeDOM(target.pos);

    if (!(dom instanceof HTMLElement)) {
      codeBadge.visible = false;

      return;
    }

    const bounds = host.getBoundingClientRect();
    const rect = dom.getBoundingClientRect();

    codeBadge.visible = true;
    codeBadge.top = rect.top - bounds.top + 42;
    codeBadge.right = bounds.right - rect.right + 10;
  }

  function setCodeLanguage(language: string): void {
    patchBlock({ language: language || null });
    editor?.commands.focus();
  }

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
    inserter.left =
      editor.view.dom.getBoundingClientRect().right - bounds.left - 24;

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

    if (menu.source === 'top') {
      const range = topBlock();
      const node = range ? editor.state.doc.nodeAt(range.pos) : null;
      const isEmptyParagraph =
        node?.type.name === 'paragraph' && node.content.size === 0;

      if (!isEmptyParagraph) {
        const at = range
          ? range.pos + range.size
          : editor.state.doc.content.size;

        chain.insertContentAt(at, { type: 'paragraph' }).focus(at + 1);
      }
    }

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

      if (node.type.name === 'callout' || node.type.name === 'codeBlock') {
        return {
          kind: node.type.name as BlockKind,
          pos: selection.$from.before(depth),
          attrs: node.attrs,
        };
      }
    }

    return null;
  }

  /** Renders the highlighted preview of the code block under the caret. */
  function codePreview(
    node: HTMLElement,
    params: { source: string; language: string | null },
  ) {
    const render = ({ source, language }: typeof params): void => {
      const result = highlightSource(source, language);

      node.className = result ? 'hljs' : '';
      node.replaceChildren(result ? result.fragment : source);
    };

    render(params);

    return { update: render };
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
    Object.assign(ctx, {
      open: true,
      kind: 'context',
      x: event.clientX,
      y: event.clientY,
    });
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

    if (ctx.kind === 'type') {
      return [
        blockItems.map<ContextItem>((item) => ({
          label: item.label,
          icon: item.icon,
          on: currentBlock?.label === item.label,
          action: () => applyBlock(item),
        })),
      ];
    }

    if (ctx.kind === 'align') {
      return [
        (['left', 'center', 'right'] as const).map<ContextItem>((align) => ({
          label: alignLabels[align],
          icon: alignIcons[align],
          on: isAligned(align),
          action: () => setAlign(align),
        })),
      ];
    }

    const hasSelection =
      !instance.state.selection.empty ||
      instance.state.selection instanceof NodeSelection;
    const groups: ContextItem[][] = [
      [
        {
          label: 'Salin',
          shortcut: 'Ctrl+C',
          disabled: !hasSelection,
          action: () => clipboardCommand('copy'),
        },
        {
          label: 'Potong',
          shortcut: 'Ctrl+X',
          disabled: !hasSelection,
          action: () => clipboardCommand('cut'),
        },
        { label: 'Tempel', shortcut: 'Ctrl+V', action: pasteFromClipboard },
      ],
    ];

    if (!minimal) {
      groups[0].push(
        { label: 'Gandakan', action: duplicateBlock },
        { label: 'Tambahkan sebelumnya', action: () => addParagraph('before') },
        { label: 'Tambahkan setelahnya', action: () => addParagraph('after') },
      );
    }

    const format: ContextItem[] = [
      {
        label: 'Tebal',
        shortcut: 'Ctrl+B',
        on: active('bold'),
        action: () => instance.chain().focus().toggleBold().run(),
      },
      {
        label: 'Miring',
        shortcut: 'Ctrl+I',
        on: active('italic'),
        action: () => instance.chain().focus().toggleItalic().run(),
      },
      {
        label: 'Garis bawah',
        shortcut: 'Ctrl+U',
        on: active('underline'),
        action: () => instance.chain().focus().toggleUnderline().run(),
      },
      {
        label: 'Coret',
        on: active('strike'),
        action: () => instance.chain().focus().toggleStrike().run(),
      },
    ];

    groups.push(format);

    if (minimal) return groups;

    format.push(
      {
        label: 'Kode inline',
        on: active('code'),
        action: () => instance.chain().focus().toggleCode().run(),
      },
      {
        label: active('link') ? 'Ubah tautan' : 'Tautan',
        on: active('link'),
        action: setLink,
      },
    );

    groups.push([
      {
        label: 'Ubah menjadi...',
        action: () =>
          setTimeout(() => Object.assign(ctx, { open: true, kind: 'type' })),
      },
      {
        label: 'Pindah ke atas',
        disabled: !canMove(-1),
        action: () => moveBlock(-1),
      },
      {
        label: 'Pindah ke bawah',
        disabled: !canMove(1),
        action: () => moveBlock(1),
      },
    ]);

    if (blockInfo?.kind === 'image') {
      groups.push([
        ...(['left', 'center', 'right'] as const).map<ContextItem>((align) => ({
          label: alignLabels[align],
          on: blockInfo?.attrs.align === align,
          action: () => patchBlock({ align }),
        })),
        {
          label: 'Ganti gambar...',
          action: () => openPicker('replace'),
        },
      ]);
    }

    if (blockInfo?.kind === 'codeBlock') {
      const current = blockInfo.attrs.language ?? '';

      groups.push(
        [
          { label: 'Bahasa: Otomatis', value: '' },
          ...languageNames().map((name) => ({
            label: `Bahasa: ${name}`,
            value: name,
          })),
          { label: 'Bahasa: Teks biasa', value: 'plaintext' },
        ].map<ContextItem>(({ label, value }) => ({
          label,
          on: current === value,
          action: () => patchBlock({ language: value || null }),
        })),
        CODE_THEMES.map<ContextItem>((theme) => ({
          label: `Tema: ${theme.label}`,
          on: (blockInfo?.attrs.theme ?? '') === theme.value,
          action: () => patchBlock({ theme: theme.value || null }),
        })),
      );
    }

    groups.push([
      { label: 'Sunting sebagai HTML', action: () => setMode('html') },
    ]);

    groups.push([
      {
        label: 'Hapus',
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
    const x = Math.max(
      8,
      Math.min(ctx.x, window.innerWidth - bounds.width - 8),
    );
    const y = Math.max(
      8,
      Math.min(ctx.y, window.innerHeight - bounds.height - 8),
    );

    if (x !== ctx.x || y !== ctx.y) Object.assign(ctx, { x, y });
  });

  function handleMenuKey(event: KeyboardEvent, fromInput = false): boolean {
    if (!menu.open || (menu.source === 'top' && !fromInput)) return false;

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
        ...(minimal
          ? []
          : [FigureImage, Callout, ButtonBlock, CodeBlockHighlight]),
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
        syncCodeBadge();
        syncBlockUi();
        syncToolbar();
      },
      onFocus: () => {
        focused = true;
        if (menu.source === 'top') closeMenu();
        syncToolbar();
      },
      onBlur: () => {
        focused = false;
        if (menu.source === 'slash') closeMenu();
        syncToolbar();
      },
    });

    editor = instance;
    words = instance.storage.characterCount.words();

    if (window.innerWidth < 992) sidebarOpen = false;

    const resync = (): void => syncToolbar();

    window.addEventListener('scroll', resync, true);
    window.addEventListener('resize', resync);

    return () => {
      window.removeEventListener('scroll', resync, true);
      window.removeEventListener('resize', resync);
      instance.destroy();
    };
  });
</script>

<svelte:window
  onpointerdown={(event) => {
    const target = event.target as HTMLElement;

    if (
      ctx.open &&
      !ctxEl?.contains(target) &&
      !target.closest('[data-rte-trigger]')
    ) {
      closeContext();
    }

    if (
      menu.open &&
      menu.source === 'top' &&
      !menuEl?.contains(target) &&
      !target.closest('[data-rte-trigger]')
    ) {
      closeMenu();
    }
  }}
  onkeydown={(event) => {
    if (ctx.open && event.key === 'Escape') closeContext();
    if (menu.open && menu.source === 'top' && event.key === 'Escape')
      closeMenu();
  }}
  onresize={closeContext}
  onscroll={closeContext}
/>

{#snippet alignButtons(onpick: (align: 'left' | 'center' | 'right') => void)}
  <div class="rte-ins-row">
    {#each alignKeys as align (align)}
      {@const AlignIcon = alignIcons[align]}
      <button
        type="button"
        class="rte-btn"
        class:on={isAligned(align)}
        title={alignLabels[align]}
        onclick={() => onpick(align)}><AlignIcon size={16} /></button
      >
    {/each}
  </div>
{/snippet}

{#snippet inspector()}
  {#if currentBlock}
    {@const BlockIcon = blockIcons[currentBlock.kind] ?? Pilcrow}
    <div class="rte-ins">
      <div class="rte-ins-head">
        <BlockIcon size={20} />
        <div>
          <strong>{currentBlock.label}</strong>
          <p class="rte-ins-desc">{blockDescriptions[currentBlock.kind]}</p>
        </div>
      </div>

      {#if blockInfo?.kind === 'image'}
        <div class="rte-ins-group">
          <span class="rte-ins-label">Perataan</span>
          {@render alignButtons(setAlign)}
        </div>
        <div class="rte-ins-group">
          <span class="rte-ins-label">Lebar</span>
          <Select
            inline
            searchable={false}
            size="sm"
            class="rte-select"
            ariaLabel="Lebar gambar"
            placeholder="Lebar otomatis"
            items={widthItems}
            value={blockInfo.attrs.width ? String(blockInfo.attrs.width) : null}
            onchange={(next) => patchBlock({ width: Number(next) || null })}
          />
        </div>
        <div class="rte-ins-group">
          <span class="rte-ins-label">Teks alternatif</span>
          <input
            class="form-control form-control-sm"
            placeholder="Teks alternatif"
            aria-label="Teks alternatif"
            value={blockInfo.attrs.alt ?? ''}
            oninput={(event) => patchBlock({ alt: event.currentTarget.value })}
          />
        </div>
        <div class="rte-ins-group">
          <span class="rte-ins-label">Keterangan</span>
          <input
            class="form-control form-control-sm"
            placeholder="Keterangan gambar"
            aria-label="Keterangan gambar"
            value={blockInfo.attrs.caption ?? ''}
            oninput={(event) =>
              patchBlock({ caption: event.currentTarget.value })}
          />
        </div>
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary align-self-start"
          onclick={() => openPicker('replace')}>Ganti gambar</button
        >
      {:else if blockInfo?.kind === 'buttonBlock'}
        <div class="rte-ins-group">
          <span class="rte-ins-label">Teks tombol</span>
          <input
            class="form-control form-control-sm"
            placeholder="Teks tombol"
            aria-label="Teks tombol"
            value={blockInfo.attrs.text ?? ''}
            oninput={(event) => patchBlock({ text: event.currentTarget.value })}
          />
        </div>
        <div class="rte-ins-group">
          <span class="rte-ins-label">Tautan</span>
          <input
            class="form-control form-control-sm"
            placeholder="https://..."
            aria-label="Alamat tautan tombol"
            value={blockInfo.attrs.href ?? ''}
            onchange={(event) => {
              const href = safeHref(event.currentTarget.value);
              event.currentTarget.value = href;
              patchBlock({ href });
            }}
          />
        </div>
        <div class="rte-ins-group">
          <span class="rte-ins-label">Gaya</span>
          <Select
            inline
            required
            searchable={false}
            size="sm"
            class="rte-select"
            ariaLabel="Gaya tombol"
            items={buttonVariantItems}
            value={blockInfo.attrs.variant}
            onchange={(next) => patchBlock({ variant: next })}
          />
        </div>
        <div class="rte-ins-group">
          <span class="rte-ins-label">Perataan</span>
          {@render alignButtons(setAlign)}
        </div>
      {:else if blockInfo?.kind === 'codeBlock'}
        <div class="rte-ins-group">
          <span class="rte-ins-label">Bahasa</span>
          <Select
            inline
            size="sm"
            class="rte-select"
            ariaLabel="Bahasa pemrograman"
            placeholder="Otomatis"
            items={languageItems}
            value={blockInfo.attrs.language || null}
            onchange={(next) => patchBlock({ language: next || null })}
          />
        </div>
        <div class="rte-ins-group">
          <span class="rte-ins-label">Judul</span>
          <input
            type="text"
            class="form-control form-control-sm"
            aria-label="Judul blok kode"
            placeholder="Judul (mis. User.php)"
            maxlength="60"
            value={blockInfo.attrs.title ?? ''}
            onchange={(event) =>
              patchBlock({
                title: normalizeCodeTitle(event.currentTarget.value),
              })}
          />
        </div>
        <div class="rte-ins-group">
          <span class="rte-ins-label">Tema</span>
          <Select
            inline
            size="sm"
            class="rte-select"
            ariaLabel="Tema kode"
            placeholder={defaultThemeLabel}
            items={themeItems}
            value={blockInfo.attrs.theme || null}
            onchange={(next) => patchBlock({ theme: next || null })}
          />
        </div>
        <div class="rte-code-preview wysiwyg-content-wrapper">
          <span class="rte-code-preview-label">Pratinjau</span>
          <pre data-code-theme={blockInfo.attrs.theme ?? undefined}><code
              use:codePreview={{
                source:
                  editor?.state.doc.nodeAt(blockInfo.pos)?.textContent ?? '',
                language: blockInfo.attrs.language ?? null,
              }}></code></pre>
        </div>
      {:else if blockInfo?.kind === 'callout'}
        <div class="rte-ins-group">
          <span class="rte-ins-label">Jenis</span>
          <div class="rte-ins-row">
            {#each CALLOUT_TYPES as type (type)}
              <button
                type="button"
                class="rte-chip is-{type}"
                class:on={blockInfo.attrs.type === type}
                onclick={() => patchBlock({ type })}
                >{calloutLabels[type]}</button
              >
            {/each}
          </div>
        </div>
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary align-self-start"
          title="Keluarkan isi dari callout"
          onclick={() => editor?.chain().focus().lift('callout').run()}
          >Lepas kotak</button
        >
      {:else if currentBlock.kind === 'paragraph' || currentBlock.kind === 'heading'}
        <div class="rte-ins-group">
          <span class="rte-ins-label">Perataan teks</span>
          {@render alignButtons(setAlign)}
        </div>
        {#if currentBlock.kind === 'heading'}
          <div class="rte-ins-group">
            <span class="rte-ins-label">Tingkat judul</span>
            <div class="rte-ins-row">
              {#each headingLevels as level (level)}
                <button
                  type="button"
                  class="rte-chip"
                  class:on={active('heading', { level })}
                  onclick={() =>
                    editor?.chain().focus().setHeading({ level }).run()}
                  >H{level}</button
                >
              {/each}
            </div>
          </div>
        {/if}
      {/if}

      <button
        type="button"
        class="btn btn-sm btn-outline-danger align-self-start"
        onclick={() => deleteBlock()}
      >
        <Trash size={14} /> Hapus blok
      </button>
    </div>
  {:else}
    <p class="rte-ins-empty">Pilih sebuah blok untuk mengubah pengaturannya.</p>
  {/if}
{/snippet}

<div
  class="rte"
  class:is-invalid={invalid}
  class:rte-minimal={minimal}
  class:rte-shell={shell}
>
  <input type="hidden" {name} value={html} />

  <div class="rte-top">
    {#if shell && adminSidebar && (!adminSidebar.isDesktop || adminSidebar.collapsed)}
      <button
        type="button"
        class="rte-btn rte-btn-lg"
        title="Buka/tutup menu admin"
        aria-label="Buka/tutup menu admin"
        onclick={() => adminSidebar.toggle()}
        ><PanelLeftOpen size={18} /></button
      >
    {/if}

    {#if shell}
      <InertiaLink
        href={backHref}
        class="rte-btn rte-btn-lg"
        title="Kembali"
        aria-label="Kembali"><ChevronLeft size={20} /></InertiaLink
      >
    {/if}

    {#if mode === 'visual' && editor}
      <div class="rte-tools" role="toolbar" aria-label="Alat dokumen">
        {#if !minimal}
          <button
            type="button"
            class="rte-btn rte-btn-lg rte-inserter-toggle"
            class:on={menu.open && menu.source === 'top'}
            title="Sisipkan blok"
            aria-label="Sisipkan blok"
            aria-expanded={menu.open && menu.source === 'top'}
            data-rte-trigger
            onmousedown={(event) => event.preventDefault()}
            onclick={openTopInserter}><Plus size={18} /></button
          >
        {/if}
        <button
          type="button"
          class="rte-btn rte-btn-lg"
          title="Urungkan"
          disabled={!editor.can().undo()}
          onclick={() => editor?.chain().focus().undo().run()}
          ><Undo2 size={18} /></button
        >
        <button
          type="button"
          class="rte-btn rte-btn-lg"
          title="Ulangi"
          disabled={!editor.can().redo()}
          onclick={() => editor?.chain().focus().redo().run()}
          ><Redo2 size={18} /></button
        >
      </div>
    {/if}

    {#if shell}
      <div class="rte-doc-title" title={documentTitle}>
        {documentTitle || 'Tanpa judul'}<span> · {rootLabel}</span>
      </div>
    {/if}

    <div class="rte-top-end">
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

      {#if shell}
        {@render actions?.({ mode, setMode })}
        <button
          type="button"
          class="rte-btn rte-btn-lg rte-sidebar-toggle"
          class:on={sidebarOpen}
          title="Pengaturan"
          aria-label="Pengaturan"
          aria-pressed={sidebarOpen}
          onclick={() => (sidebarOpen = !sidebarOpen)}
          ><PanelRight size={18} /></button
        >
      {/if}
    </div>

    {#if !shell && blockInfo && mode === 'visual'}
      <div class="rte-blockbar" role="group" aria-label="Pengaturan blok">
        {@render inspector()}
      </div>
    {/if}
  </div>

  <div class="rte-main">
    <div class="rte-scroll">
      <div class="rte-doc" bind:this={host}>
        {#if shell}{@render header?.()}{/if}

        <div
          class="rte-body"
          hidden={mode !== 'visual'}
          bind:this={element}
        ></div>

        {#if codeBadge.visible}
          <div
            class="rte-code-badge"
            style="top: {codeBadge.top}px; right: {codeBadge.right}px"
          >
            <Select
              inline
              size="sm"
              ariaLabel="Ubah bahasa blok kode"
              placeholder="Otomatis"
              items={languageItems}
              value={blockInfo?.attrs.language || null}
              onchange={(next) => setCodeLanguage(next ? String(next) : '')}
            />
            <Select
              inline
              size="sm"
              ariaLabel="Ubah tema blok kode"
              placeholder={defaultThemeLabel}
              items={themeItems}
              value={blockInfo?.attrs.theme || null}
              onchange={(next) => {
                patchBlock({ theme: next || null });
                editor?.commands.focus();
              }}
            />
          </div>
        {/if}

        {#if !minimal && mode === 'visual'}
          {#if inserter.visible && !menu.open}
            <button
              type="button"
              class="rte-inserter"
              title="Sisipkan blok"
              aria-label="Sisipkan blok"
              style="top: {inserter.top}px; left: {inserter.left}px"
              onmousedown={(event) => event.preventDefault()}
              onclick={openButtonMenu}><Plus size={16} /></button
            >
          {/if}

          {#if menu.open}
            <div
              class="rte-menu"
              class:is-fixed={menu.source === 'top'}
              role="listbox"
              tabindex="-1"
              aria-label="Sisipkan blok"
              style="top: {menu.top}px; left: {menu.left}px"
              bind:this={menuEl}
              onmousedown={(event) => {
                if (!(event.target instanceof HTMLInputElement))
                  event.preventDefault();
              }}
            >
              {#if menu.source === 'top'}
                <label class="rte-menu-search">
                  <Search size={14} />
                  <!-- svelte-ignore a11y_autofocus -->
                  <input
                    type="search"
                    placeholder="Cari blok"
                    aria-label="Cari blok"
                    autofocus
                    bind:value={menu.query}
                    oninput={() => (menu.index = 0)}
                    onkeydown={(event) => handleMenuKey(event, true)}
                  />
                </label>
              {/if}
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
                  <span
                    ><strong>{item.label}</strong><small>{item.hint}</small
                    ></span
                  >
                </button>
              {:else}
                <p class="rte-menu-empty">Blok tidak ditemukan</p>
              {/each}
            </div>
          {/if}
        {/if}

        {#if mode === 'html'}
          <HtmlCodeEditor bind:value={html} ariaLabel="Kode HTML" />
        {/if}

        {#if shell}{@render after?.()}{/if}
      </div>
    </div>

    {#if shell}
      <aside
        class="rte-sidebar"
        class:is-open={sidebarOpen}
        aria-label="Pengaturan"
        inert={!sidebarOpen}
      >
        <div class="rte-side-inner">
          <div class="rte-side-tabs" role="tablist">
            <button
              type="button"
              role="tab"
              aria-selected={sidebarTab === 'document'}
              class="rte-side-tab"
              class:on={sidebarTab === 'document'}
              onclick={() => (sidebarTab = 'document')}>{rootLabel}</button
            >
            <button
              type="button"
              role="tab"
              aria-selected={sidebarTab === 'block'}
              class="rte-side-tab"
              class:on={sidebarTab === 'block'}
              onclick={() => (sidebarTab = 'block')}>Blok</button
            >
            <button
              type="button"
              class="rte-btn rte-side-close"
              title="Tutup pengaturan"
              aria-label="Tutup pengaturan"
              onclick={() => (sidebarOpen = false)}><X size={18} /></button
            >
          </div>
          <div class="rte-side-body">
            {#if sidebarTab === 'document'}
              {@render settings?.()}
            {:else}
              {@render inspector()}
            {/if}
          </div>
        </div>
      </aside>
    {/if}
  </div>

  {#if blockTb.visible && mode === 'visual' && editor}
    <div
      class="rte-blocktb"
      role="toolbar"
      aria-label="Pemformatan blok"
      tabindex="-1"
      style="top: {blockTb.top}px; left: {blockTb.left}px"
      onmousedown={(event) => event.preventDefault()}
    >
      <div class="rte-tb-group">
        {#if !minimal}
          {@const TypeIcon =
            blockIcons[currentBlock?.kind ?? 'paragraph'] ?? Pilcrow}
          <button
            type="button"
            class="rte-btn rte-btn-lg"
            title="Ubah jenis blok"
            aria-label="Ubah jenis blok"
            data-rte-trigger
            onclick={(event) => openToolbarMenu('type', event)}
            ><TypeIcon size={18} /></button
          >
          <div class="rte-tb-move">
            <button
              type="button"
              class="rte-btn"
              title="Pindah ke atas"
              disabled={!canMove(-1)}
              onclick={() => moveBlock(-1)}><ArrowUp size={14} /></button
            >
            <button
              type="button"
              class="rte-btn"
              title="Pindah ke bawah"
              disabled={!canMove(1)}
              onclick={() => moveBlock(1)}><ArrowDown size={14} /></button
            >
          </div>
        {/if}
      </div>
      <div class="rte-tb-group">
        {#if !minimal}
          {@const CurrentAlign =
            alignIcons[
              (['center', 'right'] as const).find((align) =>
                isAligned(align),
              ) ?? 'left'
            ]}
          <button
            type="button"
            class="rte-btn rte-btn-lg"
            title="Perataan"
            aria-label="Perataan"
            data-rte-trigger
            onclick={(event) => openToolbarMenu('align', event)}
            ><CurrentAlign size={18} /></button
          >
        {/if}
      </div>
      <div class="rte-tb-group">
        <button
          type="button"
          class="rte-btn rte-btn-lg"
          class:on={active('bold')}
          title="Tebal"
          onclick={() => editor?.chain().focus().toggleBold().run()}
          ><Bold size={18} /></button
        >
        <button
          type="button"
          class="rte-btn rte-btn-lg"
          class:on={active('italic')}
          title="Miring"
          onclick={() => editor?.chain().focus().toggleItalic().run()}
          ><Italic size={18} /></button
        >
        {#if !minimal}
          <button
            type="button"
            class="rte-btn rte-btn-lg"
            class:on={active('link')}
            title="Tautan"
            onclick={setLink}><Link size={18} /></button
          >
        {:else}
          <button
            type="button"
            class="rte-btn rte-btn-lg"
            class:on={active('bulletList')}
            title="Daftar poin"
            onclick={() => editor?.chain().focus().toggleBulletList().run()}
            ><List size={18} /></button
          >
          <button
            type="button"
            class="rte-btn rte-btn-lg"
            class:on={active('orderedList')}
            title="Daftar angka"
            onclick={() => editor?.chain().focus().toggleOrderedList().run()}
            ><ListOrdered size={18} /></button
          >
          <button
            type="button"
            class="rte-btn rte-btn-lg"
            class:on={active('blockquote')}
            title="Kutipan"
            onclick={() => editor?.chain().focus().toggleBlockquote().run()}
            ><Quote size={18} /></button
          >
        {/if}
      </div>
      <div class="rte-tb-group">
        <button
          type="button"
          class="rte-btn rte-btn-lg"
          title="Opsi lainnya"
          aria-label="Opsi lainnya"
          data-rte-trigger
          onclick={(event) => openToolbarMenu('context', event)}
          ><EllipsisVertical size={18} /></button
        >
      </div>
    </div>
  {/if}

  {#if ctx.open && contextGroups.length > 0}
    <div
      class="rte-context"
      role="menu"
      tabindex="-1"
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
            {#if item.icon}<item.icon size={16} />{/if}
            <span>{item.label}</span>
            {#if item.shortcut}<kbd>{item.shortcut}</kbd>{/if}
          </button>
        {/each}
      {/each}
    </div>
  {/if}

  <div class="rte-footer">
    <span class="rte-crumbs">
      <span>{rootLabel}</span>
      {#if currentBlock}<span aria-hidden="true">›</span>
        <span class="rte-crumb-current">{currentBlock.label}</span>{/if}
    </span>
    <span class="rte-footer-end">
      {#if uploading}<span>Mengunggah gambar...</span>{/if}
      <span>{words} kata</span>
    </span>
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
    --rte-line: rgba(var(--neo-body-color-rgb), 0.14);
    --rte-bar-h: 3.5rem;
    --rte-side-w: 17.5rem;
    --rte-foot-h: 2.75rem;

    border: 1px solid var(--rte-line);
    border-radius: 0.5rem;
    background: var(--neo-body-bg);
    position: relative;
  }
  .rte.is-invalid {
    border-color: var(--neo-form-invalid-border-color, #dc3545);
  }

  /* Top bar (document tools) */
  .rte-top {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.25rem;
    min-height: var(--rte-bar-h);
    padding: 0 0.5rem;
    border-bottom: 1px solid var(--rte-line);
    background: var(--neo-body-bg);
    border-radius: 0.5rem 0.5rem 0 0;
    position: sticky;
    top: 0;
    z-index: 5;
  }
  :global(.admin-shell) .rte:not(.rte-shell) .rte-top {
    top: 69px;
  }
  .rte-tools {
    display: flex;
    align-items: center;
    gap: 0.125rem;
  }
  .rte-top-end {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-left: auto;
  }
  .rte-doc-title {
    flex: 0 1 28rem;
    min-width: 0;
    margin: 0 auto;
    padding: 0.4rem 1rem;
    overflow: hidden;
    border-radius: 0.375rem;
    background: var(--neo-tertiary-bg);
    font-size: 0.8125rem;
    text-align: center;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .rte-doc-title span {
    color: var(--neo-secondary-color);
  }

  .rte-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border: 0;
    border-radius: 0.5rem;
    background: transparent;
    color: var(--neo-body-color);
    text-decoration: none;
  }
  .rte-btn-lg {
    width: 2.5rem;
    height: 2.5rem;
  }
  .rte-btn:hover:not(:disabled) {
    background: var(--neo-secondary-bg);
  }
  .rte-btn.on {
    background: var(--neo-emphasis-color);
    color: var(--neo-body-bg);
  }
  .rte-btn:disabled {
    opacity: 0.35;
  }
  .rte-inserter-toggle {
    width: 2rem;
    height: 2rem;
    margin-right: 0.25rem;
    border-radius: 0.5rem;
    background: var(--neo-primary);
    color: #fff;
  }
  .rte-inserter-toggle:hover:not(:disabled),
  .rte-inserter-toggle.on {
    background: var(--neo-primary);
    color: #fff;
    filter: brightness(0.9);
  }
  .rte-tabs {
    display: flex;
    overflow: hidden;
    border: 1px solid var(--rte-line);
    border-radius: 0.375rem;
  }
  .rte-tab {
    border: 0;
    background: transparent;
    padding: 0.3rem 0.75rem;
    font-size: 0.8125rem;
    color: var(--neo-secondary-color);
  }
  .rte-tab + .rte-tab {
    border-left: 1px solid var(--rte-line);
  }
  .rte-tab.on {
    background: var(--neo-emphasis-color);
    color: var(--neo-body-bg);
  }

  /* Floating block toolbar */
  .rte-blocktb {
    position: fixed;
    z-index: 1040;
    display: flex;
    align-items: stretch;
    border: 1px solid var(--rte-line);
    border-radius: 0.75rem;
    background: var(--neo-body-bg);
    box-shadow: 0 0.25rem 1rem rgb(0 0 0 / 0.1);
  }
  .rte-tb-group {
    display: flex;
    align-items: center;
    padding: 0 0.125rem;
  }
  .rte-tb-group:empty {
    display: none;
  }
  .rte-tb-group + .rte-tb-group {
    border-left: 1px solid var(--rte-line);
  }
  .rte-tb-move {
    display: flex;
    flex-direction: column;
  }
  .rte-tb-move .rte-btn {
    width: 1.5rem;
    height: 1.25rem;
  }

  /* Inline appender and block menu */
  .rte-inserter {
    position: absolute;
    z-index: 3;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.5rem;
    height: 1.5rem;
    margin-top: 0.35rem;
    border: 0;
    border-radius: 0.5rem;
    background: var(--neo-emphasis-color);
    color: var(--neo-body-bg);
  }
  .rte-inserter:hover {
    background: var(--neo-primary);
    color: #fff;
  }
  .rte-menu {
    position: absolute;
    z-index: 6;
    width: 17rem;
    max-height: 22rem;
    overflow-y: auto;
    padding: 0.25rem;
    border: 1px solid var(--rte-line);
    border-radius: 0.75rem;
    background: var(--neo-body-bg);
    box-shadow: 0 0.5rem 1.5rem rgb(0 0 0 / 0.12);
  }
  .rte-menu.is-fixed {
    position: fixed;
    z-index: 1060;
  }
  .rte-menu-search {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0.25rem 0.25rem 0.5rem;
    padding: 0 0.5rem;
    border: 1px solid var(--rte-line);
    border-radius: 0.5rem;
    color: var(--neo-secondary-color);
  }
  .rte-menu-search input {
    flex: 1;
    min-width: 0;
    padding: 0.4rem 0;
    border: 0;
    outline: none;
    background: transparent;
    color: var(--neo-body-color);
    font-size: 0.875rem;
  }
  .rte-menu-item {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    width: 100%;
    padding: 0.4rem 0.5rem;
    border: 0;
    border-radius: 0.5rem;
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

  /* Context / dropdown menu */
  .rte-context {
    position: fixed;
    z-index: 1080;
    min-width: 14rem;
    max-width: calc(100vw - 1rem);
    max-height: calc(100vh - 1rem);
    overflow-y: auto;
    padding: 0.375rem;
    border: 1px solid var(--rte-line);
    border-radius: 0.75rem;
    background: var(--neo-body-bg);
    box-shadow: 0 0.5rem 1.5rem rgb(0 0 0 / 0.2);
  }
  .rte-context-sep {
    margin: 0.375rem 0.25rem;
    border: 0;
    border-top: 1px solid var(--rte-line);
    opacity: 1;
  }
  .rte-context-item {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    width: 100%;
    padding: 0.375rem 0.75rem;
    border: 0;
    border-radius: 0.5rem;
    background: transparent;
    color: var(--neo-body-color);
    font-size: 0.8125rem;
    text-align: left;
  }
  .rte-context-item span {
    flex: 1;
  }
  .rte-context-item kbd {
    padding: 0;
    background: transparent;
    color: var(--neo-secondary-color);
    font-size: 0.75rem;
  }
  .rte-context-item:hover:not(:disabled),
  .rte-context-item:focus-visible {
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

  /* Canvas */
  .rte-main {
    display: flex;
    min-height: 0;
  }
  .rte-scroll {
    flex: 1;
    min-width: 0;
  }
  .rte-doc {
    position: relative;
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
    padding: 1.25rem 2.5rem 1.25rem 1.5rem;
    outline: none;
  }
  .rte-minimal .rte-body :global(.rte-content) {
    padding-right: 1.5rem;
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
  .rte-body :global(.rte-content > *:focus-within),
  .rte-body :global(.rte-content > .has-focus) {
    outline: none;
  }

  /* Shell: Gutenberg-style layout filling the admin content area */
  .rte-shell {
    --rte-nav-h: 0px;

    border: 0;
    border-radius: 0;
  }
  .rte-shell .rte-top {
    border-radius: 0;
  }
  .rte-shell .rte-footer {
    border-radius: 0;
  }
  .rte-shell .rte-top {
    top: var(--rte-nav-h);
    flex-wrap: nowrap;
    padding: 0 0.75rem;
  }
  .rte-shell .rte-main {
    align-items: flex-start;
  }
  .rte-shell .rte-sidebar {
    position: sticky;
    top: calc(var(--rte-nav-h) + var(--rte-bar-h));
    height: calc(100svh - 1rem - var(--rte-bar-h) - var(--rte-foot-h));
  }
  .rte-shell .rte-footer {
    height: var(--rte-foot-h);
    position: sticky;
    bottom: 0;
    z-index: 4;
  }
  .rte-shell .rte-doc {
    width: min(100%, 44rem);
    margin: 0 auto;
    padding: 4rem 1rem 8rem;
  }
  .rte-shell .rte-body,
  .rte-shell .rte-body :global(.rte-content) {
    min-height: 16rem;
  }
  .rte-shell .rte-body :global(.rte-content) {
    padding: 0 2.25rem 0 0;
    font-size: 1.25rem;
    line-height: 1.7;
  }
  .rte-doc :global(.gb-title) {
    display: block;
    width: 100%;
    margin: 0 0 1.5rem;
    padding: 0;
    border: 0;
    outline: none;
    resize: none;
    overflow: hidden;
    background: transparent;
    color: var(--neo-body-color);
    font-size: 2.5rem;
    font-weight: 400;
    line-height: 1.25;
    field-sizing: content;
  }
  .rte-doc :global(.gb-title::placeholder) {
    color: var(--neo-secondary-color);
    opacity: 0.7;
  }

  /* Sidebar */
  .rte-sidebar {
    flex: none;
    width: 0;
    overflow: hidden;
    transition: width 0.25s ease;
  }
  .rte-sidebar.is-open {
    width: var(--rte-side-w);
  }
  .rte-side-inner {
    display: flex;
    flex-direction: column;
    height: 100%;
    width: var(--rte-side-w);
    border-left: 1px solid var(--rte-line);
    background: var(--neo-body-bg);
  }
  .rte-side-tabs {
    display: flex;
    align-items: stretch;
    flex: none;
    height: 3rem;
    padding: 0 0.25rem 0 0;
    border-bottom: 1px solid var(--rte-line);
  }
  .rte-side-tab {
    padding: 0 1rem;
    border: 0;
    border-bottom: 2px solid transparent;
    background: transparent;
    color: var(--neo-body-color);
    font-size: 0.8125rem;
  }
  .rte-side-tab.on {
    border-bottom-color: var(--neo-emphasis-color);
    font-weight: 600;
  }
  .rte-side-close {
    align-self: center;
    margin-left: auto;
  }
  .rte-side-body {
    flex: 1;
    overflow-y: auto;
  }
  .rte-sidebar :global(.gb-panel) {
    border-bottom: 1px solid var(--rte-line);
  }
  .rte-sidebar :global(.gb-panel > summary) {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.875rem 1rem;
    font-size: 0.8125rem;
    font-weight: 600;
    list-style: none;
    cursor: pointer;
  }
  .rte-sidebar :global(.gb-panel > summary::-webkit-details-marker) {
    display: none;
  }
  .rte-sidebar :global(.gb-panel > summary::after) {
    content: '';
    width: 0.4rem;
    height: 0.4rem;
    margin-right: 0.2rem;
    border-right: 1.5px solid currentcolor;
    border-bottom: 1.5px solid currentcolor;
    transform: rotate(45deg);
    transition: transform 0.15s;
  }
  .rte-sidebar :global(.gb-panel[open] > summary::after) {
    transform: rotate(-135deg);
  }
  .rte-sidebar :global(.gb-panel-body) {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding: 0 1rem 1rem;
    font-size: 0.8125rem;
  }
  .rte-sidebar :global(.gb-label) {
    display: block;
    margin-bottom: 0.25rem;
    color: var(--neo-secondary-color);
    font-size: 0.6875rem;
    font-weight: 500;
    text-transform: uppercase;
  }

  /* Inspector (block settings) */
  .rte-ins {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1rem;
  }
  .rte-ins-head {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
  }
  .rte-ins-head :global(svg) {
    flex: none;
    margin-top: 0.1rem;
  }
  .rte-ins-desc {
    margin: 0.25rem 0 0;
    color: var(--neo-secondary-color);
    font-size: 0.8125rem;
  }
  .rte-ins-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }
  .rte-ins-label {
    color: var(--neo-secondary-color);
    font-size: 0.6875rem;
    font-weight: 500;
    text-transform: uppercase;
  }
  .rte-ins-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
  }
  .rte-ins-empty {
    margin: 0;
    padding: 1rem;
    color: var(--neo-secondary-color);
    font-size: 0.8125rem;
  }
  .rte-chip {
    height: 2rem;
    padding: 0 0.75rem;
    border: 1px solid var(--rte-line);
    border-radius: 999px;
    background: transparent;
    color: var(--neo-body-color);
    font-size: 0.8125rem;
  }
  .rte-chip.on {
    background: var(--neo-emphasis-color);
    border-color: var(--neo-emphasis-color);
    color: var(--neo-body-bg);
  }
  .rte-code-preview {
    position: relative;
  }
  .rte-code-preview pre {
    max-height: 12rem;
    margin: 0;
    overflow: auto;
    white-space: pre;
  }
  .rte-code-preview-label {
    position: absolute;
    top: 0.25rem;
    right: 0.5rem;
    z-index: 1;
    font-size: 0.6875rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--neo-secondary-color);
  }
  .rte-code-badge {
    position: absolute;
    z-index: 4;
    display: flex;
    gap: 0.25rem;
  }
  .rte-code-badge :global(.neo-select) {
    min-width: 8.5rem;
    font-size: 0.75rem;
  }

  /* Inline inspector (non-shell editors) */
  .rte-blockbar {
    flex: 0 0 100%;
    order: 3;
    border-top: 1px solid var(--rte-line);
  }
  .rte-blockbar .rte-ins {
    flex-flow: row wrap;
    align-items: flex-end;
    gap: 0.5rem 1rem;
    padding: 0.5rem 0.25rem;
  }
  .rte-blockbar .rte-ins-desc {
    display: none;
  }
  .rte-blockbar .rte-ins-head {
    align-items: center;
  }
  .rte-blockbar .rte-code-preview {
    flex: 0 0 100%;
  }

  /* Breadcrumb footer */
  .rte-footer {
    display: flex;
    flex: none;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.5rem 1rem;
    border-top: 1px solid var(--rte-line);
    border-radius: 0 0 0.5rem 0.5rem;
    background: var(--neo-body-bg);
    font-size: 0.8125rem;
    color: var(--neo-secondary-color);
  }
  .rte-crumbs {
    display: flex;
    gap: 0.5rem;
  }
  .rte-crumbs > :first-child,
  .rte-crumb-current {
    color: var(--neo-body-color);
  }
  .rte-footer-end {
    display: flex;
    gap: 1rem;
  }

  @media (width <= 991.98px) {
    .rte-shell .rte-sidebar {
      position: fixed;
      inset: 0 0 0 auto;
      z-index: 1050;
      width: min(100%, 20rem);
      height: auto;
      transform: translateX(100%);
      transition: transform 0.25s ease;
      box-shadow: -0.5rem 0 1.5rem rgb(0 0 0 / 0.15);
    }
    .rte-shell .rte-sidebar.is-open {
      width: min(100%, 20rem);
      transform: none;
    }
    .rte-shell .rte-side-inner {
      width: 100%;
    }
    .rte-shell .rte-doc-title,
    .rte-shell .rte-tabs {
      display: none;
    }
    .rte-shell .rte-body :global(.rte-content) {
      font-size: 1.125rem;
    }
    .rte-doc :global(.gb-title) {
      font-size: 1.875rem;
    }
  }
</style>
