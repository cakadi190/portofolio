<script lang="ts">
  import { t } from '@/lib/i18n';
  import { DatePicker } from '@svelte-plugins/datepicker';

  const monthLabels = [
    t('ui.januari'),
    t('ui.februari'),
    t('ui.maret'),
    t('ui.april'),
    t('ui.mei'),
    t('ui.juni'),
    t('ui.juli'),
    t('ui.agustus'),
    t('ui.september'),
    t('ui.oktober'),
    t('ui.november'),
    t('ui.desember'),
  ];

  /**
   * Bootstrap-themed date / datetime picker built on @svelte-plugins/datepicker.
   * Renders a hidden `<input {name}>` so Inertia's `<Form>` collects the value
   * from native `FormData`. The submitted value is `YYYY-MM-DD`, or
   * `YYYY-MM-DDTHH:mm` when `withTime` is set.
   */
  let {
    id,
    name,
    value = null,
    withTime = false,
    invalid = false,
    placeholder = t('ui.pilih_tanggal'),
    inline = false,
    display = $bindable(''),
    current = $bindable(''),
  }: {
    id?: string;
    name: string;
    value?: string | null;
    withTime?: boolean;
    invalid?: boolean;
    placeholder?: string;
    /** Shows the calendar permanently (no text field), for use inside a panel. */
    inline?: boolean;
    /** Human-readable value, e.g. `13 Oktober 2026, 06:11`, for a summary label. */
    display?: string;
    /** The submitted value (`YYYY-MM-DDTHH:mm`), kept in sync for the caller. */
    current?: string;
  } = $props();

  // An inline calendar is always on screen, so the library must see it as open.
  // svelte-ignore state_referenced_locally
  let isOpen = $state(inline);
  let startDate = $state<number | null>(null);
  let startDateTime = $state('00:00');

  const pad = (n: number): string => String(n).padStart(2, '0');

  $effect(() => {
    const match = value?.match(
      /^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2}))?/,
    );

    if (!match) {
      startDate = null;
      startDateTime = '00:00';

      return;
    }

    startDate = new Date(
      Number(match[1]),
      Number(match[2]) - 1,
      Number(match[3]),
    ).getTime();
    startDateTime = `${match[4] ?? '00'}:${match[5] ?? '00'}`;
  });

  const submitted = $derived.by((): string => {
    if (startDate === null) {
      return '';
    }

    const date = new Date(startDate);
    const day = `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

    return withTime ? `${day}T${startDateTime}` : day;
  });

  const shown = $derived.by((): string => {
    if (startDate === null) {
      return '';
    }

    const date = new Date(startDate);
    const day = `${date.getDate()} ${monthLabels[date.getMonth()]} ${date.getFullYear()}`;

    return withTime ? `${day}, ${startDateTime}` : day;
  });

  $effect(() => {
    display = shown;
    current = submitted;
  });

  const clear = (): void => {
    startDate = null;
    startDateTime = '00:00';
  };
</script>

<div class="neo-datepicker" class:is-invalid={invalid} class:is-inline={inline}>
  <input type="hidden" {name} value={submitted} />
  <DatePicker
    bind:isOpen
    alwaysShow={inline}
    bind:startDate
    bind:startDateTime
    showTimePicker={withTime}
    enableFutureDates
    startOfWeek={1}
    dowLabels={[t('ui.min'), t('ui.sen'), t('ui.sel'), t('ui.rab'), t('ui.kam'), t('ui.jum'), t('ui.sab')]}
    {monthLabels}
    includeFont={false}
    onDateChange={() => {
      if (!withTime && !inline) {
        isOpen = false;
      }
    }}
  >
    <div class="input-group" class:d-none={inline}>
      <input
        {id}
        type="text"
        class="form-control"
        class:is-invalid={invalid}
        readonly
        {placeholder}
        value={shown}
        onclick={() => (isOpen = !isOpen)}
      />
      {#if startDate !== null}
        <button
          type="button"
          class="btn btn-outline-secondary"
          aria-label={t('ui.hapus_tanggal')}
          onclick={clear}
        >
          &times;
        </button>
      {/if}
    </div>
  </DatePicker>
</div>

<style>
  .neo-datepicker {
    --datepicker-container-background: var(--neo-body-bg);
    --datepicker-container-border: var(--neo-border-width, 1px) solid
      var(--neo-border-color);
    --datepicker-container-border-radius: var(--neo-border-radius, 0.5rem);
    --datepicker-container-box-shadow: none;
    --datepicker-container-zindex: 1080;
    --datepicker-color: var(--neo-body-color);
    --datepicker-font-family: inherit;
    --datepicker-container-font-family: inherit;
    --datepicker-calendar-day-color: var(--neo-body-color);
    --datepicker-calendar-header-color: var(--neo-body-color);
    --datepicker-calendar-header-text-color: var(--neo-body-color);
    --datepicker-calendar-dow-color: var(--neo-secondary-color);
    --datepicker-calendar-day-background-hover: var(--neo-tertiary-bg);
    --datepicker-calendar-day-color-hover: var(--neo-body-color);
    --datepicker-calendar-range-selected-background: var(--neo-primary);
    --datepicker-calendar-range-selected-color: #fff;
    --datepicker-calendar-range-start-end-background: var(--neo-primary);
    --datepicker-calendar-range-start-end-color: #fff;
    --datepicker-calendar-header-month-nav-background-hover: var(
      --neo-tertiary-bg
    );
    --datepicker-calendar-today-border: var(--neo-border-width, 1px) solid
      var(--neo-primary);
    --datepicker-timepicker-input-border: var(--neo-border-width, 1px) solid
      var(--neo-border-color);
    --datepicker-calendar-padding: 0.25rem 0.5rem 0.75rem;
    --datepicker-calendar-width: 100%;
    --datepicker-calendar-header-margin: 0 0 0.5rem;
    --datepicker-calendar-header-padding: 0.25rem 0;
    --datepicker-calendar-header-font-size: 0.95rem;
    --datepicker-calendar-header-month-nav-border-radius: 999px;
    --datepicker-calendar-header-month-nav-margin-left: 0;
    --datepicker-calendar-header-month-nav-height: 30px;
    --datepicker-calendar-header-month-nav-width: 30px;
    --datepicker-calendar-dow-font-size: 0.75rem;
    --datepicker-calendar-dow-margin-bottom: 0.35rem;
    --datepicker-calendar-day-height: 34px;
    --datepicker-calendar-day-width: 34px;
    --datepicker-calendar-day-padding: 0;
    --datepicker-calendar-day-font-size: 0.85rem;
    --datepicker-calendar-day-margin-bottom: 2px;
    --datepicker-calendar-day-color-disabled: var(--neo-secondary-color);
    --datepicker-calendar-day-other-color: var(--neo-tertiary-color, #9ca3af);
    --datepicker-timepicker-container-margin-bottom: 0.5rem;
    --datepicker-timepicker-input-border-radius: 999px;
    --datepicker-timepicker-input-padding: 0.25rem 0.75rem;
    width: 100%;
  }

  .neo-datepicker :global(.datepicker) {
    width: 100%;
  }

  .neo-datepicker.is-inline {
    --datepicker-container-position: static;
    --datepicker-container-border: 0;
    --datepicker-container-border-radius: 0;
    --datepicker-container-top: auto;
    --datepicker-container-width: 100%;
  }

  .neo-datepicker.is-inline :global(.calendars-container) {
    animation: none;
  }

  .neo-datepicker :global(.calendar) {
    margin-inline: auto;
  }

  .neo-datepicker :global(input[type='time']) {
    background: var(--neo-tertiary-bg);
    color: var(--neo-body-color);
    font-variant-numeric: tabular-nums;
  }

  .neo-datepicker :global(input[readonly]) {
    cursor: pointer;
    background-color: var(--neo-body-bg);
  }
</style>
