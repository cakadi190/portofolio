<script lang="ts">
  import ChevronLeft from '@lucide/svelte/icons/chevron-left';
  import ChevronRight from '@lucide/svelte/icons/chevron-right';
  import Download from '@lucide/svelte/icons/download';
  import RotateCcw from '@lucide/svelte/icons/rotate-ccw';
  import X from '@lucide/svelte/icons/x';
  import ZoomIn from '@lucide/svelte/icons/zoom-in';
  import ZoomOut from '@lucide/svelte/icons/zoom-out';
  import { cubicOut } from 'svelte/easing';
  import { fade, fly, scale as scaleTransition } from 'svelte/transition';

  type LightboxImage = { url: string; title?: string | null };

  const ZOOM_MIN = 1;
  const ZOOM_MAX = 8;
  const ZOOM_STEP = 0.5;
  const DOUBLE_CLICK_ZOOM = 2.5;

  /**
   * Google-Drive-style image preview: full-screen overlay with header bar,
   * zoom (buttons, wheel, pinch, double-click), drag-to-pan, prev/next paging
   * and keyboard shortcuts. Ported from the BatamTix lightbox.
   */
  let {
    open = $bindable(false),
    images,
    index = $bindable(0),
  }: {
    open?: boolean;
    images: LightboxImage[];
    index?: number;
  } = $props();

  let scale = $state(1);
  let offset = $state({ x: 0, y: 0 });
  let dragging = $state(false);
  let animating = $state(false);
  let failed = $state(false);
  let direction = $state(1);
  let stage = $state<HTMLElement | null>(null);
  let image = $state<HTMLImageElement | null>(null);

  const pointers = new Map<number, { x: number; y: number }>();
  let dragOrigin: { x: number; y: number; ox: number; oy: number } | null = null;
  let pinchOrigin: { distance: number; scale: number } | null = null;
  let moved = false;

  const current = $derived(images[index]);

  const prefersReducedMotion =
    typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const motion = (duration: number): number => (prefersReducedMotion ? 0 : duration);

  function portal(node: HTMLElement) {
    document.body.appendChild(node);
    document.body.style.overflow = 'hidden';

    return {
      destroy() {
        document.body.style.overflow = '';
      },
    };
  }

  function reset(): void {
    scale = 1;
    offset = { x: 0, y: 0 };
  }

  function clampOffset(): void {
    if (!image || !stage || scale <= 1) {
      offset = { x: 0, y: 0 };
      return;
    }

    const rect = stage.getBoundingClientRect();
    const limitX = Math.max(0, (image.offsetWidth * scale - rect.width) / 2);
    const limitY = Math.max(0, (image.offsetHeight * scale - rect.height) / 2);
    offset = {
      x: Math.min(limitX, Math.max(-limitX, offset.x)),
      y: Math.min(limitY, Math.max(-limitY, offset.y)),
    };
  }

  function zoomTo(next: number, origin?: { x: number; y: number }, animate = false): void {
    if (!stage) {
      return;
    }

    const clamped = Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, next));
    const rect = stage.getBoundingClientRect();
    const cx = (origin?.x ?? rect.left + rect.width / 2) - (rect.left + rect.width / 2);
    const cy = (origin?.y ?? rect.top + rect.height / 2) - (rect.top + rect.height / 2);
    const ratio = clamped / scale;

    offset = { x: cx - (cx - offset.x) * ratio, y: cy - (cy - offset.y) * ratio };
    scale = clamped;
    animating = animate;
    clampOffset();
  }

  function go(delta: number): void {
    const next = index + delta;

    if (next < 0 || next >= images.length) {
      return;
    }

    direction = delta > 0 ? 1 : -1;
    index = next;
  }

  function close(): void {
    open = false;
  }

  function distance(): number {
    const [a, b] = [...pointers.values()];
    return Math.hypot(a.x - b.x, a.y - b.y);
  }

  function midpoint(): { x: number; y: number } {
    const [a, b] = [...pointers.values()];
    return { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 };
  }

  function onPointerDown(event: PointerEvent): void {
    if (event.pointerType === 'mouse' && event.button !== 0) {
      return;
    }

    stage?.setPointerCapture(event.pointerId);
    pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
    moved = false;

    if (pointers.size === 2) {
      dragOrigin = null;
      pinchOrigin = { distance: distance(), scale };
    } else if (pointers.size === 1) {
      dragOrigin = { x: event.clientX, y: event.clientY, ox: offset.x, oy: offset.y };
    }
  }

  function onPointerMove(event: PointerEvent): void {
    if (!pointers.has(event.pointerId)) {
      return;
    }

    pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });

    if (pointers.size >= 2 && pinchOrigin) {
      moved = true;
      zoomTo(pinchOrigin.scale * (distance() / pinchOrigin.distance), midpoint());
      return;
    }

    if (dragOrigin && scale > 1) {
      const dx = event.clientX - dragOrigin.x;
      const dy = event.clientY - dragOrigin.y;
      moved = moved || Math.hypot(dx, dy) > 4;
      dragging = true;
      animating = false;
      offset = { x: dragOrigin.ox + dx, y: dragOrigin.oy + dy };
      clampOffset();
    }
  }

  function onPointerEnd(event: PointerEvent): void {
    pointers.delete(event.pointerId);
    pinchOrigin = null;
    dragging = false;

    const [remaining] = [...pointers.values()];
    dragOrigin = remaining ? { ...remaining, ox: offset.x, oy: offset.y } : null;
    window.setTimeout(() => (moved = false), 0);
  }

  function onWheel(event: WheelEvent): void {
    event.preventDefault();
    zoomTo(scale * Math.exp(-event.deltaY * 0.006), { x: event.clientX, y: event.clientY });
  }

  function onDoubleClick(event: MouseEvent): void {
    if (scale > 1) {
      animating = true;
      reset();
      return;
    }

    zoomTo(DOUBLE_CLICK_ZOOM, { x: event.clientX, y: event.clientY }, true);
  }

  /** Dismiss on clicks outside the (transformed) image bounds. */
  function onStageClick(event: MouseEvent): void {
    if (moved || !image) {
      return;
    }

    const rect = image.getBoundingClientRect();
    const inside =
      event.clientX >= rect.left &&
      event.clientX <= rect.right &&
      event.clientY >= rect.top &&
      event.clientY <= rect.bottom;

    if (!inside) {
      close();
    }
  }

  function onKeydown(event: KeyboardEvent): void {
    if (!open) {
      return;
    }

    switch (event.key) {
      case 'Escape':
        event.stopPropagation();
        close();
        break;
      case 'ArrowLeft':
        go(-1);
        break;
      case 'ArrowRight':
        go(1);
        break;
      case '+':
      case '=':
        zoomTo(scale + ZOOM_STEP, undefined, true);
        break;
      case '-':
      case '_':
        zoomTo(scale - ZOOM_STEP, undefined, true);
        break;
      case '0':
        animating = true;
        reset();
        break;
    }
  }

  $effect(() => {
    void current?.url;
    reset();
    failed = false;
  });
</script>

<svelte:window onkeydowncapture={onKeydown} />

{#if open && current}
  <div class="lightbox" role="dialog" aria-modal="true" aria-label={current.title || 'Pratinjau gambar'} use:portal transition:fade={{ duration: motion(220) }}>
    <div class="lightbox-bar" in:fly|global={{ y: -16, duration: motion(260), delay: motion(60), easing: cubicOut }} out:fade|global={{ duration: motion(120) }}>
      <div class="lightbox-start">
        <button type="button" class="lightbox-button" aria-label="Tutup pratinjau" onclick={close}>
          <X size={20} />
        </button>
        <h2 class="lightbox-title" title={current.title ?? ''}>{current.title || 'Pratinjau gambar'}</h2>
      </div>

      <div class="lightbox-controls">
        {#if images.length > 1}
          <button type="button" class="lightbox-button" aria-label="Sebelumnya" disabled={index === 0} onclick={() => go(-1)}>
            <ChevronLeft size={20} />
          </button>
          <span class="lightbox-label">{index + 1} / {images.length}</span>
          <button type="button" class="lightbox-button" aria-label="Berikutnya" disabled={index === images.length - 1} onclick={() => go(1)}>
            <ChevronRight size={20} />
          </button>
          <span class="lightbox-divider" aria-hidden="true"></span>
        {/if}
        <button type="button" class="lightbox-button" aria-label="Perkecil" disabled={scale <= ZOOM_MIN} onclick={() => zoomTo(scale - ZOOM_STEP, undefined, true)}>
          <ZoomOut size={20} />
        </button>
        <span class="lightbox-label">{Math.round(scale * 100)}%</span>
        <button type="button" class="lightbox-button" aria-label="Perbesar" disabled={scale >= ZOOM_MAX} onclick={() => zoomTo(scale + ZOOM_STEP, undefined, true)}>
          <ZoomIn size={20} />
        </button>
        <button type="button" class="lightbox-button" aria-label="Reset zoom" disabled={scale === 1} onclick={() => { animating = true; reset(); }}>
          <RotateCcw size={20} />
        </button>
      </div>

      <div class="lightbox-end">
        <a class="lightbox-button" href={current.url} download={current.title ?? ''} aria-label="Unduh gambar" title="Unduh gambar">
          <Download size={20} />
        </a>
      </div>
    </div>

    <!-- svelte-ignore a11y_no_static_element_interactions, a11y_click_events_have_key_events -->
    <div
      class="lightbox-stage"
      class:is-zoomed={scale > 1}
      class:is-dragging={dragging}
      bind:this={stage}
      onpointerdown={onPointerDown}
      onpointermove={onPointerMove}
      onpointerup={onPointerEnd}
      onpointercancel={onPointerEnd}
      onwheel={onWheel}
      ondblclick={onDoubleClick}
      onclick={onStageClick}
      in:scaleTransition|global={{ start: 0.94, duration: motion(260), easing: cubicOut }}
      out:scaleTransition|global={{ start: 0.96, duration: motion(180), easing: cubicOut }}
    >
      {#if failed}
        <p class="lightbox-message" role="alert">Gambar gagal dimuat.</p>
      {:else}
        {#key current.url}
          <img
            in:fly|global={{ x: direction * 72, duration: motion(280), easing: cubicOut }}
            out:fade|global={{ duration: motion(140) }}
            bind:this={image}
            class="lightbox-image"
            class:is-animating={animating}
            src={current.url}
            alt={current.title ?? ''}
            draggable="false"
            style:transform={`translate3d(${offset.x}px, ${offset.y}px, 0) scale(${scale})`}
            onerror={() => (failed = true)}
          />
        {/key}
      {/if}
    </div>

    {#if images.length > 1}
      <button type="button" class="lightbox-nav lightbox-nav-prev" aria-label="Sebelumnya" disabled={index === 0} onclick={() => go(-1)}>
        <ChevronLeft size={28} />
      </button>
      <button type="button" class="lightbox-nav lightbox-nav-next" aria-label="Berikutnya" disabled={index === images.length - 1} onclick={() => go(1)}>
        <ChevronRight size={28} />
      </button>
    {/if}
  </div>
{/if}

<style>
  .lightbox {
    position: fixed;
    inset: 0;
    z-index: 2000;
    display: flex;
    flex-direction: column;
    color: #fff;
    background: rgba(18, 18, 18, 0.94);
  }

  .lightbox-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.5rem 0.75rem;
    z-index: 1;
  }

  .lightbox-start,
  .lightbox-end {
    display: flex;
    flex: 1 1 0;
    align-items: center;
    gap: 0.5rem;
    min-width: 0;
  }

  .lightbox-end {
    justify-content: flex-end;
  }

  .lightbox-title {
    margin: 0;
    overflow: hidden;
    font-size: 1rem;
    font-weight: 500;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .lightbox-controls {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.125rem 0.5rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.1);
  }

  .lightbox-label {
    min-width: 3.25rem;
    font-size: 0.8125rem;
    text-align: center;
    font-variant-numeric: tabular-nums;
  }

  .lightbox-divider {
    width: 1px;
    height: 1.25rem;
    margin: 0 0.25rem;
    background: rgba(255, 255, 255, 0.25);
  }

  .lightbox-button {
    display: inline-flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    padding: 0;
    color: inherit;
    cursor: pointer;
    background: transparent;
    border: none;
    border-radius: 50%;
  }

  .lightbox-button:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.15);
  }

  .lightbox-button:disabled,
  .lightbox-nav:disabled {
    cursor: default;
    opacity: 0.35;
  }

  .lightbox-stage {
    position: relative;
    display: flex;
    flex: 1;
    align-items: center;
    justify-content: center;
    min-height: 0;
    overflow: hidden;
    touch-action: none;
    cursor: zoom-in;
  }

  .lightbox-stage.is-zoomed {
    cursor: grab;
  }

  .lightbox-stage.is-dragging {
    cursor: grabbing;
  }

  .lightbox-image {
    position: absolute;
    max-width: 100%;
    max-height: 100%;
    user-select: none;
    will-change: transform;
  }

  .lightbox-image.is-animating {
    transition: transform 0.2s ease;
  }

  .lightbox-message {
    margin: 0;
    color: rgba(255, 255, 255, 0.8);
  }

  .lightbox-nav {
    position: absolute;
    top: 50%;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    color: inherit;
    cursor: pointer;
    background: rgba(255, 255, 255, 0.12);
    border: none;
    border-radius: 50%;
    transform: translateY(-50%);
  }

  .lightbox-nav:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.25);
  }

  .lightbox-nav-prev {
    left: 1rem;
  }

  .lightbox-nav-next {
    right: 1rem;
  }

  @media (max-width: 575.98px) {
    .lightbox-title {
      display: none;
    }

    .lightbox-nav {
      display: none;
    }
  }
</style>
