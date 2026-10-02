<script lang="ts">
  /**
   * Bootstrap-themed time picker. Renders a hidden `<input {name}>` so
   * Inertia's `<Form>` collects the value from native `FormData`.
   * The submitted value is `HH:mm`.
   */
  let {
    id,
    name,
    value = null,
    invalid = false,
    placeholder = 'Pilih jam…',
    minuteStep = 5,
  }: {
    id?: string;
    name: string;
    value?: string | null;
    invalid?: boolean;
    placeholder?: string;
    minuteStep?: number;
  } = $props();

  const pad = (n: number): string => String(n).padStart(2, '0');

  let isOpen = $state(false);
  let hour = $state<number | null>(null);
  let minute = $state<number | null>(null);
  let root: HTMLDivElement | undefined = $state();

  const hours = Array.from({ length: 24 }, (_, i) => i);
  const minutes = $derived(
    Array.from({ length: Math.ceil(60 / minuteStep) }, (_, i) => i * minuteStep),
  );

  $effect(() => {
    const match = value?.match(/^(\d{2}):(\d{2})/);

    hour = match ? Number(match[1]) : null;
    minute = match ? Number(match[2]) : null;
  });

  const submitted = $derived(
    hour === null ? '' : `${pad(hour)}:${pad(minute ?? 0)}`,
  );

  const pickHour = (h: number): void => {
    hour = h;
    minute ??= 0;
  };

  const pickMinute = (m: number): void => {
    hour ??= 0;
    minute = m;
    isOpen = false;
  };

  const clear = (): void => {
    hour = null;
    minute = null;
  };

  const handleWindowClick = (event: MouseEvent): void => {
    if (isOpen && root && !root.contains(event.target as Node)) {
      isOpen = false;
    }
  };
</script>

<svelte:window onclick={handleWindowClick} />

<div class="neo-timepicker" bind:this={root}>
  <input type="hidden" {name} value={submitted} />
  <div class="input-group">
    <input
      {id}
      type="text"
      class="form-control"
      class:is-invalid={invalid}
      readonly
      {placeholder}
      value={submitted}
      onclick={() => (isOpen = !isOpen)}
    />
    {#if hour !== null}
      <button
        type="button"
        class="btn btn-outline-secondary"
        aria-label="Hapus jam"
        onclick={clear}
      >
        &times;
      </button>
    {/if}
  </div>

  {#if isOpen}
    <div class="neo-timepicker-panel">
      <div class="neo-timepicker-col">
        {#each hours as h (h)}
          <button
            type="button"
            class="neo-timepicker-item"
            class:active={hour === h}
            onclick={() => pickHour(h)}
          >
            {pad(h)}
          </button>
        {/each}
      </div>
      <div class="neo-timepicker-col">
        {#each minutes as m (m)}
          <button
            type="button"
            class="neo-timepicker-item"
            class:active={minute === m}
            onclick={() => pickMinute(m)}
          >
            {pad(m)}
          </button>
        {/each}
      </div>
    </div>
  {/if}
</div>

<style>
  .neo-timepicker {
    position: relative;
    width: 100%;
  }

  .neo-timepicker :global(input[readonly]) {
    cursor: pointer;
    background-color: var(--neo-body-bg);
  }

  .neo-timepicker-panel {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1080;
    display: flex;
    gap: 0.25rem;
    margin-top: 0.25rem;
    padding: 0.25rem;
    background: var(--neo-body-bg);
    border: var(--neo-border-width, 1px) solid var(--neo-border-color);
    border-radius: var(--neo-border-radius, 0.5rem);
  }

  .neo-timepicker-col {
    display: flex;
    flex-direction: column;
    max-height: 12rem;
    overflow-y: auto;
  }

  .neo-timepicker-item {
    min-width: 3rem;
    padding: 0.25rem 0.5rem;
    border: 0;
    border-radius: 0.375rem;
    background: transparent;
    color: var(--neo-body-color);
    text-align: center;
  }

  .neo-timepicker-item:hover {
    background: var(--neo-tertiary-bg);
  }

  .neo-timepicker-item.active {
    background: var(--neo-primary);
    color: #fff;
  }
</style>
