<script lang="ts">
  import RotateCw from '@lucide/svelte/icons/rotate-cw';

  let { scroller }: { scroller?: HTMLElement } = $props();

  const ENGAGE_SLOP = 12;
  const TRIGGER_DISTANCE = 72;
  const MAX_DISTANCE = 112;
  const RELOAD_DELAY_MS = 150;

  let distance = $state(0);
  let pulling = $state(false);
  let armed = $state(false);
  let refreshing = $state(false);
  let statusText = $state('');

  let startX = 0;
  let startY = 0;
  let tracking = false;
  let engaged = false;

  function damp(value: number): number {
    return MAX_DISTANCE * (1 - Math.exp(-value / MAX_DISTANCE));
  }

  function reset(): void {
    tracking = false;
    engaged = false;
    armed = false;
    pulling = false;
    distance = 0;
  }

  function isBlocked(): boolean {
    return (
      refreshing ||
      document.body.classList.contains('modal-open') ||
      document.body.classList.contains('sidebar-scroll-locked')
    );
  }

  function refresh(): void {
    refreshing = true;
    distance = TRIGGER_DISTANCE;
    statusText = 'Reloading page…';
    window.setTimeout(() => window.location.reload(), RELOAD_DELAY_MS);
  }

  function onTouchStart(event: TouchEvent): void {
    const touch = event.touches[0];

    if (
      isBlocked() ||
      event.touches.length !== 1 ||
      !touch ||
      (scroller?.scrollTop ?? 0) > 0
    ) {
      return;
    }

    startX = touch.clientX;
    startY = touch.clientY;
    tracking = true;
    engaged = false;
    armed = false;
  }

  function onTouchMove(event: TouchEvent): void {
    if (!tracking) {
      return;
    }

    const touch = event.touches[0];

    if (
      event.touches.length !== 1 ||
      !touch ||
      (scroller?.scrollTop ?? 0) > 0
    ) {
      reset();
      return;
    }

    const deltaY = touch.clientY - startY;
    const deltaX = touch.clientX - startX;

    if (!engaged) {
      if (deltaY <= 0 || Math.abs(deltaX) > Math.abs(deltaY)) {
        reset();
        return;
      }

      if (deltaY < ENGAGE_SLOP) {
        return;
      }

      engaged = true;
      pulling = true;
    }

    event.preventDefault();

    distance = damp(deltaY - ENGAGE_SLOP);
    armed = distance >= TRIGGER_DISTANCE;
  }

  function finish(): void {
    if (!tracking) {
      return;
    }

    const shouldRefresh = engaged && armed;

    reset();

    if (shouldRefresh) {
      refresh();
    }
  }

  $effect(() => {
    const region = scroller?.closest<HTMLElement>('[data-main-scroll]');

    if (!region || !scroller) {
      return;
    }

    region.addEventListener('touchstart', onTouchStart, { passive: true });
    region.addEventListener('touchmove', onTouchMove, { passive: false });
    region.addEventListener('touchend', finish, { passive: true });
    region.addEventListener('touchcancel', finish, { passive: true });

    return () => {
      region.removeEventListener('touchstart', onTouchStart);
      region.removeEventListener('touchmove', onTouchMove);
      region.removeEventListener('touchend', finish);
      region.removeEventListener('touchcancel', finish);
    };
  });
</script>

<div
  class="main-refresh"
  class:main-refresh--pulling={pulling}
  class:main-refresh--armed={armed}
  class:main-refresh--refreshing={refreshing}
  style:--main-refresh-offset={`${distance.toFixed(1)}px`}
  style:--main-refresh-progress={Math.min(
    1,
    distance / TRIGGER_DISTANCE,
  ).toFixed(3)}
>
  <div class="main-refresh-indicator" aria-hidden="true">
    <RotateCw size={16} />
  </div>

  <span class="visually-hidden" role="status">{statusText}</span>
</div>
