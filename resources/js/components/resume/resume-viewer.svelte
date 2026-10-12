<script lang="ts">
  import { onMount, tick } from 'svelte';
  import ArrowLeft from '@lucide/svelte/icons/arrow-left';
  import Copy from '@lucide/svelte/icons/copy';
  import ImageIcon from '@lucide/svelte/icons/image';
  import SquareDashedMousePointer from '@lucide/svelte/icons/square-dashed-mouse-pointer';
  import TextCursor from '@lucide/svelte/icons/text-cursor';
  import Download from '@lucide/svelte/icons/download';
  import List from '@lucide/svelte/icons/list';
  import ChevronLeft from '@lucide/svelte/icons/chevron-left';
  import ChevronRight from '@lucide/svelte/icons/chevron-right';
  import Minus from '@lucide/svelte/icons/minus';
  import Plus from '@lucide/svelte/icons/plus';
  import Search from '@lucide/svelte/icons/search';
  import { perfectScrollbar } from '@/lib/perfect-scrollbar';
  import { createOcrCache, OCR_POOL_SIZE, terminateOcrWorker } from '@/lib/ocr';
  import { findPhraseMatches, textRunsToWords } from '@/lib/ocr/phrase';
  import type { PageWord } from '@/lib/ocr/phrase';
  import type { OcrLine, Rect } from '@/lib/ocr';
  import type { PDFDocumentProxy, PDFPageProxy } from 'pdfjs-dist';

  type Tab = 'outline' | 'search' | 'pages';
  type OutlineItem = { title: string; page: number; depth: number };
  type Hit = { page: number; rects: Rect[]; snippet: string; source: 'text' | 'ocr' };
  type TextRun = { str: string; transform: number[]; width: number; height: number };
  type Tool = 'text' | 'area';
  type Box = { page: number; left: number; top: number; width: number; height: number };
  type PageLink = { rect: Rect; url?: string; page?: number };

  let {
    url,
    title,
    subtitle = '',
    downloadName = 'CV.pdf',
    onclose,
  }: {
    url: string;
    title: string;
    subtitle?: string;
    downloadName?: string;
    onclose: () => void;
  } = $props();

  const ZOOMS = [0.5, 0.75, 1, 1.25, 1.3, 1.5, 2, 3];
  const GAP = 16;

  let status = $state<'loading' | 'ready' | 'error'>('loading');
  let tab = $state<Tab>('outline');
  let zoom = $state(1.3);
  let current = $state(1);
  let query = $state('');
  let copied = $state(false);
  let outline = $state<OutlineItem[]>([]);
  let hits = $state<Hit[]>([]);
  let searched = $state(false);
  let searching = $state(false);
  let tool = $state<Tool>('text');
  let drag = $state<Box | null>(null);
  let selection = $state<(Box & { text: string; busy: boolean }) | null>(null);
  let note = $state('');
  let sidebar = $state(typeof window !== 'undefined' && window.innerWidth >= 992);
  let mobileSearch = $state(false);
  let activeHit = $state(-1);
  let links = $state.raw<Record<number, PageLink[]>>({});
  let searchToken = 0;
  let searchTimer: ReturnType<typeof setTimeout> | undefined;
  const ocrCache = createOcrCache();
  const textCache = new Map<number, PageWord[]>();
  const textRendered = new Set<number>();
  let pdfjsModule: typeof import('pdfjs-dist') | null = null;
  let dragOrigin: { x: number; y: number } | null = null;
  let selectionId = 0;
  let pages = $state.raw<PDFPageProxy[]>([]);
  let scroller = $state<HTMLElement | null>(null);

  let doc: PDFDocumentProxy | null = null;
  const rendered = new Map<number, number>();
  const canvases = new Map<number, HTMLCanvasElement>();
  const thumbs = new Map<number, HTMLCanvasElement>();
  let observer: IntersectionObserver | null = null;

  const total = $derived(pages.length);

  const track = (node: HTMLCanvasElement, number: number) => {
    canvases.set(number, node);
    observer?.observe(node.parentElement as HTMLElement);

    return {
      destroy() {
        canvases.delete(number);
      },
    };
  };

  const thumb = (node: HTMLCanvasElement, number: number) => {
    thumbs.set(number, node);
    void drawThumb(number);

    return {
      destroy() {
        thumbs.delete(number);
      },
    };
  };

  const drawThumb = async (number: number): Promise<void> => {
    const canvas = thumbs.get(number);
    const page = pages[number - 1];

    if (!canvas || !page) {
      return;
    }

    const viewport = page.getViewport({ scale: 0.22 });
    canvas.width = viewport.width;
    canvas.height = viewport.height;
    await page.render({ canvas, viewport }).promise.catch(() => {});
  };

  /** Renders pdf.js's invisible text layer once at scale 1; CSS scales it with the sheet so selection survives zooming. */
  const renderText = async (number: number): Promise<void> => {
    const host = canvases.get(number)?.parentElement?.querySelector<HTMLElement>('.rv-text');
    const page = pages[number - 1];

    if (!pdfjsModule || !host || !page || textRendered.has(number)) {
      return;
    }

    textRendered.add(number);

    await new pdfjsModule.TextLayer({
      textContentSource: page.streamTextContent(),
      container: host,
      viewport: page.getViewport({ scale: 1 }),
    })
      .render()
      .catch(() => textRendered.delete(number));
  };

  const renderPage = async (number: number): Promise<void> => {
    void renderText(number);

    const canvas = canvases.get(number);
    const page = pages[number - 1];

    if (!canvas || !page || rendered.get(number) === zoom) {
      return;
    }

    const target = zoom;
    rendered.set(number, target);
    const ratio = window.devicePixelRatio || 1;
    const viewport = page.getViewport({ scale: target * ratio });
    const buffer = document.createElement('canvas');
    buffer.width = viewport.width;
    buffer.height = viewport.height;
    await page.render({ canvas: buffer, viewport }).promise.catch(() => {});
    canvas.width = buffer.width;
    canvas.height = buffer.height;
    canvas.getContext('2d')?.drawImage(buffer, 0, 0);
  };

  const renderVisible = (): void => {
    if (!scroller) {
      return;
    }

    const box = scroller.getBoundingClientRect();

    for (const [number, canvas] of canvases) {
      const rect = canvas.parentElement!.getBoundingClientRect();

      if (rect.bottom > box.top - box.height && rect.top < box.bottom + box.height) {
        void renderPage(number);
      }
    }
  };

  const updateCurrent = (): void => {
    if (!scroller) {
      return;
    }

    const middle = scroller.getBoundingClientRect().top + scroller.clientHeight / 3;
    let found = 1;

    for (const [number, canvas] of canvases) {
      if (canvas.parentElement!.getBoundingClientRect().top <= middle) {
        found = Math.max(found, number);
      }
    }

    current = found;
    renderVisible();
  };

  const closeOnNarrow = (): void => {
    if (window.innerWidth < 992) {
      sidebar = false;
    }
  };

  const goTo = (number: number): void => {
    closeOnNarrow();
    const target = Math.min(Math.max(number, 1), total);
    canvases.get(target)?.parentElement?.scrollIntoView({ block: 'start', behavior: 'smooth' });
  };

  let zoomTimer: ReturnType<typeof setTimeout> | undefined;

  const setZoom = (value: number): void => {
    const next = Math.round(Math.min(Math.max(value, 0.5), 3) * 100) / 100;

    if (next === zoom || !scroller) {
      return;
    }

    const ratio = next / zoom;
    zoom = next;
    scroller.scrollTop *= ratio;
    scroller.scrollLeft *= ratio;
    clearTimeout(zoomTimer);
    zoomTimer = setTimeout(renderVisible, 220);
  };

  const stepZoom = (direction: 1 | -1): void => {
    const next = direction > 0 ? ZOOMS.find((z) => z > zoom + 0.001) : [...ZOOMS].reverse().find((z) => z < zoom - 0.001);
    setZoom(next ?? zoom);
  };

  const wheelZoom = (node: HTMLElement) => {
    const handler = (event: WheelEvent): void => {
      if (!event.ctrlKey) {
        return;
      }

      event.preventDefault();
      setZoom(zoom * Math.exp(-event.deltaY * 0.01));
    };

    node.addEventListener('wheel', handler, { passive: false });

    return { destroy: () => node.removeEventListener('wheel', handler) };
  };

  const ocrWords = (lines: OcrLine[]): PageWord[] =>
    lines.flatMap((line, index) => line.map((word) => ({ text: word.text, rect: word.rect, line: index })));

  const scanPage = async (number: number, token: number): Promise<OcrLine[] | null> => {
    for (let ahead = 1; ahead < OCR_POOL_SIZE; ahead++) {
      const next = pages[number + ahead - 1];

      if (next) {
        void ocrCache.get(number + ahead, next).catch(() => undefined);
      }
    }

    const lines = await ocrCache.get(number, pages[number - 1]).catch(() => [] as OcrLine[]);

    return token === searchToken ? lines : null;
  };

  const runSearch = async (): Promise<void> => {
    const term = query.trim();
    const token = ++searchToken;
    searched = term !== '';
    activeHit = -1;

    if (!term || !doc) {
      hits = [];
      searching = false;

      return;
    }

    tab = 'search';
    searching = true;
    const found: Hit[] = [];
    const scannedPages = new Set<number>();

    const collect = (number: number, words: PageWord[], source: Hit['source']): void => {
      for (const match of findPhraseMatches(words, term, source === 'ocr')) {
        found.push({ page: number, rects: match.rects, snippet: match.snippet, source });
      }
    };

    for (let number = 1; number <= pages.length; number++) {
      let words = textCache.get(number);

      if (!words) {
        const content = await pages[number - 1].getTextContent();
        words = textRunsToWords((content.items as unknown[]).filter((item): item is TextRun => typeof (item as TextRun).str === 'string'));
        textCache.set(number, words);
      }

      if (token !== searchToken) {
        return;
      }

      if (words.reduce((total, word) => total + word.text.length, 0) >= 30) {
        collect(number, words, 'text');
        continue;
      }

      const lines = await scanPage(number, token);

      if (lines === null) {
        return;
      }

      scannedPages.add(number);
      collect(number, ocrWords(lines), 'ocr');
    }

    /* Text can still hide in images or outlined glyphs: when the text layer finds nothing, read every page by OCR. */
    if (found.length === 0) {
      for (let number = 1; number <= pages.length; number++) {
        if (scannedPages.has(number)) {
          continue;
        }

        const lines = await scanPage(number, token);

        if (lines === null) {
          return;
        }

        collect(number, ocrWords(lines), 'ocr');
      }
    }

    hits = found;
    searching = false;
    focusHit(found.length > 0 ? 0 : -1);
  };

  /** Centers the rendered highlight itself, so the target comes from real layout rather than recomputed geometry. */
  const scrollToHit = async (index: number): Promise<void> => {
    await tick();

    const marker = scroller?.querySelector<HTMLElement>(`[data-hit="${index}"]`);

    if (!scroller || !marker) {
      return;
    }

    const box = scroller.getBoundingClientRect();
    const rect = marker.getBoundingClientRect();

    scroller.scrollTo({
      top: rect.top - box.top + scroller.scrollTop + rect.height / 2 - box.height / 2,
      left: rect.left - box.left + scroller.scrollLeft + rect.width / 2 - box.width / 2,
      behavior: 'smooth',
    });
  };

  let hitTimer: ReturnType<typeof setTimeout> | undefined;

  const focusHit = (index: number): void => {
    activeHit = index;
    closeOnNarrow();
    clearTimeout(hitTimer);

    if (index >= 0) {
      void scrollToHit(index);
      hitTimer = setTimeout(() => {
        if (activeHit === index) {
          void scrollToHit(index);
        }
      }, 300);
    }
  };

  const stepHit = (direction: 1 | -1): void => {
    if (hits.length > 0) {
      focusHit((activeHit + direction + hits.length) % hits.length);
    }
  };

  /** Maps a PDF-space rectangle (origin bottom-left) onto the rendered sheet in CSS pixels. */
  const boxStyle = (page: number, rect: Rect): string => {
    const [minX, , , maxY] = pages[page - 1].view;
    const left = Math.min(rect[0], rect[2]);
    const right = Math.max(rect[0], rect[2]);
    const bottom = Math.min(rect[1], rect[3]);
    const top = Math.max(rect[1], rect[3]);

    return `left:${(left - minX) * zoom}px;top:${(maxY - top) * zoom}px;width:${(right - left) * zoom}px;height:${(top - bottom) * zoom}px`;
  };

  const pageWords = async (number: number): Promise<PageWord[]> => {
    let words = textCache.get(number);

    if (!words) {
      const content = await pages[number - 1].getTextContent();
      words = textRunsToWords((content.items as unknown[]).filter((item): item is TextRun => typeof (item as TextRun).str === 'string'));
      textCache.set(number, words);
    }

    if (words.reduce((total, word) => total + word.text.length, 0) >= 30) {
      return words;
    }

    return ocrWords(await ocrCache.get(number, pages[number - 1]).catch(() => [] as OcrLine[]));
  };

  const setTool = (next: Tool): void => {
    tool = next;
    selection = null;
    drag = null;
    note = '';
    window.getSelection()?.removeAllRanges();
  };

  const clamp = (value: number, min: number, max: number): number => Math.min(Math.max(value, min), max);

  const sheetPoint = (event: PointerEvent, sheet: HTMLElement): { x: number; y: number } => {
    const rect = sheet.getBoundingClientRect();

    return { x: clamp(event.clientX - rect.left, 0, rect.width), y: clamp(event.clientY - rect.top, 0, rect.height) };
  };

  const startDrag = (event: PointerEvent, number: number): void => {
    if (tool !== 'area' || event.button !== 0) {
      return;
    }

    const sheet = event.currentTarget as HTMLElement;
    sheet.setPointerCapture(event.pointerId);
    dragOrigin = sheetPoint(event, sheet);
    selection = null;
    note = '';
    drag = { page: number, left: dragOrigin.x, top: dragOrigin.y, width: 0, height: 0 };
  };

  const moveDrag = (event: PointerEvent, number: number): void => {
    if (!dragOrigin) {
      return;
    }

    const point = sheetPoint(event, event.currentTarget as HTMLElement);
    drag = {
      page: number,
      left: Math.min(dragOrigin.x, point.x),
      top: Math.min(dragOrigin.y, point.y),
      width: Math.abs(point.x - dragOrigin.x),
      height: Math.abs(point.y - dragOrigin.y),
    };
  };

  const endDrag = async (number: number): Promise<void> => {
    const box = drag;
    dragOrigin = null;
    drag = null;

    if (!box || box.width < 6 || box.height < 6) {
      return;
    }

    const id = ++selectionId;
    selection = { ...box, text: '', busy: true };

    const [minX, , , maxY] = pages[number - 1].view;
    const left = box.left / zoom + minX;
    const right = (box.left + box.width) / zoom + minX;
    const top = maxY - box.top / zoom;
    const bottom = maxY - (box.top + box.height) / zoom;
    const picked = (await pageWords(number)).filter((word) => {
      const x = (word.rect[0] + word.rect[2]) / 2;
      const y = (word.rect[1] + word.rect[3]) / 2;

      return x >= left && x <= right && y >= bottom && y <= top;
    });
    let text = '';
    let line = -1;

    for (const word of picked) {
      text += (text === '' ? '' : word.line !== line ? '\n' : ' ') + word.text;
      line = word.line;
    }

    if (id === selectionId && selection) {
      selection = { ...selection, text, busy: false };
    }
  };

  const flash = (message: string): void => {
    note = message;
    setTimeout(() => (note = ''), 2000);
  };

  const copySelectionText = async (): Promise<void> => {
    if (!selection?.text) {
      flash('Tidak ada teks di area ini');

      return;
    }

    try {
      await navigator.clipboard.writeText(selection.text);
      flash('Teks tersalin');
    } catch {
      flash('Gagal menyalin');
    }
  };

  const copySelectionImage = async (): Promise<void> => {
    const box = selection;
    const canvas = box ? canvases.get(box.page) : undefined;

    if (!box || !canvas) {
      return;
    }

    const factor = canvas.width / (canvas.parentElement as HTMLElement).clientWidth;
    const crop = document.createElement('canvas');
    crop.width = Math.max(1, Math.round(box.width * factor));
    crop.height = Math.max(1, Math.round(box.height * factor));
    crop.getContext('2d')?.drawImage(canvas, box.left * factor, box.top * factor, box.width * factor, box.height * factor, 0, 0, crop.width, crop.height);

    const blob = await new Promise<Blob | null>((resolve) => crop.toBlob(resolve, 'image/png'));

    if (!blob) {
      return;
    }

    try {
      await navigator.clipboard.write([new ClipboardItem({ 'image/png': blob })]);
      flash('Gambar tersalin');
    } catch {
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = 'cuplikan-cv.png';
      link.click();
      URL.revokeObjectURL(link.href);
      flash('Gambar diunduh');
    }
  };

  const onKeydown = (event: KeyboardEvent): void => {
    if (event.key === 'Escape') {
      selection = null;
      drag = null;
      dragOrigin = null;
    }
  };

  const bubbleStyle = (box: Box): string => {
    const height = (pages[box.page - 1].view[3] - pages[box.page - 1].view[1]) * zoom;
    const below = box.top + box.height + 52 <= height;

    return `left:${box.left}px;top:${below ? box.top + box.height + 8 : Math.max(0, box.top - 46)}px`;
  };

  const loadLinks = async (pdf: PDFDocumentProxy, loaded: PDFPageProxy[]): Promise<void> => {
    const collected: Record<number, PageLink[]> = {};

    for (const [index, page] of loaded.entries()) {
      const notes = (await page.getAnnotations()) as { subtype?: string; rect?: Rect; url?: string; dest?: unknown }[];
      const list: PageLink[] = [];

      for (const note of notes) {
        if (note.subtype !== 'Link' || !note.rect) {
          continue;
        }

        if (note.url) {
          list.push({ rect: note.rect, url: note.url });
        } else if (note.dest) {
          try {
            const dest = typeof note.dest === 'string' ? await pdf.getDestination(note.dest) : (note.dest as unknown[]);
            list.push({ rect: note.rect, page: dest ? (await pdf.getPageIndex(dest[0] as never)) + 1 : 1 });
          } catch {
            continue;
          }
        }
      }

      collected[index + 1] = list;
    }

    links = collected;
  };

  $effect(() => {
    const term = query;

    clearTimeout(searchTimer);

    if (!doc && pages.length === 0) {
      return;
    }

    searchTimer = setTimeout(() => void runSearch(), term.trim() === '' ? 0 : 300);

    return () => clearTimeout(searchTimer);
  });

  const copyLink = async (): Promise<void> => {
    try {
      await navigator.clipboard.writeText(window.location.href);
      copied = true;
      setTimeout(() => (copied = false), 2000);
    } catch {
      copied = false;
    }
  };

  const loadOutline = async (pdf: PDFDocumentProxy): Promise<void> => {
    const items: OutlineItem[] = [];
    const walk = async (nodes: Awaited<ReturnType<PDFDocumentProxy['getOutline']>>, depth: number): Promise<void> => {
      for (const node of nodes ?? []) {
        try {
          const dest = typeof node.dest === 'string' ? await pdf.getDestination(node.dest) : node.dest;
          const page = dest ? (await pdf.getPageIndex(dest[0])) + 1 : 1;
          items.push({ title: node.title, page, depth });
        } catch {
          items.push({ title: node.title, page: 1, depth });
        }
        await walk(node.items, depth + 1);
      }
    };

    await walk(await pdf.getOutline(), 0);
    outline = items;
  };

  onMount(() => {
    let cancelled = false;

    observer = new IntersectionObserver(() => renderVisible(), { rootMargin: '100% 0px' });

    (async () => {
      const [pdfjs, worker] = await Promise.all([
        import('pdfjs-dist'),
        import('pdfjs-dist/build/pdf.worker.min.mjs?url'),
      ]);
      pdfjs.GlobalWorkerOptions.workerSrc = worker.default;
      pdfjsModule = pdfjs;

      const response = await fetch(url, { credentials: 'same-origin' });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      doc = await pdfjs.getDocument({ data: new Uint8Array(await response.arrayBuffer()) }).promise;
      const loaded: PDFPageProxy[] = [];

      for (let number = 1; number <= doc.numPages; number++) {
        loaded.push(await doc.getPage(number));
      }

      if (cancelled) {
        return;
      }

      pages = loaded;
      status = 'ready';
      void loadOutline(doc);
      void loadLinks(doc, loaded);
      await tick();

      if (scroller && scroller.clientWidth < 768) {
        const width = loaded[0].view[2] - loaded[0].view[0];
        zoom = Math.round(Math.min(1.3, (scroller.clientWidth - 24) / width) * 100) / 100;
        await tick();
      }

      renderVisible();
    })().catch(() => {
      status = 'error';
    });

    return () => {
      cancelled = true;
      observer?.disconnect();
      terminateOcrWorker();
      void doc?.destroy();
    };
  });
</script>

<svelte:window onkeydown={onKeydown} />

<div class="rv">
  <header class="rv-bar">
    <button type="button" class="rv-back" onclick={onclose} aria-label="Kembali">
      <ArrowLeft size={18} /><span class="rv-label">Kembali</span>
    </button>
    <button type="button" class="rv-icon rv-menu" onclick={() => (sidebar = !sidebar)} aria-label="Tampilkan atau sembunyikan panel" aria-expanded={sidebar}><List size={20} /></button>

    <div class="rv-title">
      <strong>{title}</strong>
      {#if subtitle}<small>{subtitle}</small>{/if}
    </div>

    <button type="button" class="rv-icon rv-search-toggle" onclick={() => (mobileSearch = !mobileSearch)} aria-label="Cari dalam dokumen"><Search size={18} /></button>

    <form class="rv-search" class:open={mobileSearch} onsubmit={(event) => { event.preventDefault(); stepHit(1); }}>
      <Search size={14} />
      <input type="search" placeholder="Cari dalam dokumen" bind:value={query} aria-label="Cari dalam dokumen" onkeydown={(event) => { if (event.key === 'Enter' && event.shiftKey) { event.preventDefault(); stepHit(-1); } }} />
      {#if searched}<span class="rv-count">{searching ? '…' : hits.length === 0 ? '0' : `${activeHit + 1}/${hits.length}`}</span>{/if}
    </form>

    <div class="rv-controls">
      <div class="rv-group">
        <button type="button" class="rv-icon" class:on={tool === 'text'} aria-pressed={tool === 'text'} onclick={() => setTool('text')} aria-label="Pilih teks" title="Pilih teks"><TextCursor size={16} /></button>
        <button type="button" class="rv-icon" class:on={tool === 'area'} aria-pressed={tool === 'area'} onclick={() => setTool('area')} aria-label="Pilih area" title="Pilih area (seret kotak)"><SquareDashedMousePointer size={16} /></button>
      </div>

      <div class="rv-group">
        <button type="button" class="rv-icon" onclick={() => goTo(current - 1)} disabled={current <= 1} aria-label="Halaman sebelumnya"><ChevronLeft size={18} /></button>
        <span class="rv-page">Hal. {current} / {total || '-'}</span>
        <button type="button" class="rv-icon" onclick={() => goTo(current + 1)} disabled={current >= total} aria-label="Halaman berikutnya"><ChevronRight size={18} /></button>
      </div>

      <div class="rv-group">
        <button type="button" class="rv-icon" onclick={() => stepZoom(-1)} aria-label="Perkecil"><Minus size={14} /></button>
        <span class="rv-page">{Math.round(zoom * 100)}%</span>
        <button type="button" class="rv-icon" onclick={() => stepZoom(1)} aria-label="Perbesar"><Plus size={14} /></button>
      </div>
    </div>

    <a class="rv-download" href={url} download={downloadName} aria-label="Unduh PDF"><Download size={16} /><span class="rv-label">Unduh PDF</span></a>
    <button type="button" class="rv-copy" onclick={copyLink}>{copied ? 'Tersalin!' : 'Salin tautan'}</button>
  </header>

  <button type="button" class="rv-backdrop" class:open={sidebar} onclick={() => (sidebar = false)} aria-label="Tutup panel" tabindex={sidebar ? 0 : -1}></button>

  <div class="rv-body">
    <aside class="rv-side" class:open={sidebar} aria-hidden={!sidebar}>
      <nav class="rv-tabs" role="tablist">
        {#each [['outline', 'Struktur'], ['search', 'Pencarian'], ['pages', 'Halaman']] as [key, label] (key)}
          <button type="button" role="tab" aria-selected={tab === key} class:active={tab === key} onclick={() => (tab = key as Tab)}>{label}</button>
        {/each}
      </nav>

      <div class="rv-panel" use:perfectScrollbar>
        {#if tab === 'outline'}
          {#each outline as item, index (index)}
            <button type="button" class="rv-item" style:padding-left="{0.75 + item.depth}rem" onclick={() => goTo(item.page)}>{item.title}</button>
          {:else}
            <p class="rv-muted">Daftar isi tidak tersedia pada berkas ini.</p>
          {/each}
        {:else if tab === 'search'}
          {#each hits as hit, index (index)}
            <button type="button" class="rv-item" class:current={activeHit === index} onclick={() => focusHit(index)}>
              <span class="rv-badge">Hal. {hit.page}</span> …{hit.snippet}…
            </button>
          {:else}
            <p class="rv-muted">{searching ? 'Mencari…' : searched ? 'Tidak ada hasil yang cocok.' : 'Mulai mengetik untuk mencari.'}</p>
          {/each}
        {:else}
          <div class="rv-thumbs">
            {#each pages as _, index (index)}
              <button type="button" class="rv-thumb" class:active={current === index + 1} onclick={() => goTo(index + 1)}>
                <canvas use:thumb={index + 1}></canvas>
                <span>{index + 1}</span>
              </button>
            {/each}
          </div>
        {/if}
      </div>
    </aside>

    <main class="rv-main" bind:this={scroller} onscroll={updateCurrent} use:wheelZoom>
      {#if status === 'loading'}
        <p class="rv-state">Memuat dokumen…</p>
      {:else if status === 'error'}
        <p class="rv-state">Dokumen gagal dimuat. <a href={url}>Buka langsung</a></p>
      {:else}
        {#each pages as page, index (index)}
          {@const size = page.getViewport({ scale: zoom })}
          <div
            class="rv-sheet"
            class:area={tool === 'area'}
            role="presentation"
            style:width="{size.width}px"
            style:height="{size.height}px"
            style:margin-bottom="{GAP}px"
            onpointerdown={(event) => startDrag(event, index + 1)}
            onpointermove={(event) => moveDrag(event, index + 1)}
            onpointerup={() => void endDrag(index + 1)}
            onpointercancel={() => void endDrag(index + 1)}
          >
            <canvas use:track={index + 1}></canvas>
            <div class="rv-text" style:transform="scale({zoom})"></div>
            {#each hits as hit, hitIndex (hitIndex)}
              {#if hit.page === index + 1}
                {#each hit.rects as rect, rectIndex (rectIndex)}
                  <span class="rv-hit" data-hit={hitIndex} class:first={rectIndex === 0} class:active={activeHit === hitIndex} style={boxStyle(index + 1, rect)}></span>
                {/each}
              {/if}
            {/each}
            {#each links[index + 1] ?? [] as link, linkIndex (linkIndex)}
              {#if link.url}
                <a class="rv-link" href={link.url} target="_blank" rel="noopener noreferrer" aria-label={link.url} style={boxStyle(index + 1, link.rect)}></a>
              {:else}
                <button type="button" class="rv-link" aria-label="Ke halaman {link.page}" onclick={() => goTo(link.page ?? 1)} style={boxStyle(index + 1, link.rect)}></button>
              {/if}
            {/each}
            {#if drag?.page === index + 1}
              <div class="rv-marquee" style="left:{drag.left}px;top:{drag.top}px;width:{drag.width}px;height:{drag.height}px"></div>
            {/if}
            {#if selection?.page === index + 1}
              <div class="rv-marquee" style="left:{selection.left}px;top:{selection.top}px;width:{selection.width}px;height:{selection.height}px"></div>
              <div class="rv-sel-actions" style={bubbleStyle(selection)} role="toolbar" tabindex="-1" onpointerdown={(event) => event.stopPropagation()}>
                {#if note}
                  <span class="rv-note">{note}</span>
                {:else if selection.busy}
                  <span class="rv-note">Membaca area…</span>
                {:else}
                  <button type="button" onclick={copySelectionText}><Copy size={14} /> Salin teks</button>
                  <button type="button" onclick={copySelectionImage}><ImageIcon size={14} /> Salin gambar</button>
                {/if}
              </div>
            {/if}
          </div>
        {/each}
      {/if}
    </main>
  </div>
</div>

<style>
  .rv { display: flex; flex-direction: column; height: 100%; color: var(--neo-body-color); font-size: 0.875rem; background: var(--neo-tertiary-bg); }
  .rv-bar { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem 1rem; padding: 0.75rem 1rem; color: #fff; background: var(--neo-primary); }
  .rv-back { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.5rem 0.875rem; font-weight: 600; color: var(--neo-primary); cursor: pointer; background: #fff; border: 0; border-radius: 0.5rem; }
  .rv-title { display: flex; flex: 1 1 14rem; flex-direction: column; min-width: 0; }
  .rv-title strong { font-size: 1rem; }
  .rv-title small { overflow: hidden; color: #cbd5e1; font-size: 0.7rem; text-overflow: ellipsis; white-space: nowrap; }
  .rv-search { display: flex; flex: 0 1 22rem; align-items: center; gap: 0.5rem; padding: 0 0.875rem; background: rgba(255, 255, 255, 0.12); border-radius: 999px; }
  .rv-search input { flex: 1; min-width: 0; padding: 0.5rem 0; color: #fff; background: transparent; border: 0; outline: 0; }
  .rv-search input::placeholder { color: #cbd5e1; }
  .rv-group { display: flex; align-items: center; gap: 0.25rem; }
  .rv-page { min-width: 4.5rem; font-size: 0.8rem; font-weight: 600; text-align: center; }
  .rv-icon { display: inline-grid; width: 1.75rem; height: 1.75rem; color: #fff; cursor: pointer; background: transparent; border: 0; border-radius: 0.375rem; place-items: center; }
  .rv-icon:hover:not(:disabled) { background: rgba(255, 255, 255, 0.15); }
  .rv-icon:disabled { opacity: 0.4; cursor: default; }
  .rv-download { padding: 0.5rem 0.875rem; font-weight: 700; color: #fff; text-decoration: none; background: var(--neo-primary-text-emphasis); border-radius: 0.5rem; }
  .rv-copy { padding: 0.5rem 0.875rem; font-weight: 600; color: #fff; cursor: pointer; background: transparent; border: 1px solid rgba(255, 255, 255, 0.6); border-radius: 0.5rem; }
  .rv-body { display: flex; flex: 1; min-height: 0; }
  .rv-side { display: flex; flex: 0 0 20rem; width: 20rem; overflow: hidden; transition: flex-basis 0.25s ease, width 0.25s ease, opacity 0.2s ease, visibility 0.25s; flex-direction: column; min-height: 0; background: var(--neo-body-bg); border-right: 1px solid var(--neo-border-color); }
  .rv-tabs { display: flex; border-bottom: 1px solid var(--neo-border-color); }
  .rv-tabs button { flex: 1; padding: 0.75rem 0; font-weight: 600; color: var(--neo-secondary-color); cursor: pointer; background: transparent; border: 0; border-bottom: 2px solid transparent; }
  .rv-tabs button.active { color: var(--neo-primary); border-bottom-color: var(--neo-primary); }
  .rv-panel { position: relative; flex: 1; padding: 0.75rem 0; overflow-y: auto; }
  .rv-item { display: block; width: 100%; padding: 0.5rem 0.75rem; text-align: left; color: inherit; cursor: pointer; background: transparent; border: 0; }
  .rv-item:hover { background: var(--neo-primary-bg-subtle); }
  .rv-badge { display: inline-block; padding: 0 0.375rem; margin-right: 0.25rem; font-size: 0.7rem; font-weight: 700; color: var(--neo-primary-text-emphasis); background: var(--neo-primary-bg-subtle); border-radius: 0.25rem; }
  .rv-muted { padding: 0 1rem; font-size: 0.8rem; color: var(--neo-secondary-color); }
  .rv-thumbs { display: grid; gap: 0.75rem; padding: 0 1rem; justify-items: center; }
  .rv-thumb { display: flex; flex-direction: column; align-items: center; gap: 0.25rem; padding: 0.375rem; cursor: pointer; background: transparent; border: 2px solid transparent; border-radius: 0.375rem; }
  .rv-thumb.active { border-color: var(--neo-primary); }
  .rv-thumb canvas { box-shadow: 0 1px 4px rgba(0, 0, 0, 0.25); }
  .rv-sheet canvas { display: block; width: 100%; height: 100%; }
 .rv-hit { position: absolute; pointer-events: none; background: rgba(255, 193, 7, 0.45); border-radius: 2px; mix-blend-mode: multiply; transition: left 0.2s ease-out, top 0.2s ease-out, width 0.2s ease-out, height 0.2s ease-out; }
  .rv-hit.active { z-index: 1; background: rgba(255, 120, 0, 0.55); outline: 2px solid #ff7800; }
  .rv-hit.active.first::before { position: absolute; top: 50%; right: calc(100% + 6px); width: 0; height: 0; margin-top: -8px; content: ''; border-top: 8px solid transparent; border-bottom: 8px solid transparent; border-left: 12px solid #ff7800; animation: rv-point 0.9s ease-in-out infinite; }
  .rv-hit.active.first::after { position: absolute; inset: -6px; content: ''; border: 2px solid #ff7800; border-radius: 6px; animation: rv-ping 1.2s ease-out infinite; }
  @keyframes rv-point { 50% { transform: translateX(-6px); } }
  @keyframes rv-ping { from { opacity: 0.9; transform: scale(0.9); } to { opacity: 0; transform: scale(1.25); } }
  .rv-link { position: absolute; padding: 0; cursor: pointer; background: transparent; border: 0; border-radius: 2px; transition: left 0.2s ease-out, top 0.2s ease-out, width 0.2s ease-out, height 0.2s ease-out, background 0.15s; }
  .rv-link:hover { background: rgba(var(--neo-primary-rgb), 0.18); outline: 1px solid var(--neo-primary); }
  .rv-count { font-size: 0.75rem; font-weight: 600; white-space: nowrap; }
  .rv-item.current { background: var(--neo-primary-bg-subtle); }
  .rv-main { flex: 1; padding: 1rem; overflow: auto; }
  .rv-sheet { position: relative; margin-right: auto; margin-left: auto; background: #fff; transition: width 0.2s ease-out, height 0.2s ease-out; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.18); }
  .rv-text { position: absolute; top: 0; left: 0; overflow: clip; line-height: 1; text-align: initial; transform-origin: 0 0; --min-font-size: 1; --text-scale-factor: calc(var(--total-scale-factor) * var(--min-font-size)); --min-font-size-inv: calc(1 / var(--min-font-size)); transition: transform 0.2s ease-out; }
  .rv-text :global(:is(span, br)) { position: absolute; color: transparent; white-space: pre; cursor: text; transform-origin: 0 0; user-select: text; }
  .rv-text > :global(:not(.markedContent)), .rv-text :global(.markedContent span:not(.markedContent)) { --font-height: 0; --scale-x: 1; --rotate: 0deg; font-size: calc(var(--text-scale-factor) * var(--font-height)); transform: rotate(var(--rotate)) scaleX(var(--scale-x)) scale(var(--min-font-size-inv)); }
  .rv-text :global(.markedContent) { display: contents; }
  .rv-text :global(::selection) { color: transparent; background: rgba(var(--neo-primary-rgb), 0.35); }
  .rv-text :global(.endOfContent) { position: absolute; inset: 100% 0 0; display: block; cursor: default; user-select: none; }
  .rv-text:global(.selecting) :global(.endOfContent) { top: 0; }
  .rv-sheet.area { cursor: crosshair; user-select: none; touch-action: none; }
  .rv-sheet.area .rv-text { pointer-events: none; }
  .rv-marquee { position: absolute; z-index: 2; pointer-events: none; background: rgba(var(--neo-primary-rgb), 0.1); border: 1.5px dashed var(--neo-primary); }
  .rv-sel-actions { position: absolute; z-index: 3; display: flex; gap: 0.25rem; padding: 0.25rem; color: var(--neo-body-color); background: var(--neo-body-bg); border: 1px solid var(--neo-border-color); border-radius: 0.5rem; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2); cursor: default; }
  .rv-sel-actions button { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.625rem; font-size: 0.8rem; font-weight: 600; color: inherit; white-space: nowrap; cursor: pointer; background: transparent; border: 0; border-radius: 0.375rem; }
  .rv-sel-actions button:hover { background: var(--neo-primary-bg-subtle); }
  .rv-note { padding: 0.375rem 0.625rem; font-size: 0.8rem; font-weight: 600; white-space: nowrap; }
  .rv-icon.on { background: rgba(255, 255, 255, 0.28); }
  .rv-state { margin-top: 3rem; text-align: center; color: var(--neo-secondary-color); }

  @media (prefers-reduced-motion: reduce) {
    .rv-sheet, .rv-side, .rv-backdrop, .rv-text { transition: none !important; }
    .rv-hit.active.first::before, .rv-hit.active.first::after { animation: none; }
  }

  .rv-side:not(.open) { flex-basis: 0; width: 0; border-right-width: 0; opacity: 0; visibility: hidden; }
  .rv-search-toggle { display: none; }
  .rv-backdrop { display: none; }
  .rv-controls { display: contents; }
  .rv-download { display: inline-flex; align-items: center; gap: 0.375rem; }

  @media (max-width: 991.98px) {
    .rv-side, .rv-side:not(.open) { position: fixed; z-index: 30; top: 0; bottom: 0; left: 0; flex-basis: auto; width: min(20rem, 86vw); border-right-width: 1px; opacity: 1; visibility: visible; box-shadow: 0 0 40px rgba(0, 0, 0, 0.35); transition: transform 0.28s cubic-bezier(0.32, 0.72, 0, 1); transform: translateX(-105%); }
    .rv-side.open { transform: translateX(0); }
    .rv-backdrop { position: fixed; z-index: 29; inset: 0; display: block; background: rgba(0, 0, 0, 0.4); border: 0; opacity: 0; pointer-events: none; transition: opacity 0.25s ease; }
    .rv-backdrop.open { opacity: 1; pointer-events: auto; }
    .rv-search { flex-basis: 100%; order: 5; }
  }

  @media (max-width: 767.98px) {
    .rv-bar { flex-wrap: wrap; gap: 0.5rem; padding: 0.625rem 0.75rem; }
    .rv-label, .rv-title small, .rv-copy { display: none; }
    .rv-back, .rv-download { display: inline-grid; width: 2.25rem; height: 2.25rem; padding: 0; place-items: center; border-radius: 999px; }
    .rv-title { flex: 1 1 0; }
    .rv-title strong { overflow: hidden; font-size: 1rem; text-overflow: ellipsis; white-space: nowrap; }
    .rv-search-toggle { display: inline-grid; }
    .rv-search { display: none; }
    .rv-search.open { display: flex; }
    .rv-main { padding: 0.75rem 0.75rem 5.5rem; }
    .rv-controls { position: fixed; z-index: 20; bottom: 1rem; left: 50%; display: flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; color: var(--neo-body-color); background: var(--neo-body-bg); border: 1px solid var(--neo-border-color); border-radius: 999px; box-shadow: 0 4px 18px rgba(0, 0, 0, 0.2); transform: translateX(-50%); }
    .rv-controls .rv-icon { color: inherit; }
    .rv-controls .rv-icon.on { color: var(--neo-primary-text-emphasis); background: var(--neo-primary-bg-subtle); }
    .rv-controls .rv-icon:hover:not(:disabled) { background: var(--neo-primary-bg-subtle); }
    .rv-controls .rv-page { min-width: 3.5rem; font-size: 0.75rem; }
    .rv-controls .rv-group + .rv-group { padding-left: 0.375rem; border-left: 1px solid var(--neo-border-color); }
  }
</style>
