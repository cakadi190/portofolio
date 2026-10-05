<script lang="ts" module>
  export const PDF_ZOOM_MIN = 0.5;
  export const PDF_ZOOM_MAX = 4;
  export const PDF_ZOOM_STEP = 0.25;
</script>

<script lang="ts">
  import BookOpen from '@lucide/svelte/icons/book-open';
  import Book from '@lucide/svelte/icons/book';
  import ChevronLeft from '@lucide/svelte/icons/chevron-left';
  import ChevronRight from '@lucide/svelte/icons/chevron-right';
  import ChevronDown from '@lucide/svelte/icons/chevron-down';
  import Download from '@lucide/svelte/icons/download';
  import FileText from '@lucide/svelte/icons/file-text';
  import Hand from '@lucide/svelte/icons/hand';
  import Maximize from '@lucide/svelte/icons/maximize';
  import Menu from '@lucide/svelte/icons/menu';
  import Minus from '@lucide/svelte/icons/minus';
  import PanelLeft from '@lucide/svelte/icons/panel-left';
  import Plus from '@lucide/svelte/icons/plus';
  import RotateCcw from '@lucide/svelte/icons/rotate-ccw';
  import RotateCw from '@lucide/svelte/icons/rotate-cw';
  import Search from '@lucide/svelte/icons/search';
  import SlidersHorizontal from '@lucide/svelte/icons/sliders-horizontal';
  import type { PDFDocumentProxy, PDFPageProxy, RenderTask } from 'pdfjs-dist';

  type PageLayout = 'single' | 'odd' | 'even';
  type Rect = [number, number, number, number];
  type TextRun = { str: string; transform: number[]; width: number; height: number };
  type SearchHit = { page: number; rect: Rect; snippet: string };
  type Slot = { node: HTMLElement; key: string; task: RenderTask | null };

  const ZOOM_PRESETS = [0.5, 0.75, 1, 1.25, 1.5, 2, 3, 4];
  const PAGE_GAP = 12;
  const THUMB_WIDTH = 112;

  let {
    url,
    title = 'Pratinjau PDF',
    zoom = $bindable(1),
  }: {
    url: string;
    title?: string;
    zoom?: number;
  } = $props();

  let root = $state<HTMLElement | null>(null);
  let scroller = $state<HTMLElement | null>(null);
  let thumbList = $state<HTMLElement | null>(null);
  let status = $state<'loading' | 'ready' | 'error'>('loading');
  let pdfPages = $state.raw<PDFPageProxy[]>([]);
  let page = $state(1);
  let rotation = $state(0);
  let layout = $state<PageLayout>('single');
  let availWidth = $state(0);
  let showThumbs = $state(typeof window !== 'undefined' && window.innerWidth >= 768);
  let showSearch = $state(false);
  let menu = $state<'main' | 'settings' | 'zoom' | null>(null);
  let panTool = $state(true);
  let panning = $state(false);
  let pagerVisible = $state(false);
  let pagerEngaged = false;
  let pagerTimer: number | undefined;

  let query = $state('');
  let caseSensitive = $state(false);
  let wholeWord = $state(false);
  let searching = $state(false);
  let hits = $state.raw<SearchHit[]>([]);
  let activeHit = $state(-1);
  let searchInput = $state<HTMLInputElement | null>(null);

  const slots = new Map<number, Slot>();
  const visible = new Set<number>();
  const thumbs = new Map<number, { node: HTMLCanvasElement; key: string }>();
  const thumbVisible = new Set<number>();
  const textCache = new Map<number, TextRun[]>();
  let observer: IntersectionObserver | null = null;
  let thumbObserver: IntersectionObserver | null = null;
  let renderTimer: number | undefined;
  let searchToken = 0;
  let panOrigin: { x: number; y: number; left: number; top: number } | null = null;

  const pageCount = $derived(pdfPages.length);
  const columns = $derived(layout === 'single' ? 1 : 2);
  const fitWidth = $derived(
    Math.max(
      120,
      Math.min((availWidth - 32 - PAGE_GAP * (columns - 1)) / columns, columns === 1 ? 1024 : 640),
    ),
  );
  const rows = $derived.by(() => {
    const result: number[][] = [];
    let next = 1;

    if (layout === 'even' && pageCount > 0) {
      result.push([1]);
      next = 2;
    }

    const step = layout === 'single' ? 1 : 2;

    for (; next <= pageCount; next += step) {
      result.push(step === 2 && next + 1 <= pageCount ? [next, next + 1] : [next]);
    }

    return result;
  });
  const hitsByPage = $derived.by(() => {
    const grouped = new Map<number, { index: number; rect: Rect }[]>();

    hits.forEach((hit, index) => {
      grouped.set(hit.page, [...(grouped.get(hit.page) ?? []), { index, rect: hit.rect }]);
    });

    return grouped;
  });

  function viewOf(number: number, scale = 1) {
    const source = pdfPages[number - 1];

    return source.getViewport({ scale, rotation: source.rotate + rotation });
  }

  function pageStyle(number: number): string {
    const view = viewOf(number);
    const width = fitWidth * zoom;

    return `width:${width}px;height:${(width * view.height) / view.width}px`;
  }

  function hitStyle(number: number, rect: Rect): string {
    const view = viewOf(number);
    const [x1, y1, x2, y2] = view.convertToViewportRectangle(rect);

    return [
      `left:${(Math.min(x1, x2) / view.width) * 100}%`,
      `top:${(Math.min(y1, y2) / view.height) * 100}%`,
      `width:${(Math.abs(x2 - x1) / view.width) * 100}%`,
      `height:${(Math.abs(y2 - y1) / view.height) * 100}%`,
    ].join(';');
  }

  /** Scrolls the page with the given 1-based number to the top of the viewport. */
  function scrollToPage(number: number): void {
    const slot = slots.get(Math.min(Math.max(number, 1), pageCount));

    if (!slot || !scroller) {
      return;
    }

    scroller.scrollTo({
      top:
        slot.node.getBoundingClientRect().top -
        scroller.getBoundingClientRect().top +
        scroller.scrollTop -
        8,
    });
  }

  async function renderSlot(number: number): Promise<void> {
    const slot = slots.get(number);
    const source = pdfPages[number - 1];
    const canvas = slot?.node.querySelector('canvas');

    if (!slot || !source || !canvas) {
      return;
    }

    const cssWidth = Math.round(fitWidth * zoom);
    const key = `${cssWidth}:${rotation}`;

    if (slot.key === key) {
      return;
    }

    slot.task?.cancel();
    slot.key = key;

    const base = viewOf(number);
    const view = source.getViewport({
      scale: Math.min((cssWidth / base.width) * (window.devicePixelRatio || 1), 6),
      rotation: source.rotate + rotation,
    });
    const buffer = document.createElement('canvas');
    buffer.width = Math.floor(view.width);
    buffer.height = Math.floor(view.height);

    const task = source.render({ canvas: buffer, viewport: view });
    slot.task = task;

    try {
      await task.promise;
    } catch {
      if (slot.task === task) {
        slot.key = '';
      }

      return;
    }

    canvas.width = buffer.width;
    canvas.height = buffer.height;
    canvas.getContext('2d')?.drawImage(buffer, 0, 0);
    slot.task = null;
  }

  async function renderThumb(number: number): Promise<void> {
    const entry = thumbs.get(number);
    const source = pdfPages[number - 1];

    if (!entry || !source || entry.key === String(rotation)) {
      return;
    }

    entry.key = String(rotation);

    const base = viewOf(number);
    const view = source.getViewport({
      scale: (THUMB_WIDTH / base.width) * (window.devicePixelRatio || 1),
      rotation: source.rotate + rotation,
    });
    const buffer = document.createElement('canvas');
    buffer.width = Math.floor(view.width);
    buffer.height = Math.floor(view.height);

    try {
      await source.render({ canvas: buffer, viewport: view }).promise;
    } catch {
      entry.key = '';
      return;
    }

    entry.node.width = buffer.width;
    entry.node.height = buffer.height;
    entry.node.getContext('2d')?.drawImage(buffer, 0, 0);
  }

  function pageSlot(node: HTMLElement, number: number) {
    slots.set(number, { node, key: '', task: null });
    node.dataset.page = String(number);
    observer?.observe(node);

    return {
      destroy() {
        slots.get(number)?.task?.cancel();
        slots.delete(number);
        visible.delete(number);
        observer?.unobserve(node);
      },
    };
  }

  function thumbSlot(node: HTMLCanvasElement, number: number) {
    thumbs.set(number, { node, key: '' });
    node.dataset.page = String(number);
    thumbObserver?.observe(node);

    return {
      destroy() {
        thumbs.delete(number);
        thumbVisible.delete(number);
        thumbObserver?.unobserve(node);
      },
    };
  }

  function revealPager(): void {
    pagerVisible = true;
    window.clearTimeout(pagerTimer);

    if (!pagerEngaged) {
      pagerTimer = window.setTimeout(() => (pagerVisible = false), 1500);
    }
  }

  function engagePager(engaged: boolean): void {
    pagerEngaged = engaged;
    revealPager();
  }

  function updateCurrentPage(): void {
    if (!scroller || slots.size === 0) {
      return;
    }

    const probe = scroller.getBoundingClientRect().top + scroller.clientHeight / 3;
    let current = 1;

    for (const [number, slot] of slots) {
      if (slot.node.getBoundingClientRect().top <= probe && number > current) {
        current = number;
      }
    }

    page = current;
  }

  $effect(() => {
    const source = url;
    const host = scroller;
    const rail = thumbList;

    if (!host) {
      return;
    }

    let cancelled = false;
    let doc: PDFDocumentProxy | null = null;

    status = 'loading';
    pdfPages = [];
    page = 1;
    hits = [];
    activeHit = -1;
    textCache.clear();

    observer = new IntersectionObserver(
      (entries) => {
        for (const entry of entries) {
          const number = Number((entry.target as HTMLElement).dataset.page);

          if (entry.isIntersecting) {
            visible.add(number);
            void renderSlot(number);
          } else {
            visible.delete(number);
          }
        }
      },
      { root: host, rootMargin: '100% 0px' },
    );

    thumbObserver = new IntersectionObserver(
      (entries) => {
        for (const entry of entries) {
          const number = Number((entry.target as HTMLElement).dataset.page);

          if (entry.isIntersecting) {
            thumbVisible.add(number);
            void renderThumb(number);
          } else {
            thumbVisible.delete(number);
          }
        }
      },
      { root: rail, rootMargin: '50% 0px' },
    );

    (async () => {
      const [pdfjs, worker] = await Promise.all([
        import('pdfjs-dist'),
        import('pdfjs-dist/build/pdf.worker.min.mjs?url'),
      ]);
      pdfjs.GlobalWorkerOptions.workerSrc = worker.default;

      const response = await fetch(source, { credentials: 'same-origin' });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      doc = await pdfjs.getDocument({ data: new Uint8Array(await response.arrayBuffer()) })
        .promise;

      const loaded: PDFPageProxy[] = [];

      for (let number = 1; number <= doc.numPages; number++) {
        loaded.push(await doc.getPage(number));
      }

      if (cancelled) {
        return;
      }

      pdfPages = loaded;
      status = 'ready';
    })().catch(() => {
      if (!cancelled) {
        status = 'error';
      }
    });

    return () => {
      cancelled = true;
      window.clearTimeout(renderTimer);
      window.clearTimeout(pagerTimer);
      observer?.disconnect();
      thumbObserver?.disconnect();
      slots.forEach((slot) => slot.task?.cancel());
      void doc?.destroy();
    };
  });

  $effect(() => {
    const host = scroller;

    if (!host) {
      return;
    }

    const resize = new ResizeObserver(() => (availWidth = host.clientWidth));
    resize.observe(host);

    return () => resize.disconnect();
  });

  /** Resize instantly via CSS, then re-render sharp once zooming/resizing settles. */
  $effect(() => {
    void [zoom, fitWidth, rotation, layout];

    if (status !== 'ready') {
      return;
    }

    window.clearTimeout(renderTimer);
    renderTimer = window.setTimeout(() => {
      visible.forEach((number) => void renderSlot(number));
    }, 150);
  });

  $effect(() => {
    void rotation;
    thumbVisible.forEach((number) => void renderThumb(number));
  });

  $effect(() => {
    const current = page;

    if (showThumbs) {
      thumbList
        ?.querySelector(`[data-thumb="${current}"]`)
        ?.scrollIntoView({ block: 'nearest' });
    }
  });

  function setZoom(next: number): void {
    zoom = Math.min(PDF_ZOOM_MAX, Math.max(PDF_ZOOM_MIN, next));
  }

  function rotate(delta: number): void {
    rotation = (rotation + delta + 360) % 360;
    menu = null;
  }

  function setLayout(next: PageLayout): void {
    layout = next;
    menu = null;
  }

  function toggleFullscreen(): void {
    const host = root?.closest<HTMLElement>('[role="dialog"]');

    if (document.fullscreenElement) {
      void document.exitFullscreen();
    } else {
      void host?.requestFullscreen();
    }

    menu = null;
  }

  function toggleSearch(): void {
    showSearch = !showSearch;

    if (showSearch) {
      queueMicrotask(() => searchInput?.focus());
    }
  }

  function escapeRegExp(value: string): string {
    return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  async function runSearch(): Promise<void> {
    const term = query.trim();
    const token = ++searchToken;

    if (!term || pdfPages.length === 0) {
      hits = [];
      activeHit = -1;
      searching = false;
      return;
    }

    searching = true;

    const pattern = new RegExp(
      `${wholeWord ? '\\b' : ''}${escapeRegExp(term)}${wholeWord ? '\\b' : ''}`,
      caseSensitive ? 'g' : 'gi',
    );
    const found: SearchHit[] = [];

    for (let number = 1; number <= pdfPages.length; number++) {
      let runs = textCache.get(number);

      if (!runs) {
        const content = await pdfPages[number - 1].getTextContent();
        runs = (content.items as unknown[]).filter(
          (item): item is TextRun => typeof (item as TextRun).str === 'string',
        );
        textCache.set(number, runs);
      }

      if (token !== searchToken) {
        return;
      }

      for (const run of runs) {
        for (const match of run.str.matchAll(pattern)) {
          const length = run.str.length;
          const left = run.transform[4] + run.width * (match.index / length);
          const width = run.width * (match[0].length / length);
          const height = run.height || Math.abs(run.transform[3]);
          const bottom = run.transform[5] - height * 0.2;
          const from = Math.max(0, match.index - 24);

          found.push({
            page: number,
            rect: [left, bottom, left + width, bottom + height * 1.2],
            snippet: run.str.slice(from, match.index + match[0].length + 40).trim(),
          });
        }
      }
    }

    hits = found;
    searching = false;
    focusHit(found.length > 0 ? 0 : -1);
  }

  function focusHit(index: number): void {
    activeHit = index;

    if (index >= 0) {
      scrollToPage(hits[index].page);
    }
  }

  function stepHit(delta: number): void {
    if (hits.length > 0) {
      focusHit((activeHit + delta + hits.length) % hits.length);
    }
  }

  $effect(() => {
    void [query, caseSensitive, wholeWord, status];

    if (status !== 'ready') {
      return;
    }

    const timer = window.setTimeout(() => void runSearch(), 250);

    return () => window.clearTimeout(timer);
  });

  function onSearchKeydown(event: KeyboardEvent): void {
    if (event.key === 'Enter') {
      event.preventDefault();
      stepHit(event.shiftKey ? -1 : 1);
    }
  }

  function onWheel(event: WheelEvent): void {
    if (!event.ctrlKey && !event.metaKey) {
      return;
    }

    event.preventDefault();
    setZoom(zoom * Math.exp(-event.deltaY * 0.006));
  }

  function onPointerDown(event: PointerEvent): void {
    if (event.pointerType !== 'mouse' || event.button !== 0 || !scroller || !panTool) {
      return;
    }

    scroller.setPointerCapture(event.pointerId);
    panning = true;
    panOrigin = {
      x: event.clientX,
      y: event.clientY,
      left: scroller.scrollLeft,
      top: scroller.scrollTop,
    };
  }

  function onPointerMove(event: PointerEvent): void {
    if (!panOrigin || !scroller) {
      return;
    }

    scroller.scrollLeft = panOrigin.left - (event.clientX - panOrigin.x);
    scroller.scrollTop = panOrigin.top - (event.clientY - panOrigin.y);
  }

  function onPointerEnd(): void {
    panOrigin = null;
    panning = false;
  }

  function onWindowClick(event: MouseEvent): void {
    if (!(event.target as HTMLElement).closest('.pdf-anchor')) {
      menu = null;
    }
  }
</script>

<svelte:window onclick={onWindowClick} />

<div class="pdf-root" bind:this={root}>
  <div class="pdf-toolbar">
    <div class="pdf-group">
      <div class="pdf-anchor">
        <button type="button" class="pdf-button" aria-label="Menu" aria-expanded={menu === 'main'} onclick={() => (menu = menu === 'main' ? null : 'main')}>
          <Menu size={18} />
        </button>
        {#if menu === 'main'}
          <div class="pdf-popover" role="menu">
            <a class="pdf-item" role="menuitem" href={url} download={title} onclick={() => (menu = null)}>
              <Download size={16} /> Unduh
            </a>
            <button type="button" class="pdf-item" role="menuitem" onclick={toggleFullscreen}>
              <Maximize size={16} /> Layar penuh
            </button>
          </div>
        {/if}
      </div>

      <button type="button" class="pdf-button" class:is-active={showThumbs} aria-label="Panel halaman" aria-pressed={showThumbs} onclick={() => (showThumbs = !showThumbs)}>
        <PanelLeft size={18} />
      </button>

      <div class="pdf-anchor">
        <button type="button" class="pdf-button" aria-label="Tampilan halaman" aria-expanded={menu === 'settings'} onclick={() => (menu = menu === 'settings' ? null : 'settings')}>
          <SlidersHorizontal size={18} />
        </button>
        {#if menu === 'settings'}
          <div class="pdf-popover" role="menu">
            <p class="pdf-heading">Orientasi halaman</p>
            <button type="button" class="pdf-item" role="menuitem" onclick={() => rotate(90)}>
              <RotateCw size={16} /> Putar searah jarum jam
            </button>
            <button type="button" class="pdf-item" role="menuitem" onclick={() => rotate(-90)}>
              <RotateCcw size={16} /> Putar berlawanan arah jarum jam
            </button>
            <p class="pdf-heading">Tata letak halaman</p>
            <button type="button" class="pdf-item" class:is-active={layout === 'single'} role="menuitemradio" aria-checked={layout === 'single'} onclick={() => setLayout('single')}>
              <FileText size={16} /> Satu halaman
            </button>
            <button type="button" class="pdf-item" class:is-active={layout === 'odd'} role="menuitemradio" aria-checked={layout === 'odd'} onclick={() => setLayout('odd')}>
              <BookOpen size={16} /> Halaman ganjil
            </button>
            <button type="button" class="pdf-item" class:is-active={layout === 'even'} role="menuitemradio" aria-checked={layout === 'even'} onclick={() => setLayout('even')}>
              <Book size={16} /> Halaman genap
            </button>
          </div>
        {/if}
      </div>
    </div>

    <div class="pdf-group pdf-zoom">
      <div class="pdf-anchor">
        <button type="button" class="pdf-button pdf-zoom-value" aria-label="Pilih zoom" aria-expanded={menu === 'zoom'} onclick={() => (menu = menu === 'zoom' ? null : 'zoom')}>
          {Math.round(zoom * 100)}% <ChevronDown size={14} />
        </button>
        {#if menu === 'zoom'}
          <div class="pdf-popover pdf-popover-narrow" role="menu">
            {#each ZOOM_PRESETS as preset (preset)}
              <button type="button" class="pdf-item" class:is-active={preset === zoom} role="menuitemradio" aria-checked={preset === zoom} onclick={() => { setZoom(preset); menu = null; }}>
                {preset * 100}%
              </button>
            {/each}
          </div>
        {/if}
      </div>
      <button type="button" class="pdf-button" aria-label="Perkecil" disabled={zoom <= PDF_ZOOM_MIN} onclick={() => setZoom(zoom - PDF_ZOOM_STEP)}>
        <Minus size={18} />
      </button>
      <button type="button" class="pdf-button" aria-label="Perbesar" disabled={zoom >= PDF_ZOOM_MAX} onclick={() => setZoom(zoom + PDF_ZOOM_STEP)}>
        <Plus size={18} />
      </button>
    </div>

    <div class="pdf-group pdf-end">
      <button type="button" class="pdf-button" class:is-active={panTool} aria-label="Alat geser" aria-pressed={panTool} title="Alat geser" onclick={() => (panTool = !panTool)}>
        <Hand size={18} />
      </button>
      <span class="pdf-divider" aria-hidden="true"></span>
      <button type="button" class="pdf-button" class:is-active={showSearch} aria-label="Cari" aria-pressed={showSearch} onclick={toggleSearch}>
        <Search size={18} />
      </button>
    </div>
  </div>

  <div class="pdf-body">
    {#if showThumbs}
      <div class="pdf-thumbs" bind:this={thumbList} aria-label="Daftar halaman">
        {#each pdfPages as _, index (index)}
          {@const number = index + 1}
          {@const thumb = viewOf(number)}
          <button type="button" class="pdf-thumb" class:is-current={page === number} data-thumb={number} aria-label={`Halaman ${number}`} aria-current={page === number} onclick={() => scrollToPage(number)}>
            <canvas use:thumbSlot={number} style:aspect-ratio={`${thumb.width} / ${thumb.height}`}></canvas>
            <span>{number}</span>
          </button>
        {/each}
      </div>
    {/if}

    <div class="pdf-main">
    <!-- svelte-ignore a11y_no_static_element_interactions -->
    <div
      class="pdf-scroller"
      class:is-pan={panTool}
      class:is-panning={panning}
      aria-busy={status === 'loading'}
      bind:this={scroller}
      onscroll={() => {
        updateCurrentPage();
        revealPager();
      }}
      onwheel={onWheel}
      onpointerdown={onPointerDown}
      onpointermove={onPointerMove}
      onpointerup={onPointerEnd}
      onpointercancel={onPointerEnd}
    >
      <div class="pdf-pages">
        {#each rows as row (row[0])}
          <div class="pdf-row">
            {#each row as number (number)}
              <div class="pdf-page" use:pageSlot={number} style={pageStyle(number)}>
                <canvas aria-label={`${title} – halaman ${number}`} oncontextmenu={(event) => event.preventDefault()}></canvas>
                {#each hitsByPage.get(number) ?? [] as hit (hit.index)}
                  <span class="pdf-hit" class:is-active={hit.index === activeHit} style={hitStyle(number, hit.rect)}></span>
                {/each}
              </div>
            {/each}
          </div>
        {/each}
      </div>

      {#if status === 'loading'}
        <p class="pdf-message">Memuat PDF…</p>
      {:else if status === 'error'}
        <p class="pdf-message" role="alert">PDF gagal dimuat.</p>
      {/if}
    </div>

    {#if pageCount > 0}
      <!-- svelte-ignore a11y_no_static_element_interactions -->
      <div
        class="pdf-pager"
        class:is-visible={pagerVisible}
        onmouseenter={() => engagePager(true)}
        onmouseleave={() => engagePager(false)}
        onfocusin={() => engagePager(true)}
        onfocusout={() => engagePager(false)}
      >
        <button type="button" class="pdf-button" aria-label="Halaman sebelumnya" disabled={page <= 1} onclick={() => scrollToPage(page - 1)}>
          <ChevronLeft size={18} />
        </button>
        <input
          class="pdf-page-input"
          type="number"
          min="1"
          max={pageCount}
          aria-label="Nomor halaman"
          value={page}
          onchange={(event) => scrollToPage(Number(event.currentTarget.value))}
        />
        <span>/ {pageCount}</span>
        <button type="button" class="pdf-button" aria-label="Halaman berikutnya" disabled={page >= pageCount} onclick={() => scrollToPage(page + 1)}>
          <ChevronRight size={18} />
        </button>
      </div>
    {/if}
    </div>

    {#if showSearch}
      <div class="pdf-search">
        <label class="pdf-search-field">
          <Search size={16} />
          <input bind:this={searchInput} bind:value={query} type="search" placeholder="Cari dalam dokumen" aria-label="Cari dalam dokumen" onkeydown={onSearchKeydown} />
        </label>
        <label class="pdf-check"><input type="checkbox" bind:checked={caseSensitive} /> Sesuai huruf besar/kecil</label>
        <label class="pdf-check"><input type="checkbox" bind:checked={wholeWord} /> Kata utuh</label>

        {#if query.trim()}
          <p class="pdf-count">
            {#if searching}
              Mencari…
            {:else if hits.length === 0}
              Tidak ada hasil
            {:else}
              {activeHit + 1} dari {hits.length} hasil
            {/if}
          </p>
        {/if}

        <ul class="pdf-results">
          {#each hits as hit, index (index)}
            <li>
              <button type="button" class="pdf-result" class:is-active={index === activeHit} onclick={() => focusHit(index)}>
                <span class="pdf-result-page">Hal. {hit.page}</span>
                <span class="pdf-result-text">{hit.snippet}</span>
              </button>
            </li>
          {/each}
        </ul>
      </div>
    {/if}
  </div>
</div>

<style>
  .pdf-root {
    display: flex;
    flex: 1;
    flex-direction: column;
    min-height: 0;
    margin: 0 0.75rem 0.75rem;
    overflow: hidden;
    border-radius: 0.5rem;
    background: #1c1c1e;
  }

  .pdf-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem 0.75rem;
    padding: 0.375rem 0.75rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  }

  .pdf-group {
    display: flex;
    align-items: center;
    gap: 0.125rem;
  }

  .pdf-zoom {
    padding: 0.125rem 0.25rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.5rem;
  }

  .pdf-end {
    margin-left: auto;
  }

  .pdf-divider {
    width: 1px;
    height: 1.25rem;
    margin: 0 0.375rem;
    background: rgba(255, 255, 255, 0.15);
  }

  .pdf-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    min-width: 2rem;
    height: 2rem;
    padding: 0 0.375rem;
    font-size: 0.8125rem;
    color: inherit;
    cursor: pointer;
    background: transparent;
    border: none;
    border-radius: 0.375rem;
  }

  .pdf-button:hover:not(:disabled),
  .pdf-button.is-active {
    background: rgba(255, 255, 255, 0.12);
  }

  .pdf-button:disabled {
    cursor: default;
    opacity: 0.35;
  }

  .pdf-zoom-value {
    min-width: 4rem;
    font-variant-numeric: tabular-nums;
  }

  .pdf-main {
    position: relative;
    display: flex;
    flex: 1;
    min-width: 0;
  }

  .pdf-pager {
    position: absolute;
    bottom: 1rem;
    left: 50%;
    z-index: 3;
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    font-variant-numeric: tabular-nums;
    background: rgba(24, 24, 26, 0.95);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 0.625rem;
    box-shadow: 0 0.25rem 1rem rgba(0, 0, 0, 0.5);
    opacity: 0;
    pointer-events: none;
    transform: translate(-50%, 0.5rem);
    transition:
      opacity 0.2s ease,
      transform 0.2s ease;
  }

  .pdf-pager.is-visible {
    opacity: 1;
    pointer-events: auto;
    transform: translate(-50%, 0);
  }

  .pdf-page-input {
    width: 3rem;
    padding: 0.125rem 0.25rem;
    color: inherit;
    text-align: center;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 0.25rem;
  }

  .pdf-anchor {
    position: relative;
  }

  .pdf-popover {
    position: absolute;
    top: calc(100% + 0.375rem);
    left: 0;
    z-index: 5;
    display: flex;
    flex-direction: column;
    min-width: 13rem;
    padding: 0.375rem;
    background: #2a2a2d;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 0.5rem;
    box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.5);
  }

  .pdf-popover-narrow {
    min-width: 6rem;
  }

  .pdf-heading {
    margin: 0.375rem 0.5rem 0.25rem;
    font-size: 0.8125rem;
    font-weight: 600;
  }

  .pdf-item {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.5rem;
    font-size: 0.875rem;
    color: inherit;
    text-align: left;
    text-decoration: none;
    cursor: pointer;
    background: transparent;
    border: none;
    border-radius: 0.375rem;
  }

  .pdf-item:hover,
  .pdf-item.is-active {
    background: rgba(255, 255, 255, 0.1);
  }

  .pdf-body {
    display: flex;
    flex: 1;
    min-height: 0;
  }

  .pdf-thumbs {
    flex: 0 0 9.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 0.5rem;
    overflow-y: auto;
    border-right: 1px solid rgba(255, 255, 255, 0.08);
  }

  .pdf-thumb {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    padding: 0;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.7);
    cursor: pointer;
    background: none;
    border: none;
  }

  .pdf-thumb canvas {
    width: 7rem;
    background: #fff;
    border: 2px solid transparent;
    border-radius: 0.25rem;
  }

  .pdf-thumb.is-current canvas {
    border-color: #8ab4f8;
  }

  .pdf-thumb.is-current {
    color: #fff;
  }

  .pdf-scroller {
    flex: 1;
    min-width: 0;
    overflow: auto;
    padding: 0.5rem 1rem 1rem;
  }

  .pdf-scroller.is-pan {
    cursor: grab;
  }

  .pdf-scroller.is-panning {
    cursor: grabbing;
    user-select: none;
  }

  .pdf-pages {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    width: max-content;
    min-width: 100%;
  }

  .pdf-row {
    display: flex;
    gap: 0.75rem;
  }

  .pdf-page {
    position: relative;
    flex-shrink: 0;
    background: #fff;
    border-radius: 0.25rem;
    box-shadow: 0 0.25rem 1rem rgba(0, 0, 0, 0.4);
  }

  .pdf-page canvas {
    display: block;
    width: 100%;
    height: 100%;
    user-select: none;
    -webkit-user-drag: none;
  }

  .pdf-hit {
    position: absolute;
    pointer-events: none;
    background: rgba(255, 213, 0, 0.4);
    mix-blend-mode: multiply;
  }

  .pdf-hit.is-active {
    background: rgba(255, 126, 0, 0.55);
    outline: 2px solid rgba(255, 126, 0, 0.9);
  }

  .pdf-message {
    margin: 2rem 0 0;
    text-align: center;
    color: rgba(255, 255, 255, 0.8);
  }

  .pdf-search {
    display: flex;
    flex: 0 0 17rem;
    flex-direction: column;
    gap: 0.5rem;
    min-height: 0;
    padding: 0.75rem;
    border-left: 1px solid rgba(255, 255, 255, 0.08);
  }

  .pdf-search-field {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0 0.625rem;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 0.5rem;
  }

  .pdf-search-field input {
    flex: 1;
    min-width: 0;
    padding: 0.5rem 0;
    color: inherit;
    background: transparent;
    border: none;
    outline: none;
  }

  .pdf-check {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    cursor: pointer;
  }

  .pdf-count {
    margin: 0;
    padding-top: 0.5rem;
    font-size: 0.8125rem;
    color: rgba(255, 255, 255, 0.7);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
  }

  .pdf-results {
    flex: 1;
    min-height: 0;
    margin: 0;
    padding: 0;
    overflow-y: auto;
    list-style: none;
  }

  .pdf-result {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
    width: 100%;
    padding: 0.5rem;
    font-size: 0.8125rem;
    color: inherit;
    text-align: left;
    cursor: pointer;
    background: transparent;
    border: none;
    border-radius: 0.375rem;
  }

  .pdf-result:hover,
  .pdf-result.is-active {
    background: rgba(255, 255, 255, 0.1);
  }

  .pdf-result-page {
    font-size: 0.75rem;
    color: #8ab4f8;
  }

  .pdf-result-text {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  @media (max-width: 767.98px) {
    .pdf-thumbs {
      flex-basis: 6.5rem;
    }

    .pdf-thumb canvas {
      width: 4.5rem;
    }

    .pdf-search {
      position: absolute;
      right: 0;
      z-index: 4;
      width: min(17rem, 85%);
      background: #1c1c1e;
    }

    .pdf-body {
      position: relative;
    }
  }
</style>
