<script lang="ts">
  import { flushSync, onMount, tick } from 'svelte';
  import ArrowLeft from '@lucide/svelte/icons/arrow-left';
  import Check from '@lucide/svelte/icons/check';
  import LinkIcon from '@lucide/svelte/icons/link';
  import Download from '@lucide/svelte/icons/download';
  import List from '@lucide/svelte/icons/list';
  import ChevronLeft from '@lucide/svelte/icons/chevron-left';
  import ChevronRight from '@lucide/svelte/icons/chevron-right';
  import Minus from '@lucide/svelte/icons/minus';
  import Plus from '@lucide/svelte/icons/plus';
  import MoveHorizontal from '@lucide/svelte/icons/move-horizontal';
  import MoreVertical from '@lucide/svelte/icons/ellipsis-vertical';
  import Maximize from '@lucide/svelte/icons/maximize';
  import Search from '@lucide/svelte/icons/search';
  import Moon from '@lucide/svelte/icons/moon';
  import Sun from '@lucide/svelte/icons/sun';
  import { toggleDocumentTheme } from '@/lib/theme';
  import ResumeVerification from '@/components/resume/resume-verification.svelte';
  import { extractPdfSignatures } from '@/lib/pdf-signatures';
  import type { PdfSignature } from '@/lib/pdf-signatures';
  import { perfectScrollbar } from '@/lib/perfect-scrollbar';
  import { anchoredScroll, clampZoom, distanceBetween, fitPageZoom, fitWidthZoom, pinchZoom, stepZoom as nextZoom } from '@/lib/resume-zoom';
  import type { ZoomMode } from '@/lib/resume-zoom';
  import { createOcrCache, OCR_POOL_SIZE, terminateOcrWorker } from '@/lib/ocr';
  import { findPhraseMatches, textRunsToWords } from '@/lib/ocr/phrase';
  import type { PageWord } from '@/lib/ocr/phrase';
  import type { OcrLine, Rect } from '@/lib/ocr';
  import type { PDFDocumentLoadingTask, PDFDocumentProxy, PDFPageProxy } from 'pdfjs-dist';

  type Tab = 'outline' | 'search' | 'pages' | 'info' | 'verify';
  type OutlineItem = { title: string; page: number; depth: number };
  type Hit = { page: number; rects: Rect[]; snippet: string; source: 'text' | 'ocr' };
  type TextRun = { str: string; transform: number[]; width: number; height: number };
  type PageLink = { rect: Rect; url?: string; page?: number };

  let {
    url,
    title,
    subtitle = '',
    downloadName = 'CV.pdf',
    profiles = [],
    activeProfile = '',
    onprofile,
    info = [],
    onclose,
  }: {
    url: string;
    title: string;
    subtitle?: string;
    downloadName?: string;
    profiles?: { key: string; label: string; description?: string }[];
    activeProfile?: string;
    onprofile?: (key: string) => void;
    /** Rows for the "Informasi" tab, pushed in by the page as label/value pairs. */
    info?: { label: string; value: string; href?: string }[];
    onclose: () => void;
  } = $props();

  const GAP = 16;

  let status = $state<'loading' | 'ready' | 'error'>('loading');
  let fitted = $state(false);
  let tab = $state<Tab>('info');
  let zoom = $state(1);
  let zoomMode = $state<ZoomMode>('fit-width');
  const MODE_LABELS: Record<ZoomMode, string> = { 'fit-width': 'Sesuai lebar', 'fit-page': 'Sesuai halaman', custom: 'Kustom' };
  let current = $state(1);
  let query = $state('');
  let copied = $state(false);
  let outline = $state<OutlineItem[]>([]);
  let signatures = $state.raw<PdfSignature[] | null>(null);
  let hits = $state<Hit[]>([]);
  let searched = $state(false);
  let searching = $state(false);
  let sidebar = $state(typeof window !== 'undefined' && window.innerWidth >= 992);
  let searchInput = $state<HTMLInputElement | null>(null);
  let activeHit = $state(-1);
  let links = $state.raw<Record<number, PageLink[]>>({});
  let searchToken = 0;
  let searchTimer: ReturnType<typeof setTimeout> | undefined;
  const ocrCache = createOcrCache();
  const textCache = new Map<number, PageWord[]>();
  const textRendered = new Set<number>();
  let pdfjsModule: typeof import('pdfjs-dist') | null = null;
  let pages = $state.raw<PDFPageProxy[]>([]);
  let scroller = $state<HTMLElement | null>(null);

  let doc: PDFDocumentProxy | null = null;
  let loadingTask: PDFDocumentLoadingTask | null = null;
  const rendered = new Map<number, number>();
  const canvases = new Map<number, HTMLCanvasElement>();
  const thumbs = new Map<number, HTMLCanvasElement>();
  let observer: IntersectionObserver | null = null;

  const total = $derived(pages.length);
  const hasSignatures = $derived((signatures?.length ?? 0) > 0);
  const tabs = $derived<[Tab, string][]>([
    ['info', 'Informasi'],
    ['outline', 'Struktur'],
    ['search', 'Pencarian'],
    ['pages', 'Halaman'],
    ...(hasSignatures ? ([['verify', 'Verifikasi']] as [Tab, string][]) : []),
  ]);
  let isDark = $state(typeof document !== 'undefined' && document.documentElement.getAttribute('data-bs-theme') === 'dark');
  const infoRows = $derived([...info, ...(total > 0 ? [{ label: 'Jumlah halaman', value: String(total) }] : [])]);
  let tabStrip = $state<HTMLElement | null>(null);
  let tabsCanLeft = $state(false);
  let tabsCanRight = $state(false);

  const syncTabArrows = (): void => {
    if (!tabStrip) {
      return;
    }

    tabsCanLeft = tabStrip.scrollLeft > 1;
    tabsCanRight = tabStrip.scrollLeft + tabStrip.clientWidth < tabStrip.scrollWidth - 1;
  };

  let tabSlide = 0;

  /**
   * Slides the tab strip so the next tab hidden on that side lines up with the edge, one tab per click.
   */
  const scrollTabs = (direction: -1 | 1): void => {
    if (!tabStrip) {
      return;
    }

    const strip = tabStrip;
    const buttons = Array.from(strip.querySelectorAll<HTMLElement>('button'));
    const left = strip.scrollLeft;
    const right = left + strip.clientWidth;
    const target =
      direction === 1
        ? buttons.find((button) => button.offsetLeft + button.offsetWidth > right + 1)
        : [...buttons].reverse().find((button) => button.offsetLeft < left - 1);

    if (!target) {
      return;
    }

    const max = strip.scrollWidth - strip.clientWidth;
    const destination = Math.min(
      Math.max(direction === 1 ? target.offsetLeft + target.offsetWidth - strip.clientWidth : target.offsetLeft, 0),
      max,
    );
    const from = left;
    const started = performance.now();
    const token = ++tabSlide;
    const step = (now: number): void => {
      if (token !== tabSlide) {
        return;
      }

      const progress = Math.min((now - started) / 260, 1);
      const eased = 1 - (1 - progress) ** 3;

      strip.scrollLeft = from + (destination - from) * eased;
      syncTabArrows();

      if (progress < 1) {
        requestAnimationFrame(step);
      }
    };

    requestAnimationFrame(step);
  };

  /** Maps the wheel (and Shift + wheel, which some browsers report on deltaY) onto the strip's horizontal scroll. */
  const wheelTabs = (event: WheelEvent): void => {
    if (!tabStrip || tabStrip.scrollWidth <= tabStrip.clientWidth) {
      return;
    }

    const delta = Math.abs(event.deltaX) > Math.abs(event.deltaY) ? event.deltaX : event.deltaY;

    event.preventDefault();
    tabSlide++;
    tabStrip.scrollLeft += delta;
  };

  const watchTabScroll = (node: HTMLElement) => {
    const observer = new ResizeObserver(syncTabArrows);

    observer.observe(node);
    Array.from(node.children).forEach((child) => observer.observe(child));
    node.addEventListener('scroll', syncTabArrows, { passive: true });
    node.addEventListener('wheel', wheelTabs, { passive: false });
    syncTabArrows();

    return {
      destroy() {
        observer.disconnect();
        node.removeEventListener('scroll', syncTabArrows);
        node.removeEventListener('wheel', wheelTabs);
      },
    };
  };

  $effect(() => {
    if (tab === 'verify' && !hasSignatures) {
      tab = 'info';
    }
  });

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

  let resizeObserver: ResizeObserver | undefined;
  let zoomTimer: ReturnType<typeof setTimeout> | undefined;

  let zoomFrame = 0;
  let zoomTarget = 1;
  type ZoomTween = { base: number; k: number; point: { x: number; y: number }; sheets: { node: HTMLElement; origin: string }[] };
  let tween: ZoomTween | null = null;

  const paintTween = (): void => {
    for (const { node, origin } of tween?.sheets ?? []) {
      node.style.transformOrigin = origin;
      node.style.transform = tween && tween.k !== 1 ? `scale(${tween.k})` : '';
    }
  };

  /** Applies the zoom for real (one layout), keeping `point` fixed, and drops any in-flight transform. */
  const commitZoom = (final: number, point: { x: number; y: number }): void => {
    const view = scroller;
    const ratio = final / zoom;

    cancelAnimationFrame(zoomFrame);

    if (tween) {
      tween.k = 1;
      paintTween();
      tween = null;
    }

    if (!view) {
      return;
    }

    zoom = final;
    flushSync();
    view.scrollTop = anchoredScroll(view.scrollTop, point.y, ratio);
    view.scrollLeft = anchoredScroll(view.scrollLeft, point.x, ratio);
    clearTimeout(zoomTimer);
    zoomTimer = setTimeout(renderVisible, 220);
  };

  /**
   * Scales only the document sheets; the page itself is never zoomed. `anchor` is a point in the scroller's
   * viewport. The change eases over `duration` ms (0 applies it immediately). While it eases, the sheets are
   * only scaled with a GPU transform about the anchor, so no layout runs per frame; the real zoom is committed
   * once at the end. A new call mid-way retargets the same tween instead of restarting it.
   */
  const setZoom = (value: number, mode: ZoomMode = 'custom', anchor?: { x: number; y: number }, duration = 200): void => {
    const target = clampZoom(value);
    zoomMode = mode;
    zoomTarget = target;

    if (!scroller) {
      return;
    }

    const view = scroller;
    const point = tween?.point ?? anchor ?? { x: view.clientWidth / 2, y: view.clientHeight / 2 };

    if (duration <= 0 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      if (target !== zoom || tween) {
        commitZoom(target, point);
      }

      return;
    }

    if (!tween) {
      if (target === zoom) {
        return;
      }

      const box = view.getBoundingClientRect();

      tween = {
        base: zoom,
        k: 1,
        point,
        sheets: Array.from(view.querySelectorAll<HTMLElement>('.rv-sheet')).map((node) => {
          const rect = node.getBoundingClientRect();

          return { node, origin: `${point.x - (rect.left - box.left)}px ${point.y - (rect.top - box.top)}px` };
        }),
      };
    }

    cancelAnimationFrame(zoomFrame);

    const active = tween;
    const from = active.k;
    const to = target / active.base;
    const started = performance.now();
    const step = (now: number): void => {
      const progress = Math.min((now - started) / duration, 1);
      const eased = 1 - (1 - progress) ** 3;

      active.k = from + (to - from) * eased;
      paintTween();

      if (progress < 1) {
        zoomFrame = requestAnimationFrame(step);
      } else {
        commitZoom(target, active.point);
      }
    };

    zoomFrame = requestAnimationFrame(step);
  };

  const stepZoom = (direction: 1 | -1): void => setZoom(nextZoom(zoomTarget, direction));

  /** Re-applies a fit mode against the current viewer size (also on rotation or resize). */
  const applyFit = (mode: Exclude<ZoomMode, 'custom'>): void => {
    const first = pages[0];

    if (!scroller || !first) {
      return;
    }

    const width = first.view[2] - first.view[0];
    const height = first.view[3] - first.view[1];
    const padding = scroller.clientWidth < 768 ? 24 : 32;

    setZoom(
      mode === 'fit-width'
        ? fitWidthZoom(scroller.clientWidth, width, padding)
        : fitPageZoom(scroller.clientWidth, scroller.clientHeight, width, height, padding),
      mode,
    );
  };

  const wheelZoom = (node: HTMLElement) => {
    let frame = 0;
    let factor = 1;
    let anchor = { x: 0, y: 0 };

    // Wheel events fire faster than frames on high-rate mice; apply one zoom step per frame so slow machines never queue layouts.
    const handler = (event: WheelEvent): void => {
      if (!event.ctrlKey) {
        return;
      }

      event.preventDefault();
      const box = node.getBoundingClientRect();
      factor *= Math.exp(-event.deltaY * 0.01);
      anchor = { x: event.clientX - box.left, y: event.clientY - box.top };

      if (frame === 0) {
        frame = requestAnimationFrame(() => {
          frame = 0;
          setZoom(zoomTarget * factor, 'custom', anchor, 110);
          factor = 1;
        });
      }
    };

    node.addEventListener('wheel', handler, { passive: false });

    return {
      destroy: () => {
        cancelAnimationFrame(frame);
        node.removeEventListener('wheel', handler);
      },
    };
  };

  /** Two-finger pinch inside the viewer only; the browser's own page pinch is disabled here via touch-action. */
  const pinchZoomAction = (node: HTMLElement) => {
    let start: { distance: number; zoom: number } | null = null;

    const points = (event: TouchEvent): { x: number; y: number }[] =>
      Array.from(event.touches).map((touch) => ({ x: touch.clientX, y: touch.clientY }));

    const onStart = (event: TouchEvent): void => {
      if (event.touches.length === 2) {
        const [a, b] = points(event);
        start = { distance: distanceBetween(a, b), zoom: zoomTarget };
      }
    };

    const onMove = (event: TouchEvent): void => {
      if (!start || event.touches.length !== 2) {
        return;
      }

      const [a, b] = points(event);
      const box = node.getBoundingClientRect();
      setZoom(pinchZoom(start.zoom, start.distance, distanceBetween(a, b)), 'custom', {
        x: (a.x + b.x) / 2 - box.left,
        y: (a.y + b.y) / 2 - box.top,
      }, 90);
    };

    const onEnd = (event: TouchEvent): void => {
      if (event.touches.length < 2) {
        start = null;
      }
    };

    node.addEventListener('touchstart', onStart, { passive: true });
    node.addEventListener('touchmove', onMove, { passive: true });
    node.addEventListener('touchend', onEnd, { passive: true });
    node.addEventListener('touchcancel', onEnd, { passive: true });

    return {
      destroy: () => {
        node.removeEventListener('touchstart', onStart);
        node.removeEventListener('touchmove', onMove);
        node.removeEventListener('touchend', onEnd);
        node.removeEventListener('touchcancel', onEnd);
      },
    };
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

  const clamp = (value: number, min: number, max: number): number => Math.min(Math.max(value, min), max);

  const focusSearch = async (): Promise<void> => {
    sidebar = true;
    tab = 'search';
    await tick();
    searchInput?.focus();
    searchInput?.select();
  };

  const onKeydown = (event: KeyboardEvent): void => {
    if ((event.ctrlKey || event.metaKey) && !event.altKey && event.key.toLowerCase() === 'f') {
      event.preventDefault();
      void focusSearch();

      return;
    }

    if (event.key === 'Escape') {
    }
  };

  const loadLinks = async (pdf: PDFDocumentProxy, loaded: PDFPageProxy[]): Promise<void> => {
    const collected: Record<number, PageLink[]> = {};

    for (const [index, page] of loaded.entries()) {
      if (doc !== pdf) {
        return;
      }

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

    if (doc === pdf) {
      links = collected;
    }
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
        if (doc !== pdf) {
          return;
        }

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

    if (doc === pdf) {
      outline = items;
    }
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

      const bytes = new Uint8Array(await response.arrayBuffer());

      void extractPdfSignatures(bytes.slice())
        .then((found) => {
          if (!cancelled) {
            signatures = found;
          }
        })
        .catch(() => {
          if (!cancelled) {
            signatures = [];
          }
        });

      loadingTask = pdfjs.getDocument({ data: bytes });
      doc = await loadingTask.promise;
      const loaded: PDFPageProxy[] = [];

      for (let number = 1; number <= doc.numPages; number++) {
        if (cancelled) {
          return;
        }

        loaded.push(await doc.getPage(number));
      }

      if (cancelled) {
        return;
      }

      pages = loaded;
      status = 'ready';
      loadOutline(doc).catch(() => {});
      loadLinks(doc, loaded).catch(() => {});
      await tick();

      if (scroller) {
        zoomMode = scroller.clientWidth < 768 ? 'fit-width' : 'fit-page';
        applyFit(zoomMode);
        await tick();
      }

      fitted = true;
      resizeObserver = new ResizeObserver(() => {
        if (zoomMode !== 'custom') {
          applyFit(zoomMode);
        }
      });
      resizeObserver.observe(scroller!);

      renderVisible();
    })().catch(() => {
      status = 'error';
    });

    return () => {
      cancelled = true;
      observer?.disconnect();
      resizeObserver?.disconnect();
      clearTimeout(zoomTimer);
      cancelAnimationFrame(zoomFrame);
      terminateOcrWorker();
      doc = null;
      void loadingTask?.destroy().catch(() => {});
    };
  });
</script>

<svelte:window onkeydown={onKeydown} />

<div class="rv" class:rv-fitted={fitted} data-zoom-mode={zoomMode}>
  <header class="rv-bar">
    <button type="button" class="rv-back" onclick={onclose} aria-label="Kembali" data-tip="Kembali">
      <ArrowLeft size={18} /><span class="rv-label">Kembali</span>
    </button>
    <button type="button" class="rv-icon rv-menu" onclick={() => (sidebar = !sidebar)} aria-label="Tampilkan atau sembunyikan panel" aria-expanded={sidebar} data-tip="Panel samping"><List size={20} /></button>

    <div class="rv-title">
      <strong>{title}</strong>
      {#if subtitle}<small>{subtitle}</small>{/if}
    </div>

    {#if profiles.length > 1}
      <nav class="rv-profiles" aria-label="Pilih jenis CV">
        {#each profiles as item (item.key)}
          <button type="button" class:on={item.key === activeProfile} aria-current={item.key === activeProfile ? 'page' : undefined} title={item.description} onclick={() => onprofile?.(item.key)}>{item.label}</button>
        {/each}
      </nav>
    {/if}

    <div class="rv-controls">

      <div class="rv-group">
        <button type="button" class="rv-icon" onclick={() => goTo(current - 1)} disabled={current <= 1} aria-label="Halaman sebelumnya"><ChevronLeft size={18} /></button>
        <span class="rv-page"><span class="rv-label">Hal. </span>{current} / {total || '-'}</span>
        <button type="button" class="rv-icon" onclick={() => goTo(current + 1)} disabled={current >= total} aria-label="Halaman berikutnya" data-tip="Halaman berikutnya"><ChevronRight size={18} /></button>
      </div>

      <div class="rv-group">
        <button type="button" class="rv-icon" onclick={() => stepZoom(-1)} aria-label="Perkecil" data-tip="Perkecil" data-zoom-out><Minus size={14} /></button>
        <span class="rv-page" data-zoom-value aria-live="polite" data-tip={MODE_LABELS[zoomMode]}>{Math.round(zoom * 100)}%</span>
        <button type="button" class="rv-icon" onclick={() => stepZoom(1)} aria-label="Perbesar" data-tip="Perbesar" data-zoom-in><Plus size={14} /></button>
        <div class="dropdown dropup">
          <button type="button" class="rv-icon" data-bs-toggle="dropdown" data-bs-offset="0,12" aria-expanded="false" aria-label="Opsi zoom" data-tip="Opsi zoom"><MoreVertical size={16} /></button>
          <ul class="dropdown-menu dropdown-menu-end rv-zoommenu">
            <li><button type="button" class="dropdown-item" class:active={zoomMode === 'fit-width'} onclick={() => applyFit('fit-width')}><MoveHorizontal size={16} /> Sesuai lebar</button></li>
            <li><button type="button" class="dropdown-item" class:active={zoomMode === 'fit-page'} onclick={() => applyFit('fit-page')}><Maximize size={15} /> Sesuai halaman</button></li>
          </ul>
        </div>
      </div>
    </div>

    <div class="rv-actions">
      <a class="rv-download" href={url} download={downloadName} aria-label="Unduh PDF" data-tip="Unduh PDF"><Download size={16} /><span class="rv-label">Unduh PDF</span></a>
      <button type="button" class="rv-copy" onclick={() => (isDark = toggleDocumentTheme() === 'dark')} aria-label={`Ubah ke mode ${isDark ? 'terang' : 'gelap'}`} data-tip="Ubah tema">{#if isDark}<Sun size={16} />{:else}<Moon size={16} />{/if}</button>
      <button type="button" class="rv-copy" class:done={copied} onclick={copyLink} aria-label="Salin tautan" data-tip={copied ? 'Tautan tersalin!' : 'Salin tautan'}>{#if copied}<Check size={16} />{:else}<LinkIcon size={16} />{/if}</button>
    </div>
  </header>

  <button type="button" class="rv-backdrop" class:open={sidebar} onclick={() => (sidebar = false)} aria-label="Tutup panel" tabindex={sidebar ? 0 : -1}></button>

  <div class="rv-body">
    <aside class="rv-side" class:open={sidebar} aria-hidden={!sidebar}>
      <div class="rv-tabbar">
        {#if tabsCanLeft}
          <button type="button" class="rv-tabarrow" onclick={() => scrollTabs(-1)} aria-label="Geser tab ke kiri"><ChevronLeft size={16} /></button>
        {/if}
        <div class="rv-tabs" role="tablist" bind:this={tabStrip} use:watchTabScroll>
          {#each tabs as [key, label] (key)}
            <button type="button" role="tab" aria-selected={tab === key} class:active={tab === key} onclick={() => (tab = key as Tab)}>{label}</button>
          {/each}
        </div>
        {#if tabsCanRight}
          <button type="button" class="rv-tabarrow" onclick={() => scrollTabs(1)} aria-label="Geser tab ke kanan"><ChevronRight size={16} /></button>
        {/if}
      </div>

      {#if tab === 'search'}
        <form class="rv-search" onsubmit={(event) => { event.preventDefault(); stepHit(1); }}>
          <Search size={14} />
          <input bind:this={searchInput} type="search" name="rv-search" autocomplete="off" autocorrect="off" spellcheck="false" placeholder="Cari dalam dokumen" bind:value={query} aria-label="Cari dalam dokumen" onkeydown={(event) => { if (event.key === 'Enter' && event.shiftKey) { event.preventDefault(); stepHit(-1); } }} />
          {#if searched}<span class="rv-count">{searching ? '…' : hits.length === 0 ? '0' : `${activeHit + 1}/${hits.length}`}</span>{/if}
        </form>
      {/if}

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
        {:else if tab === 'info'}
          <ul class="rv-info">
            {#each infoRows as row, index (index)}
              <li><span>{row.label}</span>{#if row.href}<a href={row.href} target="_blank" rel="noopener noreferrer">{row.value}</a>{:else}<strong>{row.value}</strong>{/if}</li>
            {:else}
              <li class="rv-muted">Belum ada informasi.</li>
            {/each}
          </ul>
        {:else if tab === 'verify'}
          <ResumeVerification {signatures} />
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

    <main class="rv-main" bind:this={scroller} onscroll={updateCurrent} use:wheelZoom use:pinchZoomAction>
      {#if status === 'loading'}
        <p class="rv-state">Memuat dokumen…</p>
      {:else if status === 'error'}
        <p class="rv-state">Dokumen gagal dimuat. <a href={url}>Buka langsung</a></p>
      {:else}
        {#each pages as page, index (index)}
          {@const size = page.getViewport({ scale: zoom })}
          <div
            class="rv-sheet"
            role="presentation"
            style:width="{size.width}px"
            style:height="{size.height}px"
            style:margin-bottom="{GAP}px"
          >
            <canvas use:track={index + 1}></canvas>
            <div class="rv-text" style:transform="scale({zoom})" style:width="{page.view[2] - page.view[0]}px" style:height="{page.view[3] - page.view[1]}px"></div>
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
          </div>
        {/each}
      {/if}
    </main>
  </div>
</div>

<style>
  .rv:not(.rv-fitted) .rv-main:has(.rv-sheet) { opacity: 0; }
  .rv-main { transition: opacity 0.25s ease; }
  .rv { display: flex; flex-direction: column; height: 100%; color: var(--neo-body-color); font-size: 0.875rem; background: var(--neo-body-bg); }
  .rv-bar { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem 1rem; padding: 0.75rem 1rem; color: #fff; background: var(--neo-primary); }
  .rv-back { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.5rem 0.875rem; font-weight: 600; color: var(--neo-primary); cursor: pointer; background: #fff; border: 0; border-radius: 0.5rem; }
  .rv-title { display: flex; flex: 1 1 14rem; flex-direction: column; min-width: 0; }
  .rv-title strong { font-size: 1rem; }
  .rv-title small { overflow: hidden; color: #cbd5e1; font-size: 0.7rem; text-overflow: ellipsis; white-space: nowrap; }
  .rv-search { display: flex; align-items: center; gap: 0.5rem; margin: 0.75rem 0.75rem 0; padding: 0 0.875rem; color: var(--neo-body-color); background: var(--neo-tertiary-bg, rgba(0, 0, 0, 0.05)); border: 1px solid var(--neo-border-color); border-radius: 999px; }
  .rv-side > .rv-search { box-sizing: border-box; width: calc(100% - 1.5rem); }
  .rv-search input::-webkit-search-cancel-button { display: none; }
  .rv-search:focus-within { border-color: var(--neo-primary); }
  .rv-search input { flex: 1; min-width: 0; padding: 0.5rem 0; color: inherit; background: transparent; border: 0; outline: 0; }
  .rv-search input::placeholder { color: var(--neo-secondary-color); }
  .rv-group { display: flex; align-items: center; gap: 0.25rem; }
  .rv-page { min-width: 4.5rem; font-size: 0.8rem; font-weight: 600; text-align: center; }
  .rv-icon { display: inline-grid; width: 1.75rem; height: 1.75rem; color: #fff; cursor: pointer; background: transparent; border: 0; border-radius: 0.375rem; place-items: center; }
  .rv-icon:hover:not(:disabled) { background: rgba(255, 255, 255, 0.15); }
  .rv-icon:disabled { opacity: 0.4; cursor: default; }
  .rv-actions { display: flex; gap: 0.25rem; padding: 0.1875rem; background: rgba(255, 255, 255, 0.12); border-radius: 999px; }
  .rv-download { padding: 0.3125rem 0.875rem; font-size: 0.8rem; font-weight: 600; color: #fff; text-decoration: none; background: var(--neo-primary-text-emphasis); border-radius: 999px; }
  .rv-copy { display: inline-grid; width: 1.9rem; height: 1.9rem; padding: 0; color: #fff; cursor: pointer; background: transparent; border: 0; border-radius: 999px; place-items: center; transition: background 0.2s ease, color 0.2s ease; }
  .rv-copy:hover { background: rgba(255, 255, 255, 0.18); }
  .rv-copy.done { color: var(--neo-primary); background: #fff; }
  .rv-body { display: flex; flex: 1; min-height: 0; }
  .rv-side { display: flex; flex: 0 0 20rem; width: 20rem; overflow: hidden; transition: flex-basis 0.25s ease, width 0.25s ease, opacity 0.2s ease, visibility 0.25s; flex-direction: column; min-height: 0; background: var(--neo-body-bg); border-right: 1px solid var(--neo-border-color); }
  .rv-side > * { width: 20rem; }
  .rv-tabbar { position: relative; display: flex; align-items: stretch; border-bottom: 1px solid var(--neo-border-color); }
  .rv-tabs { position: relative; display: flex; flex: 1; min-width: 0; overflow-x: auto; scrollbar-width: none; touch-action: pan-x; overscroll-behavior: contain; }
  .rv-tabs::-webkit-scrollbar { display: none; }
  .rv-tabarrow { z-index: 2; display: grid; flex: 0 0 1.75rem; place-items: center; padding: 0; color: var(--neo-secondary-color); cursor: pointer; background: var(--neo-body-bg); border: 0; }
  .rv-tabarrow:hover { color: var(--neo-primary); background: var(--neo-primary-bg-subtle); }
  .rv-tabs button { flex: 1 0 auto; padding: 0.75rem 1rem; white-space: nowrap; font-weight: 600; color: var(--neo-secondary-color); cursor: pointer; background: transparent; border: 0; border-bottom: 2px solid transparent; }
  .rv-tabs button.active { color: var(--neo-primary); border-bottom-color: var(--neo-primary); }
  .rv-info { margin: -0.75rem 0; padding: 0; list-style: none; }
  .rv-info li { display: flex; gap: 1rem; align-items: baseline; justify-content: space-between; padding: 0.75rem 1rem; border-bottom: 1px solid var(--neo-border-color); }
  .rv-info span { color: var(--neo-secondary-color); }
  .rv-info a { font-weight: 600; color: var(--neo-primary); text-align: right; overflow-wrap: anywhere; }
  .rv-info strong { font-weight: 600; text-align: right; overflow-wrap: anywhere; }
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
  .rv-main { flex: 1; min-width: 0; padding: 1rem; overflow: auto; overscroll-behavior: contain; touch-action: pan-x pan-y; }
  .rv-icon, .rv-back, .rv-download, .rv-copy, .rv-profiles button, .rv-tabs button, .rv-item, .rv-thumb { touch-action: manipulation; -webkit-tap-highlight-color: transparent; }
  .rv-profiles { display: flex; gap: 0.25rem; padding: 0.1875rem; background: rgba(255, 255, 255, 0.12); border-radius: 999px; }
  .rv-profiles button { padding: 0.3125rem 0.75rem; font-size: 0.8rem; font-weight: 600; color: #fff; white-space: nowrap; cursor: pointer; background: transparent; border: 0; border-radius: 999px; }
  .rv-profiles button.on { color: var(--neo-primary); background: #fff; }
  .rv-icon:focus-visible, .rv-back:focus-visible, .rv-download:focus-visible, .rv-copy:focus-visible, .rv-profiles button:focus-visible { outline: 2px solid #fff; outline-offset: 2px; }
  .rv-controls .rv-icon:focus-visible { outline-color: var(--neo-primary); }
  .rv-sheet { position: relative; will-change: transform; margin-right: auto; margin-left: auto; background: #fff; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.18); }
  .rv-text { position: absolute; top: 0; left: 0; overflow: clip; line-height: 1; text-align: initial; transform-origin: 0 0; --total-scale-factor: 1; --scale-round-x: 1px; --scale-round-y: 1px; --min-font-size: 1; --text-scale-factor: calc(var(--total-scale-factor) * var(--min-font-size)); --min-font-size-inv: calc(1 / var(--min-font-size)); will-change: transform; }
  .rv-text :global(:is(span, br)) { position: absolute; color: transparent; white-space: pre; cursor: text; transform-origin: 0 0; user-select: text; }
  .rv-text > :global(:not(.markedContent)), .rv-text :global(.markedContent span:not(.markedContent)) { --font-height: 0; --scale-x: 1; --rotate: 0deg; font-size: calc(var(--text-scale-factor) * var(--font-height)); transform: rotate(var(--rotate)) scaleX(var(--scale-x)) scale(var(--min-font-size-inv)); }
  .rv-text :global(.markedContent) { display: contents; }
  .rv-text :global(::selection) { color: transparent; background: rgba(var(--neo-primary-rgb), 0.35); }
  .rv-text :global(.endOfContent) { position: absolute; inset: 100% 0 0; display: block; cursor: default; user-select: none; }
  .rv-text:global(.selecting) :global(.endOfContent) { top: 0; }
  .rv-sheet canvas { user-select: none; }
  .rv-zoommenu .dropdown-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; }
  .rv-icon.on { background: rgba(255, 255, 255, 0.28); }
  .rv-state { margin-top: 3rem; text-align: center; color: var(--neo-secondary-color); }

  @media (prefers-reduced-motion: reduce) {
    .rv-sheet, .rv-side, .rv-backdrop, .rv-text { transition: none !important; }
    .rv-hit.active.first::before, .rv-hit.active.first::after { animation: none; }
  }

  .rv-side:not(.open) { flex-basis: 0; width: 0; border-right-width: 0; opacity: 0; visibility: hidden; }
  .rv-backdrop { display: none; }
  .rv-controls .rv-icon { width: 2rem; height: 2rem; }
  .rv-controls .rv-group { gap: 0.125rem; }
  .rv-controls { position: fixed; z-index: 20; bottom: calc(1rem + env(safe-area-inset-bottom)); right: 0; left: 0; width: fit-content; max-width: calc(100% - 1rem); margin-inline: auto; flex-wrap: wrap; justify-content: center; display: flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; color: var(--neo-body-color); background: var(--neo-body-bg); border: 1px solid var(--neo-border-color); border-radius: 999px; box-shadow: 0 4px 18px rgba(0, 0, 0, 0.2); }
  .rv-controls .rv-icon { color: inherit; }
  .rv-controls .rv-icon.on { color: var(--neo-primary-text-emphasis); background: var(--neo-primary-bg-subtle); }
  .rv-controls .rv-icon:hover:not(:disabled) { background: var(--neo-primary-bg-subtle); }
  .rv-controls .rv-page { min-width: 2.5rem; font-size: 0.75rem; }
  .rv-controls .rv-group + .rv-group { padding-left: 0.375rem; border-left: 1px solid var(--neo-border-color); }
  .rv-controls { gap: 0.25rem; }
  .rv-main { padding-bottom: calc(6rem + env(safe-area-inset-bottom)); }
  [data-tip] { position: relative; }
  [data-tip]:hover::after, [data-tip]:focus-visible::after { position: absolute; z-index: 50; top: calc(100% + 0.5rem); left: 50%; padding: 0.25rem 0.5rem; font-size: 0.7rem; font-weight: 600; line-height: 1.2; color: #fff; white-space: nowrap; pointer-events: none; content: attr(data-tip); background: #111827; border-radius: 0.375rem; transform: translateX(-50%); }
  .rv-controls [data-tip]:hover::after, .rv-controls [data-tip]:focus-visible::after { top: auto; bottom: calc(100% + 0.75rem); }
  .rv-download { display: inline-flex; align-items: center; gap: 0.375rem; }

  @media (max-width: 991.98px) {
    .rv-side, .rv-side:not(.open) { position: fixed; z-index: 30; top: 0; bottom: 0; left: 0; flex-basis: auto; width: min(20rem, 86vw); border-right-width: 1px; opacity: 1; visibility: visible; box-shadow: 0 0 40px rgba(0, 0, 0, 0.35); transition: transform 0.28s cubic-bezier(0.32, 0.72, 0, 1); transform: translateX(-105%); }
    .rv-side.open { transform: translateX(0); }
    .rv-side > * { width: min(20rem, 86vw); }
    .rv-backdrop { position: fixed; z-index: 29; inset: 0; display: block; background: rgba(0, 0, 0, 0.4); border: 0; opacity: 0; pointer-events: none; transition: opacity 0.25s ease; }
    .rv-backdrop.open { opacity: 1; pointer-events: auto; }
    .rv-profiles { flex-basis: 100%; order: 6; justify-content: stretch; }
    .rv-profiles button { flex: 1; }
  }

  @media (max-width: 767.98px) {
    .rv-bar { flex-wrap: wrap; gap: 0.5rem; padding: 0.625rem 0.75rem; }
    .rv-label, .rv-title small { display: none; }
    .rv-back, .rv-download { display: inline-grid; width: 2.25rem; height: 2.25rem; padding: 0; place-items: center; border-radius: 999px; }
    .rv-title { flex: 1 1 0; }
    .rv-title strong { overflow: hidden; font-size: 1rem; text-overflow: ellipsis; white-space: nowrap; }
    .rv-main { padding: 0.75rem 0.75rem calc(6rem + env(safe-area-inset-bottom)); }
  }
</style>
