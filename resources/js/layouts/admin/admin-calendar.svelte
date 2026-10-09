<script lang="ts">
  import ChevronLeft from '@lucide/svelte/icons/chevron-left';
  import ChevronRight from '@lucide/svelte/icons/chevron-right';
  import { calendarEvents } from '@/wayfinder/routes/admin';

  type CalendarEvent = {
    id: string;
    type: 'post' | 'speaking';
    title: string;
    subtitle: string;
    starts_at: string;
    ends_at: string | null;
  };

  let { active = false }: { active?: boolean } = $props();

  const weekdays = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

  const pad = (value: number) => String(value).padStart(2, '0');
  const toKey = (date: Date) =>
    `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

  const longDate = new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });
  const monthTitle = new Intl.DateTimeFormat('id-ID', {
    month: 'long',
    year: 'numeric',
  });

  const today = new Date();
  const todayKey = toKey(today);

  let viewDate = $state(new Date(today.getFullYear(), today.getMonth(), 1));
  let selectedKey = $state(todayKey);
  let events = $state<CalendarEvent[]>([]);
  let upcoming = $state<CalendarEvent[]>([]);
  let loading = $state(false);
  let failed = $state(false);

  const monthKey = $derived(`${viewDate.getFullYear()}-${pad(viewDate.getMonth() + 1)}`);

  /** Days of the visible month, padded so weeks start on Monday. */
  const cells = $derived.by(() => {
    const first = new Date(viewDate.getFullYear(), viewDate.getMonth(), 1);
    const offset = (first.getDay() + 6) % 7;
    const length = new Date(viewDate.getFullYear(), viewDate.getMonth() + 1, 0).getDate();

    return [
      ...Array.from({ length: offset }, () => null),
      ...Array.from({ length }, (_, index) =>
        toKey(new Date(viewDate.getFullYear(), viewDate.getMonth(), index + 1)),
      ),
    ];
  });

  /** Event types present per day, spreading multi-day events over every day. */
  const eventsByDay = $derived.by(() => {
    const map: Record<string, CalendarEvent[]> = {};

    for (const event of events) {
      const start = new Date(`${event.starts_at.slice(0, 10)}T00:00:00`);
      const end = new Date(`${(event.ends_at ?? event.starts_at).slice(0, 10)}T00:00:00`);

      for (let day = start; day <= end; day = new Date(day.getFullYear(), day.getMonth(), day.getDate() + 1)) {
        (map[toKey(day)] ??= []).push(event);
      }
    }

    return map;
  });

  const selectedEvents = $derived(eventsByDay[selectedKey] ?? []);
  const selectedLabel = $derived(longDate.format(new Date(`${selectedKey}T00:00:00`)));

  async function load(month: string) {
    loading = true;
    failed = false;

    try {
      const response = await fetch(calendarEvents({ query: { month } }).url, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
      });

      if (!response.ok) {
        throw new Error('failed');
      }

      const payload = (await response.json()) as {
        events: CalendarEvent[];
        upcoming: CalendarEvent[];
      };

      events = payload.events;
      upcoming = payload.upcoming ?? [];
    } catch {
      events = [];
      failed = true;
    } finally {
      loading = false;
    }
  }

  $effect(() => {
    if (active) {
      load(monthKey);
    }
  });

  function shiftMonth(step: number) {
    viewDate = new Date(viewDate.getFullYear(), viewDate.getMonth() + step, 1);
  }

  function goToToday() {
    viewDate = new Date(today.getFullYear(), today.getMonth(), 1);
    selectedKey = todayKey;
  }

  const shortDate = new Intl.DateTimeFormat('id-ID', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
  });

  function jumpTo(event: CalendarEvent) {
    const key = event.starts_at.slice(0, 10);
    const date = new Date(`${key}T00:00:00`);

    viewDate = new Date(date.getFullYear(), date.getMonth(), 1);
    selectedKey = key;
  }

  function timeOf(event: CalendarEvent): string {
    return event.starts_at.slice(11, 16).replace(':', '.');
  }
</script>

<div class="admin-calendar">
  <div class="admin-calendar__main">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <button
        type="button"
        class="btn btn-sm btn-outline-secondary"
        aria-label="Bulan sebelumnya"
        onclick={() => shiftMonth(-1)}
      >
        <ChevronLeft size={16} aria-hidden="true" />
      </button>
      <div class="d-flex align-items-center gap-2">
        <strong class="text-capitalize fs-6">{monthTitle.format(viewDate)}</strong>
        <button type="button" class="btn btn-sm btn-outline-primary py-0" onclick={goToToday}>
          Hari ini
        </button>
      </div>
      <button
        type="button"
        class="btn btn-sm btn-outline-secondary"
        aria-label="Bulan berikutnya"
        onclick={() => shiftMonth(1)}
      >
        <ChevronRight size={16} aria-hidden="true" />
      </button>
    </div>

    <div class="admin-calendar__grid" role="grid" aria-label={monthTitle.format(viewDate)}>
      {#each weekdays as weekday (weekday)}
        <div class="admin-calendar__weekday" role="columnheader">{weekday}</div>
      {/each}

      {#each cells as key, index (key ?? `blank-${index}`)}
        {#if key === null}
          <div></div>
        {:else}
          {@const dayEvents = eventsByDay[key] ?? []}
          <button
            type="button"
            class="admin-calendar__day"
            class:is-today={key === todayKey}
            class:is-selected={key === selectedKey}
            aria-label={longDate.format(new Date(`${key}T00:00:00`))}
            aria-pressed={key === selectedKey}
            onclick={() => (selectedKey = key)}
          >
            <span>{Number(key.slice(8))}</span>
            <span class="admin-calendar__dots">
              {#each [...new Set(dayEvents.map((event) => event.type))] as type (type)}
                <i class={`admin-calendar__dot admin-calendar__dot--${type}`}></i>
              {/each}
            </span>
          </button>
        {/if}
      {/each}
    </div>

    <div class="admin-calendar__legend small text-body-secondary mt-3">
      <span><i class="admin-calendar__dot admin-calendar__dot--speaking"></i> Pembicara &amp; Mentoring</span>
      <span><i class="admin-calendar__dot admin-calendar__dot--post"></i> Artikel terbit</span>
    </div>
  </div>

  <aside class="admin-calendar__side">
    <div class="admin-calendar__today">
      <span class="small opacity-75">Hari ini</span>
      <strong class="fs-5">{longDate.format(today)}</strong>
    </div>

    <section aria-label="Kegiatan terdekat">
      <div class="small fw-semibold text-uppercase mb-2">📌 Kegiatan terdekat</div>
      {#if upcoming.length === 0}
        <div class="small text-body-secondary">Belum ada kegiatan mendatang.</div>
      {:else}
        <ul class="list-unstyled mb-0 d-grid gap-2">
          {#each upcoming as event (event.id)}
            <li>
              <button type="button" class="admin-calendar__pin" onclick={() => jumpTo(event)}>
                <i class={`admin-calendar__dot admin-calendar__dot--${event.type}`}></i>
                <span class="admin-calendar__pin-body">
                  <span class="admin-calendar__pin-title">{event.title}</span>
                  <span class="small text-body-secondary">
                    {shortDate.format(new Date(`${event.starts_at.slice(0, 10)}T00:00:00`))} · {timeOf(event)} WIB
                  </span>
                </span>
              </button>
            </li>
          {/each}
        </ul>
      {/if}
    </section>

    <section aria-label="Kegiatan pada tanggal terpilih">
      <div class="small fw-semibold text-uppercase mb-2">{selectedLabel}</div>

      {#if loading}
        <p class="text-body-secondary small mb-0">Memuat kegiatan…</p>
      {:else if failed}
        <p class="text-danger small mb-0">Kegiatan gagal dimuat.</p>
      {:else if selectedEvents.length === 0}
        <p class="text-body-secondary small mb-0">Belum ada kegiatan.</p>
      {:else}
        <ul class="list-unstyled mb-0 d-grid gap-2">
          {#each selectedEvents as event (event.id)}
            <li class="admin-calendar__event">
              <i class={`admin-calendar__dot admin-calendar__dot--${event.type}`}></i>
              <div class="min-w-0">
                <div class="fw-semibold">{event.title}</div>
                <div class="small text-body-secondary">{timeOf(event)} WIB · {event.subtitle}</div>
              </div>
            </li>
          {/each}
        </ul>
      {/if}
    </section>
  </aside>
</div>
