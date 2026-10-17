<script lang="ts">
  import { t } from '@/lib/i18n';
  import '../../../css/content.scss';
  import AlignCenter from '@lucide/svelte/icons/align-center';
  import AlignLeft from '@lucide/svelte/icons/align-left';
  import AlignRight from '@lucide/svelte/icons/align-right';
  import ArrowDown from '@lucide/svelte/icons/arrow-down';
  import ArrowUp from '@lucide/svelte/icons/arrow-up';
  import Check from '@lucide/svelte/icons/check';
  import Copy from '@lucide/svelte/icons/copy';
  import CopyPlus from '@lucide/svelte/icons/copy-plus';
  import Monitor from '@lucide/svelte/icons/monitor';
  import Smartphone from '@lucide/svelte/icons/smartphone';
  import Tablet from '@lucide/svelte/icons/tablet';
  import BetweenHorizontalEnd from '@lucide/svelte/icons/between-horizontal-end';
  import BetweenHorizontalStart from '@lucide/svelte/icons/between-horizontal-start';
  import BetweenVerticalEnd from '@lucide/svelte/icons/between-vertical-end';
  import BetweenVerticalStart from '@lucide/svelte/icons/between-vertical-start';
  import BookA from '@lucide/svelte/icons/book-a';
  import BookOpenText from '@lucide/svelte/icons/book-open-text';
  import BookText from '@lucide/svelte/icons/book-text';
  import Bold from '@lucide/svelte/icons/bold';
  import ClipboardList from '@lucide/svelte/icons/clipboard-list';
  import Info from '@lucide/svelte/icons/info';
  import MousePointerClick from '@lucide/svelte/icons/mouse-pointer-click';
  import Replace from '@lucide/svelte/icons/replace';
  import Trash from '@lucide/svelte/icons/trash';
  import Heading1 from '@lucide/svelte/icons/heading-1';
  import Heading2 from '@lucide/svelte/icons/heading-2';
  import Heading3 from '@lucide/svelte/icons/heading-3';
  import ImageIcon from '@lucide/svelte/icons/image';
  import ExternalLink from '@lucide/svelte/icons/external-link';
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
  import Sigma from '@lucide/svelte/icons/sigma';
  import SquareCode from '@lucide/svelte/icons/square-code';
  import Table2 from '@lucide/svelte/icons/table-2';
  import TableProperties from '@lucide/svelte/icons/table-properties';
  import Trash2 from '@lucide/svelte/icons/trash-2';
  import TableRowsSplit from '@lucide/svelte/icons/table-rows-split';
  import Undo2 from '@lucide/svelte/icons/undo-2';
  import { Editor, type ChainedCommands } from '@tiptap/core';
  import CharacterCount from '@tiptap/extension-character-count';
  import Placeholder from '@tiptap/extension-placeholder';
  import TextAlign from '@tiptap/extension-text-align';
  import { Fragment, Slice } from '@tiptap/pm/model';
  import { NodeSelection } from '@tiptap/pm/state';
  import StarterKit from '@tiptap/starter-kit';
  import { onMount, untrack, type Snippet } from 'svelte';
  import { useAdminSidebarState } from '@/layouts/admin/sidebar-state.svelte';
  import HtmlCodeEditor from '@/components/ui/html-code-editor.svelte';
  import MediaPickerModal from '@/components/media/media-picker-modal.svelte';
  import {
    BULLET_STYLES,
    BulletListStyle,
    ButtonBlock,
    CALLOUT_TYPES,
    Callout,
    CODE_THEMES,
    CodeBlockHighlight,
    LinkHighlight,
    Definition,
    FigureImage,
    LinkCard,
    MathBlock,
    MathInline,
    Reference,
    References,
    SCRIPTURE_ARABIC_SIZES,
    SCRIPTURE_KINDS,
    SCRIPTURE_LABELS,
    ScriptureQuote,
    cleanScriptureText,
    type ScriptureKind,
    SpecLabel,
    SpecRow,
    SpecSheet,
    SpecValue,
    TableBlocks,
    TableSetup,
    cleanBlockText,
    safeHref,
    sourceUrl,
  } from '@/lib/editor-blocks';
  import { normalizeCodeTitle } from '@/lib/code-card';
  import LinkPopover from '@/components/ui/link-popover.svelte';
  import { portal } from '@/lib/dom';
  import { overlayIn, overlayOut } from '@/lib/overlay-motion';
  import { highlightSource, languageNames } from '@/lib/highlight';
  import Select from '@/components/ui/select.svelte';
  import { uploadMedia } from '@/lib/media';
  import { linkPreview } from '@/wayfinder/routes/admin';
  import { toast } from '@/lib/toast';
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
    contentKey = '',
    onchange,
    placeholder = t('ui.mulai_menulis_cerita_anda'),
    invalid = false,
    minimal = false,
    shell = false,
    documentTitle = '',
    rootLabel = t('ui.dokumen'),
    viewHref = null,
    header,
    settings,
    actions,
    after,
  }: {
    name: string;
    value?: string | null;
    /** Changing this key replaces the document with the current `value` (e.g. when switching language). */
    contentKey?: string;
    /** Called with the document's HTML whenever it changes. */
    onchange?: (html: string) => void;
    placeholder?: string;
    invalid?: boolean;
    /** Public-safe mode: basic formatting only, no images, links, headings or HTML tab. */
    minimal?: boolean;
    /** Gutenberg-style full-screen editor: top bar, canvas and a settings sidebar. */
    shell?: boolean;
    /** Target of the back arrow in the shell top bar. */
    /** Title shown in the centre of the shell top bar. */
    documentTitle?: string;
    /** First crumb of the breadcrumb under the canvas. */
    rootLabel?: string;
    /** Public URL of the saved, published document; shows an open-in-new-tab button next to the title. */
    viewHref?: string | null;
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
  // svelte-ignore state_referenced_locally
  let loadedKey = contentKey;

  $effect(() => {
    const current = html;

    // Untracked: the callback reads the page's active language, and tracking that would
    // re-run this with the previous language's HTML and overwrite the next one's.
    untrack(() => onchange?.(current));
  });

  $effect(() => {
    const key = contentKey;

    if (key === loadedKey) {
      return;
    }

    loadedKey = key;

    untrack(() => {
      html = value ?? '';
      editor?.commands.setContent(html, { emitUpdate: false });
      words = editor?.storage.characterCount.words() ?? 0;
    });
  });
  let uploading = $state(false);
  let words = $state(0);
  // Bumped on every editor transaction so toolbar state re-evaluates.
  let tick = $state(0);
  let pickerOpen = $state(false);
  let toolbarOpen = $state(false);
  let host = $state<HTMLDivElement>();
  let linkPopover = $state<{ start(): void }>();
  let blockInfo = $state.raw<BlockInfo | null>(null);
  // Floating language picker anchored to the code block under the caret.
  let codeBadge = $state({ visible: false, top: 0, right: 0 });
  let pickerMode = $state<'insert' | 'replace'>('insert');
  let ctx = $state({
    open: false,
    x: 0,
    y: 0,
    kind: 'context' as 'context' | 'type' | 'align' | 'table' | 'device',
  });
  let ctxEl = $state<HTMLDivElement>();
  let menuEl = $state<HTMLDivElement>();
  let focused = $state(false);
  let sidebarOpen = $state(true);

  type Device = 'desktop' | 'tablet' | 'mobile';

  /** Width of the writing canvas, to preview the article on other screens. */
  let device = $state<Device>('desktop');

  /** The device preview only exists on desktop screens (same breakpoint as the sidebar's default). */
  let isDesktop = $state(true);

  const deviceOptions = [
    { value: 'desktop', label: t('ui.desktop'), icon: Monitor },
    { value: 'tablet', label: t('ui.tablet'), icon: Tablet },
    { value: 'mobile', label: t('ui.ponsel'), icon: Smartphone },
  ] as const;
  const DeviceIcon = $derived(
    deviceOptions.find((option) => option.value === device)?.icon ?? Monitor,
  );
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

  type BlockKind =
    | 'image'
    | 'buttonBlock'
    | 'linkCard'
    | 'callout'
    | 'codeBlock'
    | 'definition'
    | 'scriptureQuote'
    | 'specSheet'
    | 'table'
    | 'mathBlock'
    | 'mathInline';
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
    success: t('ui.sukses'),
    warning: t('ui.peringatan'),
    danger: t('ui.bahaya'),
    dark: t('ui.gelap'),
  };

  const scriptureLabels: Record<ScriptureKind, string> = {
    bible: t('ui.alkitab'),
    quran: t('ui.al_quran'),
    hadith: t('ui.hadis'),
  };

  /** Steps the Arabic font size one notch smaller (-1) or larger (+1) within sm, md, lg, xl. */
  function stepArabicSize(direction: -1 | 1): void {
    const index = SCRIPTURE_ARABIC_SIZES.indexOf(
      blockInfo?.attrs.arabicSize ?? 'md',
    );
    const next = SCRIPTURE_ARABIC_SIZES[index + direction];

    if (next) patchBlock({ arabicSize: next });
  }

  /** Switches the quote type, swapping the label only while it is still the old type's default. */
  function setScriptureKind(kind: ScriptureKind): void {
    const current = blockInfo?.attrs;

    if (!current) return;

    const isDefaultLabel =
      !current.label ||
      current.label === scriptureLabels[current.kind as ScriptureKind] ||
      current.label === SCRIPTURE_LABELS[current.kind as ScriptureKind];

    patchBlock({
      kind,
      ...(isDefaultLabel ? { label: scriptureLabels[kind] } : {}),
    });
  }

  const blockLabels: Record<string, string> = {
    paragraph: t('ui.paragraf'),
    bulletList: t('ui.daftar_poin'),
    orderedList: t('ui.daftar_angka'),
    blockquote: t('ui.kutipan'),
    codeBlock: t('ui.blok_kode'),
    callout: 'Callout',
    image: t('admin.gambar'),
    buttonBlock: t('ui.tombol'),
    linkCard: t('ui.kartu_tautan'),
    horizontalRule: t('ui.garis_pemisah'),
    table: t('ui.tabel'),
    definition: t('ui.kartu_definisi'),
    scriptureQuote: t('ui.kutipan_kitab_suci'),
    specSheet: t('ui.kartu_spesifikasi'),
    references: t('ui.daftar_referensi'),
    mathBlock: t('ui.rumus_blok'),
    mathInline: t('ui.rumus_sebaris'),
  };
  const blockDescriptions: Record<string, string> = {
    paragraph: t('ui.mulai_dengan_komponen_dasar_dari_semua'),
    heading: t('ui.beri_judul_pada_bagian_tulisan_anda'),
    bulletList: t('ui.buat_daftar_poin_biasa'),
    orderedList: t('ui.buat_daftar_bernomor'),
    blockquote: t('ui.beri_penekanan_visual_pada_kutipan'),
    codeBlock: t('ui.tampilkan_cuplikan_kode_dengan_pewarnaan_sintaks'),
    callout: t('ui.kotak_catatan_berwarna_untuk_info_penting'),
    image: t('ui.sisipkan_gambar_dari_pustaka_media'),
    buttonBlock: t('ui.tautan_ajakan_bertindak_berbentuk_tombol'),
    linkCard: t('ui.pratinjau_halaman_web_lengkap_dengan_gambar'),
    horizontalRule: t('ui.pisahkan_bagian_tulisan_dengan_garis'),
    table: t('ui.susun_data_dalam_baris_dan_kolom'),
    definition: t('ui.kartu_istilah_dengan_pelafalan_dan_artinya'),
    scriptureQuote: t('ui.kutipan_ayat_alkitab_quran_atau_hadis'),
    specSheet: t('ui.lembar_pasangan_label_dan_nilai_mis'),
    references: t('ui.daftar_sumber_atau_rujukan_bernomor'),
    mathBlock: t('ui.persamaan_matematika_tampil_di_tengah_latex'),
    mathInline: t('ui.rumus_matematika_di_tengah_kalimat_latex'),
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
    linkCard: ExternalLink,
    horizontalRule: Minus,
    table: Table2,
    definition: BookA,
    scriptureQuote: BookOpenText,
    specSheet: ClipboardList,
    references: BookText,
    mathBlock: Sigma,
    mathInline: Sigma,
  };
  const alignKeys = ['left', 'center', 'right'] as const;
  const headingLevels = [1, 2, 3] as const;
  const alignLabels = {
    left: t('ui.rata_kiri'),
    center: t('ui.rata_tengah'),
    right: t('ui.rata_kanan'),
  } as const;
  const alignIcons = {
    left: AlignLeft,
    center: AlignCenter,
    right: AlignRight,
  } as const;

  const imageWidths = [25, 50, 75, 100];
  const headingItems = [
    { value: '0', label: t('ui.paragraf') },
    { value: '1', label: t('ui.judul_1') },
    { value: '2', label: t('ui.judul_2') },
    { value: '3', label: t('ui.judul_3') },
  ];
  const widthItems = imageWidths.map((width) => ({
    value: String(width),
    label: `${width}%`,
  }));
  const buttonVariantItems = [
    { value: 'solid', label: 'Solid' },
    { value: 'outline', label: t('ui.garis_tepi') },
  ];
  const languageItems = [
    ...languageNames().map((name) => ({ value: name, label: name })),
    { value: 'plaintext', label: t('ui.teks_biasa') },
  ];
  const themeItems = CODE_THEMES.filter((theme) => theme.value !== '').map(
    (theme) => ({
      value: theme.value,
      label: `Tema: ${theme.label}`,
    }),
  );
  const defaultThemeLabel = `Tema: ${CODE_THEMES.find((theme) => theme.value === '')?.label ?? t('ui.otomatis')}`;

  const blockItems: BlockItem[] = [
    {
      label: t('ui.paragraf'),
      hint: t('ui.teks_biasa'),
      icon: Pilcrow,
      keywords: 'paragraph teks',
      run: (c) => c.setParagraph(),
    },
    {
      label: t('ui.judul_1'),
      hint: t('ui.judul_besar'),
      icon: Heading1,
      keywords: 'heading h1',
      run: (c) => c.setHeading({ level: 1 }),
    },
    {
      label: t('ui.judul_2'),
      hint: t('ui.subjudul'),
      icon: Heading2,
      keywords: 'heading h2',
      run: (c) => c.setHeading({ level: 2 }),
    },
    {
      label: t('ui.judul_3'),
      hint: t('ui.subjudul_kecil'),
      icon: Heading3,
      keywords: 'heading h3',
      run: (c) => c.setHeading({ level: 3 }),
    },
    {
      label: t('admin.gambar'),
      hint: t('ui.dari_pustaka_media'),
      icon: ImageIcon,
      keywords: 'image foto gambar',
      picker: true,
    },
    {
      label: 'Callout',
      hint: t('ui.kotak_catatan_judul_dan_label_opsional'),
      icon: Info,
      keywords: 'callout note catatan info peringatan kotak judul label kategori',
      run: (c) => c.wrapIn('callout', { type: 'info' }),
    },
    {
      label: t('ui.kartu_tautan'),
      hint: t('ui.pratinjau_halaman_web'),
      icon: ExternalLink,
      keywords: 'link card tautan referensi web preview whatsapp',
      action: insertLinkCard,
    },
    {
      label: t('ui.tombol'),
      hint: t('ui.tautan_ajakan_bertindak'),
      icon: MousePointerClick,
      keywords: 'button tombol cta link tautan',
      action: insertButton,
    },
    {
      label: t('ui.daftar_poin'),
      hint: t('ui.daftar_tak_berurut'),
      icon: List,
      keywords: 'bullet list daftar',
      run: (c) => c.toggleBulletList(),
    },
    {
      label: t('ui.daftar_angka'),
      hint: t('ui.daftar_berurut'),
      icon: ListOrdered,
      keywords: 'ordered number list daftar',
      run: (c) => c.toggleOrderedList(),
    },
    {
      label: t('ui.kutipan'),
      hint: t('ui.sorot_kutipan'),
      icon: Quote,
      keywords: 'quote blockquote',
      run: (c) => c.toggleBlockquote(),
    },
    {
      label: t('ui.blok_kode'),
      hint: t('ui.cuplikan_kode'),
      icon: SquareCode,
      keywords: 'code kode',
      run: (c) => c.toggleCodeBlock({ lineNumbers: true } as never),
    },
    {
      label: t('ui.garis_pemisah'),
      hint: t('ui.pemisah_bagian'),
      icon: Minus,
      keywords: 'divider hr garis',
      run: (c) => c.setHorizontalRule(),
    },
    {
      label: t('ui.tabel'),
      hint: t('ui.baris_dan_kolom_data'),
      icon: Table2,
      keywords: 'table tabel baris kolom row column grid',
      run: (c) => c.insertContent({ type: 'tableSetup' }),
    },
    {
      label: t('ui.rumus_blok'),
      hint: t('ui.persamaan_di_tengah_latex'),
      icon: Sigma,
      keywords: 'math equation formula latex rumus persamaan matematika',
      action: () => insertAtomAndSelect('mathBlock'),
    },
    {
      label: t('ui.rumus_sebaris'),
      hint: t('ui.rumus_di_tengah_kalimat'),
      icon: Sigma,
      keywords: 'inline math formula latex rumus sebaris',
      action: () => insertAtomAndSelect('mathInline'),
    },
    {
      label: t('ui.kartu_spesifikasi'),
      hint: t('ui.pasangan_label_dan_nilai'),
      icon: ClipboardList,
      keywords: 'spec specification sheet matrix label value spesifikasi data',
      run: (c) =>
        c.insertContent({
          type: 'specSheet',
          attrs: { title: t('ui.spesifikasi') },
          content: [1, 2].map(() => ({
            type: 'specRow',
            content: [{ type: 'specLabel' }, { type: 'specValue' }],
          })),
        }),
    },
    {
      label: t('ui.kartu_definisi'),
      hint: t('ui.istilah_pelafalan_dan_arti'),
      icon: BookA,
      keywords: 'definition dictionary term kamus istilah arti glossary',
      run: (c) => c.wrapIn('definition', { term: t('ui.istilah') }),
    },
    {
      label: t('ui.kutipan_kitab_suci'),
      hint: t('ui.alkitab_quran_dan_hadis'),
      icon: BookOpenText,
      keywords: 'scripture quote alkitab bible quran al-quran ayat hadis hadith kutipan kitab suci',
      run: (c) =>
        c.insertContent({
          type: 'scriptureQuote',
          attrs: { kind: 'bible', label: scriptureLabels.bible },
          content: [{ type: 'paragraph' }],
        }),
    },
    {
      label: t('ui.daftar_referensi'),
      hint: t('ui.sumber_bernomor'),
      icon: BookText,
      keywords:
        'references bibliography sources citations referensi sumber pustaka',
      run: (c) =>
        c.insertContent({
          type: 'references',
          content: [{ type: 'reference' }, { type: 'reference' }],
        }),
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
          ? t('ui.judul_level', { level: node.attrs.level })
          : (blockLabels[kind] ?? kind),
    };
  });

  /** Closest list around the caret (nested lists included), for its numbering / marker settings. */
  const listInfo = $derived.by(() => {
    void tick;

    if (minimal || !editor) return null;

    const caret = editor.state.selection.$from;

    for (let depth = caret.depth; depth > 0; depth--) {
      const node = caret.node(depth);

      if (node.type.name === 'orderedList' || node.type.name === 'bulletList') {
        return { kind: node.type.name, pos: caret.before(depth), attrs: node.attrs };
      }
    }

    return null;
  });

  const listTypeItems = [
    { value: '1', label: t('ui.penomoran_angka') },
    { value: 'a', label: t('ui.penomoran_huruf_kecil') },
    { value: 'A', label: t('ui.penomoran_huruf_besar') },
    { value: 'i', label: t('ui.penomoran_romawi_kecil') },
    { value: 'I', label: t('ui.penomoran_romawi_besar') },
  ];
  const bulletStyleLabels: Record<(typeof BULLET_STYLES)[number], string> = {
    disc: t('ui.penanda_titik'),
    circle: t('ui.penanda_lingkaran'),
    square: t('ui.penanda_kotak'),
    dash: t('ui.penanda_strip'),
  };
  const bulletStyleItems = BULLET_STYLES.map((value) => ({
    value,
    label: bulletStyleLabels[value],
  }));

  /** Changes the numbering (`type`, `start`) or marker (`type`) of the list under the caret. */
  function patchList(patch: { type?: string | null; start?: number }): void {
    const target = listInfo;

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
    kind: 'type' | 'align' | 'context' | 'table' | 'device',
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

      return kind === 'image' ||
        kind === 'buttonBlock' ||
        kind === 'linkCard' ||
        kind === 'mathBlock' ||
        kind === 'mathInline'
        ? { kind, pos: selection.from, attrs: selection.node.attrs }
        : null;
    }

    for (let depth = selection.$from.depth; depth > 0; depth--) {
      const node = selection.$from.node(depth);

      if (
        ['image', 'callout', 'codeBlock', 'definition', 'scriptureQuote', 'specSheet', 'table'].includes(
          node.type.name,
        )
      ) {
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

  function insertLinkCard(): void {
    if (!editor) return;

    editor.chain().focus().insertContent({ type: 'linkCard' }).run();

    const before = editor.state.selection.$from.nodeBefore;

    if (before?.type.name === 'linkCard') {
      editor.commands.setNodeSelection(
        editor.state.selection.from - before.nodeSize,
      );
    }
  }

  let fetchingPreview = $state(false);

  /** Fills the selected link card from the page's Open Graph metadata. */
  async function loadLinkPreview(url: string): Promise<void> {
    fetchingPreview = true;

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

      if (!response.ok) {
        toast.error(t('ui.pratinjau_tautan_tidak_dapat_diambil_isi'));

        return;
      }

      patchBlock(await response.json());
    } catch {
      toast.error(t('ui.pratinjau_tautan_tidak_dapat_diambil'));
    } finally {
      fetchingPreview = false;
    }
  }

  /** Inserts an atom block (equation) and selects it so the block bar opens on it. */
  function insertAtomAndSelect(type: 'mathBlock' | 'mathInline'): void {
    if (!editor) return;

    editor.chain().focus().insertContent({ type }).run();

    const before = editor.state.selection.$from.nodeBefore;

    if (before?.type.name === type) {
      editor.commands.setNodeSelection(
        editor.state.selection.from - before.nodeSize,
      );
    }
  }

  /** Whether the first row / first column of the table under the caret are header cells. */
  function tableHeaders(): { row: boolean; column: boolean } {
    void tick;

    const table = blockInfo ? editor?.state.doc.nodeAt(blockInfo.pos) : null;

    if (table?.type.name !== 'table') return { row: false, column: false };

    const isHeader = (cell: { type: { name: string } }): boolean =>
      cell.type.name === 'tableHeader';
    const cellsOf = (row: { childCount: number; child: (i: number) => any }) =>
      Array.from({ length: row.childCount }, (_, i) => row.child(i));
    const rows = Array.from({ length: table.childCount }, (_, i) =>
      table.child(i),
    );

    return {
      row: cellsOf(rows[0]).every(isHeader),
      column: rows.every((row) => isHeader(row.child(0))),
    };
  }

  function addSpecRow(): void {
    const target = blockInfo;
    const sheet = target ? editor?.state.doc.nodeAt(target.pos) : null;

    if (!editor || !target || sheet?.type.name !== 'specSheet') return;

    const end = target.pos + sheet.nodeSize - 1;

    editor
      .chain()
      .focus()
      .insertContentAt(end, {
        type: 'specRow',
        content: [{ type: 'specLabel' }, { type: 'specValue' }],
      })
      .setTextSelection(end + 2)
      .run();
  }

  function removeSpecRow(): void {
    if (!editor) return;

    const { $from: caret } = editor.state.selection;

    for (let depth = caret.depth; depth > 0; depth--) {
      if (caret.node(depth).type.name !== 'specRow') continue;

      if (caret.node(depth - 1).childCount <= 1) return;

      editor
        .chain()
        .focus()
        .deleteRange({ from: caret.before(depth), to: caret.after(depth) })
        .run();

      return;
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

  /**
   * The top-level block under the caret as clipboard data, in ProseMirror's own format
   * (HTML with `data-pm-slice`), so pasting it into any editor of this app restores the
   * block exactly and other apps (Word, Docs) receive plain HTML.
   */
  function blockClipboard(): { html: string; text: string; range: { pos: number; size: number } } | null {
    const range = topBlock();
    const node = editor && range ? editor.state.doc.nodeAt(range.pos) : null;

    if (!editor || !range || !node) return null;

    const { dom, text } = editor.view.serializeForClipboard(
      new Slice(Fragment.from(node), 0, 0),
    );

    return { html: dom.innerHTML, text, range };
  }

  async function writeClipboard(html: string, text: string): Promise<boolean> {
    try {
      await navigator.clipboard.write([
        new ClipboardItem({
          'text/html': new Blob([html], { type: 'text/html' }),
          'text/plain': new Blob([text], { type: 'text/plain' }),
        }),
      ]);

      return true;
    } catch {
      // Async clipboard API unavailable or denied: fall back to a one-off copy event.
    }

    let written = false;
    const fill = (event: ClipboardEvent): void => {
      event.clipboardData?.setData('text/html', html);
      event.clipboardData?.setData('text/plain', text);
      event.preventDefault();
      written = true;
    };

    document.addEventListener('copy', fill, { once: true });
    document.execCommand('copy');
    document.removeEventListener('copy', fill);

    return written;
  }

  /**
   * Copies the whole top-level block under the caret, whatever its type (like selecting
   * a paragraph in Word and pressing Ctrl+C); Ctrl+V then pastes it anywhere.
   */
  async function copyBlock(): Promise<boolean> {
    const data = blockClipboard();

    if (!data || !(await writeClipboard(data.html, data.text))) {
      toast.error(t('ui.izin_papan_klip_ditolak'));

      return false;
    }

    toast.success(t('ui.blok_disalin'));

    return true;
  }

  async function cutBlock(): Promise<void> {
    const data = blockClipboard();

    if (!data || !(await copyBlock())) return;

    deleteBlock(data.range);
  }

  /** Plain text blocks: selecting part of their text copies just that text, as in any editor. */
  const TEXT_BLOCKS = new Set(['paragraph', 'heading', 'blockquote', 'bulletList', 'orderedList', 'codeBlock']);

  /**
   * Whether a copy/cut should take the whole top-level block: nothing is selected (caret
   * only), or the selection covers all the text of a special block such as an image with
   * its caption, so a captioned image is always copied as one block (like Gutenberg).
   */
  function selectionMeansWholeBlock(): boolean {
    if (!editor) return false;

    const { selection } = editor.state;

    if (selection.empty) return true;

    if (selection instanceof NodeSelection || selection.$from.depth < 1) return false;

    const range = topBlock();
    const block = range ? editor.state.doc.nodeAt(range.pos) : null;

    if (!block || TEXT_BLOCKS.has(block.type.name)) return false;

    const start = selection.$from;
    const end = selection.$to;

    return (
      start.parent === end.parent &&
      start.parent.isTextblock &&
      start.parentOffset === 0 &&
      end.parentOffset === end.parent.content.size
    );
  }

  /**
   * Ctrl+C / Ctrl+X with nothing selected copies or cuts the whole block (as a code
   * editor copies the current line), so every block type can be copied with the caret in
   * it. A selection, including a selected node, keeps the browser's normal behaviour.
   */
  function clipboardEventForBlock(event: ClipboardEvent, cut: boolean): boolean {
    if (minimal || !editor || !event.clipboardData || !selectionMeansWholeBlock()) {
      return false;
    }

    const data = blockClipboard();

    // An empty paragraph has nothing worth replacing the clipboard with.
    if (!data || (editor.state.doc.nodeAt(data.range.pos)?.type.name === 'paragraph' && data.text === '')) {
      return false;
    }

    event.preventDefault();
    event.clipboardData.setData('text/html', data.html);
    event.clipboardData.setData('text/plain', data.text);

    if (cut) deleteBlock(data.range);

    toast.success(t(cut ? 'ui.blok_dipotong' : 'ui.blok_disalin'));

    return true;
  }

  /** Pastes the clipboard (HTML when available, else text) as new block(s) below the current block. */
  async function pasteBlockBelow(): Promise<void> {
    if (!editor) return;

    const range = topBlock();
    const position = range ? range.pos + range.size : editor.state.doc.content.size;

    try {
      let content = '';

      for (const item of await navigator.clipboard.read()) {
        if (item.types.includes('text/html')) {
          content = await (await item.getType('text/html')).text();
          break;
        }
      }

      if (content === '') {
        const text = (await navigator.clipboard.readText()).trim();

        content = text
          .split(/\n{2,}/)
          .map((paragraph) => `<p>${paragraph.replace(/[&<>]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' })[c] as string).replaceAll('\n', '<br>')}</p>`)
          .join('');
      }

      if (content === '') return;

      editor.chain().focus().insertContentAt(position, content).run();
    } catch {
      toast.error(t('ui.izin_papan_klip_ditolak'));
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
      toast.error(t('ui.izin_papan_klip_ditolak'));
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

    if (ctx.kind === 'table') {
      const run =
        (command: (chain: ChainedCommands) => ChainedCommands) => () =>
          command(instance.chain().focus()).run();

      return [
        [
          {
            label: t('ui.sisipkan_baris_di_atas'),
            icon: BetweenHorizontalStart,
            action: run((c) => c.addRowBefore()),
          },
          {
            label: t('ui.sisipkan_baris_di_bawah'),
            icon: BetweenHorizontalEnd,
            action: run((c) => c.addRowAfter()),
          },
          {
            label: t('ui.hapus_baris'),
            icon: TableRowsSplit,
            action: run((c) => c.deleteRow()),
          },
        ],
        [
          {
            label: t('ui.sisipkan_kolom_di_kiri'),
            icon: BetweenVerticalStart,
            action: run((c) => c.addColumnBefore()),
          },
          {
            label: t('ui.sisipkan_kolom_di_kanan'),
            icon: BetweenVerticalEnd,
            action: run((c) => c.addColumnAfter()),
          },
          {
            label: t('ui.hapus_kolom'),
            icon: Table2,
            action: run((c) => c.deleteColumn()),
          },
        ],
        [
          {
            label: t('ui.hapus_tabel'),
            icon: Trash2,
            danger: true,
            action: run((c) => c.deleteTable()),
          },
        ],
      ];
    }

    if (ctx.kind === 'device') {
      return [
        deviceOptions.map<ContextItem>((option) => ({
          label: option.label,
          icon: option.icon,
          on: device === option.value,
          action: () => (device = option.value),
        })),
        ...(viewHref
          ? [
              [
                {
                  label: t('ui.pratinjau_di_tab_baru'),
                  icon: ExternalLink,
                  action: () => {
                    window.open(viewHref, '_blank', 'noopener,noreferrer');
                  },
                },
              ],
            ]
          : []),
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
          label: t('ui.salin'),
          shortcut: 'Ctrl+C',
          disabled: !hasSelection && minimal,
          action: () => (hasSelection ? clipboardCommand('copy') : void copyBlock()),
        },
        {
          label: t('ui.potong'),
          shortcut: 'Ctrl+X',
          disabled: !hasSelection && minimal,
          action: () => (hasSelection ? clipboardCommand('cut') : void cutBlock()),
        },
        { label: t('ui.tempel'), shortcut: 'Ctrl+V', action: pasteFromClipboard },
      ],
    ];

    if (!minimal) {
      groups[0].push(
        { label: t('ui.gandakan_blok'), shortcut: 'Ctrl+Shift+D', action: duplicateBlock },
        { label: t('ui.salin_blok'), action: () => void copyBlock() },
        { label: t('ui.potong_blok'), action: () => void cutBlock() },
        { label: t('ui.tempel_di_bawah_blok'), action: () => void pasteBlockBelow() },
        { label: t('ui.tambahkan_sebelumnya'), action: () => addParagraph('before') },
        { label: t('ui.tambahkan_setelahnya'), action: () => addParagraph('after') },
      );
    }

    const format: ContextItem[] = [
      {
        label: t('ui.tebal'),
        shortcut: 'Ctrl+B',
        on: active('bold'),
        action: () => instance.chain().focus().toggleBold().run(),
      },
      {
        label: t('ui.miring'),
        shortcut: 'Ctrl+I',
        on: active('italic'),
        action: () => instance.chain().focus().toggleItalic().run(),
      },
      {
        label: t('ui.garis_bawah'),
        shortcut: 'Ctrl+U',
        on: active('underline'),
        action: () => instance.chain().focus().toggleUnderline().run(),
      },
      {
        label: t('ui.coret'),
        on: active('strike'),
        action: () => instance.chain().focus().toggleStrike().run(),
      },
    ];

    groups.push(format);

    if (minimal) return groups;

    format.push(
      {
        label: t('ui.kode_inline'),
        on: active('code'),
        action: () => instance.chain().focus().toggleCode().run(),
      },
      {
        label: active('link') ? t('ui.ubah_tautan') : t('ui.tautan'),
        on: active('link'),
        action: setLink,
      },
    );

    groups.push([
      {
        label: t('ui.ubah_menjadi'),
        action: () =>
          setTimeout(() => Object.assign(ctx, { open: true, kind: 'type' })),
      },
      {
        label: t('ui.pindah_ke_atas'),
        disabled: !canMove(-1),
        action: () => moveBlock(-1),
      },
      {
        label: t('ui.pindah_ke_bawah'),
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
          label: t('ui.ganti_gambar_2'),
          action: () => openPicker('replace'),
        },
      ]);
    }

    if (blockInfo?.kind === 'codeBlock') {
      const current = blockInfo.attrs.language ?? '';

      groups.push(
        [
          { label: t('ui.bahasa_otomatis'), value: '' },
          ...languageNames().map((name) => ({
            label: `Bahasa: ${name}`,
            value: name,
          })),
          { label: t('ui.bahasa_teks_biasa'), value: 'plaintext' },
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
      { label: t('ui.sunting_sebagai_html'), action: () => setMode('html') },
    ]);

    groups.push([
      {
        label: t('security.hapus'),
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
          : t('ui.gambar_gagal_diunggah'),
      );
    } finally {
      uploading = false;
    }
  }

  /** Opens the link popover: preview of the link under the caret, or a new link on the selection. */
  function setLink(): void {
    linkPopover?.start();
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
          : [
              FigureImage,
              BulletListStyle,
              LinkCard,
              Callout,
              ButtonBlock,
              Definition,
              ScriptureQuote,
              SpecSheet,
              SpecRow,
              SpecLabel,
              SpecValue,
              References,
              Reference,
              MathInline,
              MathBlock,
              ...TableBlocks,
              TableSetup,
              CodeBlockHighlight,
              LinkHighlight,
            ]),
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
          // The contenteditable textbox needs an accessible name.
          'aria-label': placeholder || t('ui.editor_teks'),
        },
        handleKeyDown: (_view, event) => {
          if (!minimal && (event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            setLink();

            return true;
          }

          if (
            !minimal &&
            (event.ctrlKey || event.metaKey) &&
            event.shiftKey &&
            event.key.toLowerCase() === 'd'
          ) {
            event.preventDefault();
            duplicateBlock();

            return true;
          }

          return handleMenuKey(event);
        },
        handleDOMEvents: {
          click: (_view, event) => {
            // Link cards are previews while editing; they must not navigate.
            if ((event.target as HTMLElement).closest('a.wp-block-linkcard')) {
              event.preventDefault();
            }

            return false;
          },
          copy: (_view, event) => clipboardEventForBlock(event, false),
          cut: (_view, event) => clipboardEventForBlock(event, true),
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
        html = current.isEmpty
          ? ''
          : current.getHTML().replaceAll('<figcaption></figcaption>', '');
        words = current.storage.characterCount.words();
      },
      onTransaction: () => {
        // A transaction can be dispatched while Svelte is evaluating a derived or
        // template expression, where mutating state throws `state_unsafe_mutation`.
        queueMicrotask(() => {
          if (!editor) return;
          tick++;
          blockInfo = readBlock();
          syncCodeBadge();
          syncBlockUi();
          syncToolbar();
        });
      },
      // Focus and blur can fire inside a ProseMirror dispatch while Svelte is
      // flushing effects, so state is mutated on a microtask.
      onFocus: () => {
        queueMicrotask(() => {
          focused = true;
          if (menu.source === 'top') closeMenu();
          syncToolbar();
        });
      },
      onBlur: () => {
        queueMicrotask(() => {
          focused = false;
          if (menu.source === 'slash') closeMenu();
          syncToolbar();
        });
      },
    });

    editor = instance;
    words = instance.storage.characterCount.words();

    if (window.innerWidth < 992) sidebarOpen = false;

    const resync = (): void => syncToolbar();

    const desktopQuery = window.matchMedia('(min-width: 992px)');
    const syncDesktop = (): void => {
      isDesktop = desktopQuery.matches;

      // Shrinking below desktop drops the preview straight back to full width.
      if (!isDesktop) {
        device = 'desktop';
        if (ctx.kind === 'device') ctx.open = false;
      }
    };

    syncDesktop();
    desktopQuery.addEventListener('change', syncDesktop);
    window.addEventListener('scroll', resync, true);
    window.addEventListener('resize', resync);

    return () => {
      desktopQuery.removeEventListener('change', syncDesktop);
      window.removeEventListener('scroll', resync, true);
      window.removeEventListener('resize', resync);
      // Cleared first: microtasks queued by focus/transaction callbacks still run after
      // the destroy and must see no editor, or Tiptap throws on `view.nodeDOM`.
      editor = undefined;
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
    {@const headKind =
      blockInfo?.kind === 'mathInline' ? 'mathInline' : currentBlock.kind}
    {@const BlockIcon = blockIcons[headKind] ?? Pilcrow}
    <div class="rte-ins">
      <div class="rte-ins-head">
        <BlockIcon size={20} />
        <div>
          <strong
            >{headKind === currentBlock.kind
              ? currentBlock.label
              : blockLabels[headKind]}</strong
          >
          <p class="rte-ins-desc">{blockDescriptions[headKind]}</p>
        </div>
      </div>

      {#if blockInfo?.kind === 'image'}
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.perataan')}</span>
          {@render alignButtons(setAlign)}
        </div>
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.lebar')}</span>
          <Select
            inline
            searchable={false}
            size="sm"
            class="rte-select"
            ariaLabel={t('ui.lebar_gambar')}
            placeholder={t('ui.lebar_otomatis')}
            items={widthItems}
            value={blockInfo.attrs.width ? String(blockInfo.attrs.width) : null}
            onchange={(next) => patchBlock({ width: Number(next) || null })}
          />
        </div>
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.teks_alternatif')}</span>
          <input
            class="form-control form-control-sm"
            placeholder={t('ui.teks_alternatif')}
            aria-label={t('ui.teks_alternatif')}
            value={blockInfo.attrs.alt ?? ''}
            oninput={(event) => patchBlock({ alt: event.currentTarget.value })}
          />
        </div>
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.kredit_foto')}</span>
          <input
            class="form-control form-control-sm"
            placeholder={t('ui.mis_workwear_archive')}
            aria-label={t('ui.kredit_foto')}
            maxlength="60"
            value={blockInfo.attrs.credit ?? ''}
            oninput={(event) =>
              patchBlock({ credit: cleanBlockText(event.currentTarget.value) })}
          />
        </div>
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.sumber_asli')}</span>
          <input
            class="form-control form-control-sm"
            type="url"
            placeholder="https://… (opsional)"
            aria-label={t('ui.tautan_sumber_asli')}
            value={blockInfo.attrs.source ?? ''}
            oninput={(event) =>
              patchBlock({ source: sourceUrl(event.currentTarget.value) })}
          />
        </div>
        <p class="rte-ins-desc">
          {t('ui.ketik_keterangan_langsung_di_bawah_gambar')}
        </p>
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary align-self-start"
          onclick={() => openPicker('replace')}>{t('ui.ganti_gambar')}</button
        >
      {:else if blockInfo?.kind === 'linkCard'}
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.alamat_halaman')}</span>
          <input
            class="form-control form-control-sm"
            type="url"
            placeholder="https://contoh.com/artikel"
            aria-label={t('ui.alamat_halaman')}
            value={blockInfo.attrs.url ?? ''}
            oninput={(event) =>
              patchBlock({ url: sourceUrl(event.currentTarget.value) })}
          />
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary align-self-start"
            disabled={fetchingPreview || !blockInfo.attrs.url}
            onclick={() => loadLinkPreview(String(blockInfo?.attrs.url ?? ''))}
            >{fetchingPreview ? t('ui.mengambil') : t('ui.ambil_pratinjau')}</button
          >
        </div>
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.judul')}</span>
          <input
            class="form-control form-control-sm"
            aria-label={t('ui.judul_kartu_tautan')}
            value={blockInfo.attrs.title ?? ''}
            oninput={(event) => patchBlock({ title: event.currentTarget.value })}
          />
        </div>
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.nama_situs')}</span>
          <input
            class="form-control form-control-sm"
            aria-label={t('ui.nama_situs')}
            maxlength="60"
            value={blockInfo.attrs.siteName ?? ''}
            oninput={(event) =>
              patchBlock({ siteName: cleanBlockText(event.currentTarget.value) })}
          />
        </div>
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.gambar_url')}</span>
          <input
            class="form-control form-control-sm"
            type="url"
            placeholder="https://… (opsional)"
            aria-label={t('ui.url_gambar_pratinjau')}
            value={blockInfo.attrs.image ?? ''}
            oninput={(event) =>
              patchBlock({ image: sourceUrl(event.currentTarget.value) })}
          />
        </div>
      {:else if blockInfo?.kind === 'buttonBlock'}
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.teks_tombol')}</span>
          <input
            class="form-control form-control-sm"
            placeholder={t('ui.teks_tombol')}
            aria-label={t('ui.teks_tombol')}
            value={blockInfo.attrs.text ?? ''}
            oninput={(event) => patchBlock({ text: event.currentTarget.value })}
          />
        </div>
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.tautan')}</span>
          <input
            class="form-control form-control-sm"
            placeholder="https://..."
            aria-label={t('ui.alamat_tautan_tombol')}
            value={blockInfo.attrs.href ?? ''}
            onchange={(event) => {
              const href = safeHref(event.currentTarget.value);
              event.currentTarget.value = href;
              patchBlock({ href });
            }}
          />
        </div>
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.gaya')}</span>
          <Select
            inline
            required
            searchable={false}
            size="sm"
            class="rte-select"
            ariaLabel={t('ui.gaya_tombol')}
            items={buttonVariantItems}
            value={blockInfo.attrs.variant}
            onchange={(next) => patchBlock({ variant: next })}
          />
        </div>
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.perataan')}</span>
          {@render alignButtons(setAlign)}
        </div>
      {:else if blockInfo?.kind === 'codeBlock'}
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.bahasa')}</span>
          <Select
            inline
            size="sm"
            class="rte-select"
            ariaLabel={t('ui.bahasa_pemrograman')}
            placeholder={t('ui.otomatis')}
            items={languageItems}
            value={blockInfo.attrs.language || null}
            onchange={(next) => patchBlock({ language: next || null })}
          />
        </div>
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.judul')}</span>
          <input
            type="text"
            class="form-control form-control-sm"
            aria-label={t('ui.judul_blok_kode')}
            placeholder={t('ui.judul_mis_user_php')}
            maxlength="60"
            value={blockInfo.attrs.title ?? ''}
            disabled={!!blockInfo.attrs.titleHidden}
            onchange={(event) =>
              patchBlock({
                title: normalizeCodeTitle(event.currentTarget.value),
              })}
          />
        </div>
        <div class="rte-ins-section">
          <label class="rte-switch">
            <input
              type="checkbox"
              role="switch"
              checked={!blockInfo.attrs.titleHidden}
              onchange={(event) =>
                patchBlock({ titleHidden: !event.currentTarget.checked })}
            />
            <span class="rte-switch-track" aria-hidden="true"></span>
            <span>{t('code.show_title')}</span>
          </label>
          <label class="rte-switch">
            <input
              type="checkbox"
              role="switch"
              checked={!!blockInfo.attrs.lineNumbers}
              onchange={(event) =>
                patchBlock({ lineNumbers: event.currentTarget.checked })}
            />
            <span class="rte-switch-track" aria-hidden="true"></span>
            <span>{t('code.line_numbers')}</span>
          </label>
        </div>
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.tema')}</span>
          <Select
            inline
            size="sm"
            class="rte-select"
            ariaLabel={t('ui.tema_kode')}
            placeholder={defaultThemeLabel}
            items={themeItems}
            value={blockInfo.attrs.theme || null}
            onchange={(next) => patchBlock({ theme: next || null })}
          />
        </div>
        <div class="rte-code-preview wysiwyg-content-wrapper">
          <span class="rte-code-preview-label">{t('ui.pratinjau')}</span>
          <pre data-code-theme={blockInfo.attrs.theme ?? undefined}><code
              use:codePreview={{
                source:
                  editor?.state.doc.nodeAt(blockInfo.pos)?.textContent ?? '',
                language: blockInfo.attrs.language ?? null,
              }}></code></pre>
        </div>
      {:else if blockInfo?.kind === 'callout'}
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.jenis')}</span>
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
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.kepala_callout')}</span>
          <div class="rte-ins-row">
            <button
              type="button"
              class="rte-chip"
              class:on={blockInfo.attrs.title !== ''}
              aria-pressed={blockInfo.attrs.title !== ''}
              onclick={() =>
                patchBlock({
                  title: blockInfo?.attrs.title ? '' : t('admin-notes.judul_catatan'),
                })}>{t('ui.judul')}</button
            >
            <button
              type="button"
              class="rte-chip"
              class:on={blockInfo.attrs.label !== ''}
              aria-pressed={blockInfo.attrs.label !== ''}
              onclick={() =>
                patchBlock({
                  label: blockInfo?.attrs.label ? '' : t('admin.kategori'),
                })}>{t('ui.label_kategori')}</button
            >
          </div>
        </div>
        {#if blockInfo.attrs.title}
          <div class="rte-ins-group">
            <span class="rte-ins-label">{t('ui.judul')}</span>
            <input
              class="form-control form-control-sm"
              aria-label={t('ui.judul_callout')}
              maxlength="60"
              value={blockInfo.attrs.title}
              oninput={(event) =>
                patchBlock({
                  title: cleanBlockText(event.currentTarget.value) || ' ',
                })}
            />
          </div>
        {/if}
        {#if blockInfo.attrs.label}
          <div class="rte-ins-group">
            <span class="rte-ins-label">{t('ui.label_kategori')}</span>
            <input
              class="form-control form-control-sm"
              aria-label={t('ui.label_kategori_callout')}
              maxlength="60"
              value={blockInfo.attrs.label}
              oninput={(event) =>
                patchBlock({
                  label: cleanBlockText(event.currentTarget.value) || ' ',
                })}
            />
          </div>
        {/if}
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary align-self-start"
          title={t('ui.keluarkan_isi_dari_callout')}
          onclick={() => editor?.chain().focus().lift('callout').run()}
          >{t('ui.lepas_kotak')}</button
        >
      {:else if blockInfo?.kind === 'definition'}
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.istilah')}</span>
          <input
            class="form-control form-control-sm"
            aria-label={t('ui.istilah')}
            maxlength="60"
            value={blockInfo.attrs.term ?? ''}
            oninput={(event) =>
              patchBlock({ term: cleanBlockText(event.currentTarget.value) })}
          />
        </div>
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.pelafalan')}</span>
          <input
            class="form-control form-control-sm"
            placeholder={t('ui.mis_rlm_opsional')}
            aria-label={t('ui.pelafalan')}
            maxlength="60"
            value={blockInfo.attrs.phonetic ?? ''}
            oninput={(event) =>
              patchBlock({
                phonetic: cleanBlockText(event.currentTarget.value),
              })}
          />
        </div>
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary align-self-start"
          title={t('ui.keluarkan_isi_dari_kartu')}
          onclick={() => editor?.chain().focus().lift('definition').run()}
          >{t('ui.lepas_kartu')}</button
        >
      {:else if blockInfo?.kind === 'scriptureQuote'}
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.jenis')}</span>
          <div class="rte-ins-row">
            {#each SCRIPTURE_KINDS as kind (kind)}
              <button
                type="button"
                class="rte-chip"
                class:on={blockInfo.attrs.kind === kind}
                onclick={() => setScriptureKind(kind)}
                >{scriptureLabels[kind]}</button
              >
            {/each}
          </div>
        </div>
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.label')}</span>
          <input
            class="form-control form-control-sm"
            aria-label={t('ui.label')}
            maxlength="30"
            value={blockInfo.attrs.label ?? ''}
            oninput={(event) =>
              patchBlock({
                label: cleanScriptureText(event.currentTarget.value, 'label'),
              })}
          />
        </div>
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.rujukan')}</span>
          <input
            class="form-control form-control-sm"
            placeholder={t('ui.mis_yohanes_14_27')}
            aria-label={t('ui.rujukan')}
            maxlength="80"
            value={blockInfo.attrs.reference ?? ''}
            oninput={(event) =>
              patchBlock({
                reference: cleanScriptureText(
                  event.currentTarget.value,
                  'reference',
                ),
              })}
          />
        </div>
        {#if blockInfo.attrs.kind === 'quran' || blockInfo.attrs.arabic}
          <div class="rte-ins-group">
            <span class="rte-ins-label">{t('ui.teks_arab')}</span>
            <textarea
              class="form-control form-control-sm"
              lang="ar"
              dir="rtl"
              rows="3"
              aria-label={t('ui.teks_arab')}
              maxlength="600"
              value={blockInfo.attrs.arabic ?? ''}
              oninput={(event) =>
                patchBlock({
                  arabic: cleanScriptureText(
                    event.currentTarget.value,
                    'arabic',
                  ),
                })}
            ></textarea>
          </div>
          <div class="rte-ins-group">
            <span class="rte-ins-label">{t('ui.ukuran_teks_arab')}</span>
            <div class="rte-ins-row align-items-center">
              <button
                type="button"
                class="rte-chip"
                aria-label={t('ui.perkecil_teks_arab')}
                title={t('ui.perkecil_teks_arab')}
                disabled={(blockInfo.attrs.arabicSize ?? 'md') ===
                  SCRIPTURE_ARABIC_SIZES[0]}
                onclick={() => stepArabicSize(-1)}>−</button
              >
              <span class="rte-chip on" aria-live="polite"
                >{(blockInfo.attrs.arabicSize ?? 'md').toUpperCase()}</span
              >
              <button
                type="button"
                class="rte-chip"
                aria-label={t('ui.perbesar_teks_arab')}
                title={t('ui.perbesar_teks_arab')}
                disabled={(blockInfo.attrs.arabicSize ?? 'md') ===
                  SCRIPTURE_ARABIC_SIZES[SCRIPTURE_ARABIC_SIZES.length - 1]}
                onclick={() => stepArabicSize(1)}>+</button
              >
            </div>
          </div>
        {/if}
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.sumber_kutipan')}</span>
          <input
            class="form-control form-control-sm"
            placeholder={t('ui.mis_yohanes_14_27_terjemahan_baru')}
            aria-label={t('ui.sumber_kutipan')}
            maxlength="160"
            value={blockInfo.attrs.source ?? ''}
            oninput={(event) =>
              patchBlock({
                source: cleanScriptureText(event.currentTarget.value, 'source'),
              })}
          />
        </div>
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary align-self-start"
          title={t('ui.keluarkan_isi_dari_kutipan')}
          onclick={() => editor?.chain().focus().lift('scriptureQuote').run()}
          >{t('ui.lepas_kutipan')}</button
        >
      {:else if blockInfo?.kind === 'specSheet'}
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.judul')}</span>
          <input
            class="form-control form-control-sm"
            placeholder={t('ui.judul_opsional')}
            aria-label={t('ui.judul_kartu_spesifikasi')}
            maxlength="60"
            value={blockInfo.attrs.title ?? ''}
            oninput={(event) =>
              patchBlock({ title: cleanBlockText(event.currentTarget.value) })}
          />
        </div>
        <div class="rte-ins-row">
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            onclick={addSpecRow}>{t('ui.tambah_baris')}</button
          >
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            onclick={removeSpecRow}>{t('ui.hapus_baris')}</button
          >
        </div>
        <p class="rte-ins-desc">{t('ui.tekan_enter_di_kolom_nilai_untuk')}</p>
      {:else if blockInfo?.kind === 'table'}
        {@const headers = tableHeaders()}
        <div class="rte-ins-section">
          <span class="rte-ins-title">{t('ui.pengaturan')}</span>
          <label class="rte-switch">
            <input
              type="checkbox"
              role="switch"
              checked={headers.row}
              onchange={() => editor?.chain().focus().toggleHeaderRow().run()}
            />
            <span class="rte-switch-track" aria-hidden="true"></span>
            <span>{t('ui.baris_header')}</span>
          </label>
          <label class="rte-switch">
            <input
              type="checkbox"
              role="switch"
              checked={headers.column}
              onchange={() =>
                editor?.chain().focus().toggleHeaderColumn().run()}
            />
            <span class="rte-switch-track" aria-hidden="true"></span>
            <span>{t('ui.kolom_header')}</span>
          </label>
        </div>
        <p class="rte-ins-desc">
          {t('ui.gunakan_tombol_tabel_di_toolbar_blok')}
        </p>
      {:else if blockInfo?.kind === 'mathBlock' || blockInfo?.kind === 'mathInline'}
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.rumus_latex')}</span>
          <textarea
            class="form-control form-control-sm font-monospace"
            rows="3"
            placeholder={'\\frac{a}{b}'}
            aria-label={t('ui.rumus_latex_2')}
            value={blockInfo.attrs.latex ?? ''}
            oninput={(event) =>
              patchBlock({ latex: event.currentTarget.value })}></textarea>
        </div>
        <p class="rte-ins-desc">
          {t('ui.contoh_rumus')}
        </p>
      {:else if currentBlock.kind === 'references'}
        <p class="rte-ins-desc">
          {t('ui.tekan_enter_untuk_menambah_rujukan_gunakan')}
        </p>
      {:else if currentBlock.kind === 'paragraph' || currentBlock.kind === 'heading'}
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.perataan_teks')}</span>
          {@render alignButtons(setAlign)}
        </div>
        {#if currentBlock.kind === 'heading'}
          <div class="rte-ins-group">
            <span class="rte-ins-label">{t('ui.tingkat_judul')}</span>
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

      {#if listInfo?.kind === 'orderedList'}
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.jenis_penomoran')}</span>
          <Select
            inline
            searchable={false}
            size="sm"
            class="rte-select"
            ariaLabel={t('ui.jenis_penomoran')}
            items={listTypeItems}
            value={listInfo.attrs.type || '1'}
            onchange={(next) => patchList({ type: String(next || '1') })}
          />
        </div>
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.mulai_dari_angka')}</span>
          <div class="d-flex gap-2">
            <input
              type="number"
              min="1"
              max="9999"
              class="form-control form-control-sm"
              aria-label={t('ui.mulai_dari_angka')}
              value={listInfo.attrs.start ?? 1}
              oninput={(event) => {
                const value = Math.floor(Number(event.currentTarget.value));

                if (value >= 1 && value <= 9999) patchList({ start: value });
              }}
            />
            <button
              type="button"
              class="btn btn-sm btn-outline-secondary text-nowrap"
              disabled={(listInfo.attrs.start ?? 1) === 1}
              onclick={() => patchList({ start: 1 })}
            >
              {t('ui.setel_ulang_nomor')}
            </button>
          </div>
        </div>
      {:else if listInfo?.kind === 'bulletList'}
        <div class="rte-ins-group">
          <span class="rte-ins-label">{t('ui.jenis_penanda')}</span>
          <Select
            inline
            searchable={false}
            size="sm"
            class="rte-select"
            ariaLabel={t('ui.jenis_penanda')}
            items={bulletStyleItems}
            value={listInfo.attrs.type || 'disc'}
            onchange={(next) => patchList({ type: next && next !== 'disc' ? String(next) : null })}
          />
        </div>
      {/if}

      <button
        type="button"
        class="btn btn-sm btn-outline-danger align-self-start"
        onclick={() => deleteBlock()}
      >
        <Trash size={14} /> {t('ui.hapus_blok')}
      </button>
    </div>
  {:else}
    <p class="rte-ins-empty">{t('ui.pilih_sebuah_blok_untuk_mengubah_pengaturannya')}</p>
  {/if}
{/snippet}

<div
  class="rte"
  class:is-invalid={invalid}
  class:rte-minimal={minimal}
  class:rte-shell={shell}
  class:is-device-tablet={shell && isDesktop && device === 'tablet'}
  class:is-device-mobile={shell && isDesktop && device === 'mobile'}
>
  <input type="hidden" {name} value={html} />

  <div class="rte-top">
    {#if shell && adminSidebar && (!adminSidebar.isDesktop || adminSidebar.collapsed)}
      <button
        type="button"
        class="rte-btn rte-btn-lg"
        title={t('ui.buka_tutup_menu_admin')}
        aria-label={t('ui.buka_tutup_menu_admin')}
        onclick={() => adminSidebar.toggle()}
        ><PanelLeftOpen size={18} /></button
      >
    {/if}

    {#if mode === 'visual' && editor}
      <div class="rte-tools" role="toolbar" aria-label={t('ui.alat_dokumen')}>
        {#if !minimal}
          <button
            type="button"
            class="rte-btn rte-btn-lg rte-inserter-toggle"
            class:on={menu.open && menu.source === 'top'}
            title={t('ui.sisipkan_blok')}
            aria-label={t('ui.sisipkan_blok')}
            aria-expanded={menu.open && menu.source === 'top'}
            data-rte-trigger
            onmousedown={(event) => event.preventDefault()}
            onclick={openTopInserter}><Plus size={18} /></button
          >
        {/if}
        <button
          type="button"
          class="rte-btn rte-btn-lg"
          title={t('ui.urungkan')}
          disabled={!editor.can().undo()}
          onclick={() => editor?.chain().focus().undo().run()}
          ><Undo2 size={18} /></button
        >
        <button
          type="button"
          class="rte-btn rte-btn-lg"
          title={t('ui.ulangi')}
          disabled={!editor.can().redo()}
          onclick={() => editor?.chain().focus().redo().run()}
          ><Redo2 size={18} /></button
        >
      </div>
    {/if}

    {#if shell}
      <div class="rte-doc-title">
        <span class="rte-doc-text" title={documentTitle}>
          {documentTitle || t('ui.tanpa_judul')}<span> · {rootLabel}</span>
        </span>
        {#if viewHref}
          <a
            href={viewHref}
            target="_blank"
            rel="noopener noreferrer"
            class="rte-view-link"
            title={t('ui.buka_pratinjau_di_tab_baru')}
            aria-label={t('ui.buka_pratinjau_di_tab_baru')}
            ><ExternalLink size={15} /></a
          >
        {/if}
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
            onclick={() => setMode('visual')}>{t('ui.visual')}</button
          >
          <button
            type="button"
            role="tab"
            aria-selected={mode === 'html'}
            class="rte-tab"
            class:on={mode === 'html'}
            onclick={() => setMode('html')}>{t('ui.html')}</button
          >
        </div>
      {/if}

      {#if shell}
        {#if isDesktop}
        <button
          type="button"
          class="rte-btn rte-btn-lg"
          class:on={device !== 'desktop' || (ctx.open && ctx.kind === 'device')}
          title={t('ui.pratinjau')}
          aria-label={t('ui.pratinjau')}
          aria-haspopup="menu"
          data-rte-trigger
          onclick={(event) => openToolbarMenu('device', event)}
          ><DeviceIcon size={18} /></button
        >
        {/if}
        {@render actions?.({ mode, setMode })}
        <button
          type="button"
          class="rte-btn rte-btn-lg rte-sidebar-toggle"
          class:on={sidebarOpen}
          title={t('ui.pengaturan')}
          aria-label={t('ui.pengaturan')}
          aria-pressed={sidebarOpen}
          onclick={() => (sidebarOpen = !sidebarOpen)}
          ><PanelRight size={18} /></button
        >
      {/if}
    </div>

    {#if !shell && blockInfo && mode === 'visual'}
      <div class="rte-blockbar" role="group" aria-label={t('ui.pengaturan_blok')}>
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

        {#if !minimal && editor && mode === 'visual'}
          <LinkPopover {editor} {host} bind:this={linkPopover} />
        {/if}

        {#if codeBadge.visible}
          <div
            class="rte-code-badge"
            style="top: {codeBadge.top}px; right: {codeBadge.right}px"
          >
            <Select
              inline
              size="sm"
              ariaLabel={t('ui.ubah_bahasa_blok_kode')}
              placeholder={t('ui.otomatis')}
              items={languageItems}
              value={blockInfo?.attrs.language || null}
              onchange={(next) => setCodeLanguage(next ? String(next) : '')}
            />
            <Select
              inline
              size="sm"
              ariaLabel={t('ui.ubah_tema_blok_kode')}
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
              title={t('ui.sisipkan_blok')}
              aria-label={t('ui.sisipkan_blok')}
              style="top: {inserter.top}px; left: {inserter.left}px"
              onmousedown={(event) => event.preventDefault()}
              onclick={openButtonMenu}><Plus size={16} /></button
            >
          {/if}

          {#if menu.open}
            <div
              class="rte-menu"
              class:is-fixed={menu.source === 'top'}
              in:overlayIn={{ side: 'bottom', origin: 'top left' }}
              out:overlayOut={{ origin: 'top left' }}
              role="listbox"
              tabindex="-1"
              aria-label={t('ui.sisipkan_blok')}
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
                    placeholder={t('ui.cari_blok')}
                    aria-label={t('ui.cari_blok')}
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
                <p class="rte-menu-empty">{t('ui.blok_tidak_ditemukan')}</p>
              {/each}
            </div>
          {/if}
        {/if}

        {#if mode === 'html'}
          <HtmlCodeEditor bind:value={html} ariaLabel={t('ui.kode_html')} />
        {/if}

        {#if shell}{@render after?.()}{/if}
      </div>
    </div>

    {#if shell}
      <aside
        class="rte-sidebar"
        class:is-open={sidebarOpen}
        aria-label={t('ui.pengaturan')}
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
              onclick={() => (sidebarTab = 'block')}>{t('ui.blok')}</button
            >
            <button
              type="button"
              class="rte-btn rte-side-close"
              title={t('ui.tutup_pengaturan')}
              aria-label={t('ui.tutup_pengaturan')}
              onclick={() => (sidebarOpen = false)}><X size={18} /></button
            >
          </div>
          <div class="rte-side-body">
            <!-- Stays mounted so its form fields keep submitting from the block tab. -->
            <div hidden={sidebarTab !== 'document'}>
              {@render settings?.()}
            </div>
            {#if sidebarTab !== 'document'}
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
      use:portal
      role="toolbar"
      aria-label={t('ui.pemformatan_blok')}
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
            title={t('ui.ubah_jenis_blok')}
            aria-label={t('ui.ubah_jenis_blok')}
            data-rte-trigger
            onclick={(event) => openToolbarMenu('type', event)}
            ><TypeIcon size={18} /></button
          >
          <div class="rte-tb-move">
            <button
              type="button"
              class="rte-btn"
              title={t('ui.pindah_ke_atas')}
              disabled={!canMove(-1)}
              onclick={() => moveBlock(-1)}><ArrowUp size={14} /></button
            >
            <button
              type="button"
              class="rte-btn"
              title={t('ui.pindah_ke_bawah')}
              disabled={!canMove(1)}
              onclick={() => moveBlock(1)}><ArrowDown size={14} /></button
            >
          </div>
          <button
            type="button"
            class="rte-btn"
            title={t('ui.salin_blok')}
            aria-label={t('ui.salin_blok')}
            onclick={() => void copyBlock()}><Copy size={14} /></button
          >
          <button
            type="button"
            class="rte-btn"
            title={`${t('ui.gandakan_blok')} (Ctrl+Shift+D)`}
            aria-label={t('ui.gandakan_blok')}
            aria-keyshortcuts="Control+Shift+D Meta+Shift+D"
            onclick={duplicateBlock}><CopyPlus size={14} /></button
          >
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
            title={t('ui.perataan')}
            aria-label={t('ui.perataan')}
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
          title={t('ui.tebal')}
          onclick={() => editor?.chain().focus().toggleBold().run()}
          ><Bold size={18} /></button
        >
        <button
          type="button"
          class="rte-btn rte-btn-lg"
          class:on={active('italic')}
          title={t('ui.miring')}
          onclick={() => editor?.chain().focus().toggleItalic().run()}
          ><Italic size={18} /></button
        >
        {#if !minimal}
          <button
            type="button"
            class="rte-btn rte-btn-lg"
            class:on={active('link')}
            title={t('ui.tautan')}
            aria-keyshortcuts="Control+K Meta+K"
            data-rte-link-trigger
            onmousedown={(event) => event.preventDefault()}
            onclick={setLink}><Link size={18} /></button
          >
        {:else}
          <button
            type="button"
            class="rte-btn rte-btn-lg"
            class:on={active('bulletList')}
            title={t('ui.daftar_poin')}
            onclick={() => editor?.chain().focus().toggleBulletList().run()}
            ><List size={18} /></button
          >
          <button
            type="button"
            class="rte-btn rte-btn-lg"
            class:on={active('orderedList')}
            title={t('ui.daftar_angka')}
            onclick={() => editor?.chain().focus().toggleOrderedList().run()}
            ><ListOrdered size={18} /></button
          >
          <button
            type="button"
            class="rte-btn rte-btn-lg"
            class:on={active('blockquote')}
            title={t('ui.kutipan')}
            onclick={() => editor?.chain().focus().toggleBlockquote().run()}
            ><Quote size={18} /></button
          >
        {/if}
      </div>
      {#if blockInfo?.kind === 'table'}
        <div class="rte-tb-group">
          <button
            type="button"
            class="rte-btn rte-btn-lg"
            class:on={ctx.open && ctx.kind === 'table'}
            title={t('ui.ubah_tabel')}
            aria-label={t('ui.ubah_tabel')}
            data-rte-trigger
            onclick={(event) => openToolbarMenu('table', event)}
            ><TableProperties size={18} /></button
          >
        </div>
      {/if}
      <div class="rte-tb-group">
        <button
          type="button"
          class="rte-btn rte-btn-lg"
          title={t('ui.opsi_lainnya')}
          aria-label={t('ui.opsi_lainnya')}
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
      use:portal
      in:overlayIn={{ side: 'bottom', origin: 'top left' }}
      out:overlayOut={{ origin: 'top left' }}
      role="menu"
      tabindex="-1"
      aria-label={t('ui.menu_konteks_editor')}
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
      {#if uploading}<span>{t('ui.mengunggah_gambar')}</span>{/if}
      <span>{words} kata</span>
    </span>
  </div>
</div>

{#if !minimal}
  <MediaPickerModal
    bind:open={pickerOpen}
    accept="image"
    title={t('ui.sisipkan_gambar')}
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
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.4rem 0.75rem 0.4rem 1rem;
    border-radius: 0.375rem;
    background: var(--neo-tertiary-bg);
    font-size: 0.8125rem;
  }
  .rte-doc-text {
    min-width: 0;
    overflow: hidden;
    text-align: center;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .rte-view-link {
    display: inline-flex;
    flex: 0 0 auto;
    color: inherit;
    opacity: 0.7;
    text-decoration: none;
  }
  .rte-view-link:hover {
    opacity: 1;
  }
  .rte-doc-text span {
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
    --rte-line: rgba(var(--neo-body-color-rgb), 0.14);
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

  .rte-ins-section {
    display: flex;
    flex-direction: column;
    gap: 0.625rem;
  }
  .rte-ins-title {
    font-size: 0.8125rem;
    font-weight: 600;
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
    background: transparent;
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

  /* Context / dropdown menu */
  .rte-context {
    --rte-line: rgba(var(--neo-body-color-rgb), 0.14);
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
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 100%;
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
    align-items: stretch;
    flex: 1;
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
  .rte-shell .rte-scroll {
    transition: background-color 0.2s ease;
  }
  .rte-shell.is-device-tablet .rte-scroll,
  .rte-shell.is-device-mobile .rte-scroll {
    background: var(--neo-tertiary-bg);
  }
  .rte-shell.is-device-tablet .rte-doc,
  .rte-shell.is-device-mobile .rte-doc {
    margin-block: 2rem;
    padding: 3rem 1.5rem 6rem;
    background: var(--neo-body-bg);
    box-shadow: 0 0 0 1px var(--rte-line);
  }
  .rte-shell.is-device-tablet .rte-doc {
    width: min(100%, 48.75rem);
  }
  .rte-shell.is-device-mobile .rte-doc {
    width: min(100%, 22.5rem);
    padding-inline: 1rem;
  }
  .rte-shell .rte-doc {
    transition: width 0.2s ease;
    width: min(100%, 44rem);
    margin: 0 auto;
    padding: 1.5rem 1rem 8rem;
  }
  /* Language tabs stay pinned 1rem below the top bar while the document scrolls. */
  .rte-shell .rte-doc :global(.tr-bar) {
    position: sticky;
    top: calc(var(--rte-nav-h) + var(--rte-bar-h) + 1rem);
    z-index: 4;
    background: var(--neo-body-bg);
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
  :global(.rte-link-target) {
    border-radius: 0.125rem;
    background: rgba(var(--neo-primary-rgb, 13, 110, 253), 0.22);
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
    .rte-shell .rte-doc {
      width: 100%;
      padding: 2rem 1rem 6rem;
    }
    .rte-shell .rte-body :global(.rte-content) {
      padding-right: 0;
      font-size: 1.125rem;
    }
    .rte-doc :global(.gb-title) {
      font-size: 1.875rem;
    }
  }
</style>
