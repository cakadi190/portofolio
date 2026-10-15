<script lang="ts">
  import X from '@lucide/svelte/icons/x';
  import { onMount, tick, type Snippet } from 'svelte';

  /**
   * One line of the editor's document summary, WordPress-style: a muted label
   * with the current value as a link-coloured button. The button opens a small
   * popover card (title + close) holding the controls that change the value.
   *
   * The card stays mounted while closed (only `display` toggles), so form
   * fields inside it keep submitting with the surrounding `<Form>`. Motion is
   * the shared shadcn overlay motion: enter slides 0.5rem in from the sidebar,
   * leave only fades and zooms, both 150ms.
   */
  let {
    label,
    value,
    title = label,
    placeholder = 'Atur',
    invalid = false,
    width = '20rem',
    children,
  }: {
    label: string;
    value: string;
    title?: string;
    placeholder?: string;
    invalid?: boolean;
    /** Card width; wide controls such as a calendar need more than the default. */
    width?: string;
    children?: Snippet;
  } = $props();

  type Phase = 'closed' | 'open' | 'leaving';

  const uid = $props.id();
  let phase = $state<Phase>('closed');
  let trigger = $state<HTMLButtonElement>();
  let card = $state<HTMLDivElement>();
  let top = $state(0);
  let left = $state(0);
  let leaveTimer: number | undefined;

  function place(): void {
    if (!trigger || !card) return;

    const rect = trigger.getBoundingClientRect();
    const width = card.offsetWidth;
    const height = card.offsetHeight;
    const gap = 12;
    const roomLeft = rect.left - gap - width;

    left =
      roomLeft >= 8 ? roomLeft : Math.max(8, window.innerWidth - width - 8);
    top = Math.min(
      Math.max(8, rect.top - 16),
      Math.max(8, window.innerHeight - height - 8),
    );
  }

  async function show(): Promise<void> {
    window.clearTimeout(leaveTimer);
    phase = 'open';
    await tick();
    place();
    card
      ?.querySelector<HTMLElement>(
        'input:not([type="hidden"]), select, textarea, button:not(.gb-pop-close)',
      )
      ?.focus({ preventScroll: true });
  }

  function hide(restoreFocus = false): void {
    if (phase !== 'open') return;

    phase = 'leaving';
    leaveTimer = window.setTimeout(() => (phase = 'closed'), 250);

    if (restoreFocus) trigger?.focus();
  }

  function onAnimationEnd(event: AnimationEvent): void {
    if (event.target === card && phase === 'leaving') {
      window.clearTimeout(leaveTimer);
      phase = 'closed';
    }
  }

  onMount(() => {
    const onPointerDown = (event: PointerEvent): void => {
      if (phase !== 'open') return;

      const target = event.target as Node;

      if (!card?.contains(target) && !trigger?.contains(target)) hide();
    };
    const reposition = (): void => {
      if (phase === 'open') place();
    };

    document.addEventListener('pointerdown', onPointerDown);
    window.addEventListener('resize', reposition);
    window.addEventListener('scroll', reposition, true);

    return () => {
      window.clearTimeout(leaveTimer);
      document.removeEventListener('pointerdown', onPointerDown);
      window.removeEventListener('resize', reposition);
      window.removeEventListener('scroll', reposition, true);
    };
  });
</script>

<div class="gb-sum-row" class:is-invalid={invalid}>
  <span class="gb-sum-label" id="{uid}-label">{label}</span>
  <button
    type="button"
    class="gb-sum-value"
    bind:this={trigger}
    aria-haspopup="dialog"
    aria-expanded={phase === 'open'}
    aria-controls="{uid}-card"
    aria-labelledby="{uid}-label {uid}-value"
    onclick={() => (phase === 'open' ? hide() : void show())}
  >
    <span id="{uid}-value">{value || placeholder}</span>
  </button>

  <div
    class="gb-pop"
    class:is-open={phase === 'open'}
    class:is-leaving={phase === 'leaving'}
    id="{uid}-card"
    role="dialog"
    aria-label={title}
    tabindex="-1"
    bind:this={card}
    style:display={phase === 'closed' ? 'none' : undefined}
    style:width="min({width}, calc(100vw - 1rem))"
    style:top="{top}px"
    style:left="{left}px"
    onanimationend={onAnimationEnd}
    onkeydown={(event) => {
      if (event.key === 'Escape') {
        event.stopPropagation();
        hide(true);
      }
    }}
  >
    <div class="gb-pop-head">
      <strong>{title}</strong>
      <button
        type="button"
        class="gb-pop-close"
        aria-label="Tutup"
        onclick={() => hide(true)}
      >
        <X size={16} aria-hidden="true" />
      </button>
    </div>
    <div class="gb-pop-body">
      {@render children?.()}
    </div>
  </div>
</div>

<style>
  .gb-sum-row {
    display: grid;
    grid-template-columns: minmax(4.5rem, 40%) 1fr;
    align-items: baseline;
    gap: 0.5rem;
    padding-block: 0.375rem;
  }

  .gb-sum-label {
    color: var(--neo-secondary-color);
  }

  .gb-sum-value {
    padding: 0;
    border: 0;
    background: none;
    color: var(--neo-link-color, var(--neo-primary));
    font: inherit;
    text-align: start;
    overflow-wrap: anywhere;
    cursor: pointer;
  }

  .gb-sum-value:hover {
    text-decoration: underline;
  }

  .is-invalid .gb-sum-value {
    color: var(--neo-danger);
  }

  .gb-pop {
    --neo-overlay-x: var(--neo-overlay-offset);
    --neo-overlay-y: 0;

    position: fixed;
    z-index: 1080;
    max-height: calc(100vh - 1rem);
    overflow-x: hidden;
    overflow-y: auto;
    padding: 1rem;
    border: 1px solid var(--neo-border-color);
    border-radius: 0.5rem;
    background: var(--neo-body-bg);
    box-shadow: 0 0.5rem 1.5rem rgb(0 0 0 / 0.2);
    transform-origin: right top;
    font-size: 0.8125rem;
  }

  .gb-pop.is-open {
    animation: overlay-anim-enter var(--neo-overlay-duration)
      var(--neo-overlay-ease) backwards;
  }

  .gb-pop.is-leaving {
    pointer-events: none;
    animation: overlay-anim-leave var(--neo-overlay-duration)
      var(--neo-overlay-ease) forwards;
  }

  .gb-pop-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.75rem;
  }

  .gb-pop-close {
    display: inline-flex;
    padding: 0.25rem;
    border: 0;
    border-radius: 0.375rem;
    background: none;
    color: inherit;
    cursor: pointer;
  }

  .gb-pop-close:hover {
    background: var(--neo-tertiary-bg);
  }

  .gb-pop-body {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }
</style>
