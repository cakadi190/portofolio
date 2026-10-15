<script lang="ts">
  import CalendarDays from '@lucide/svelte/icons/calendar-days';
  import Clock from '@lucide/svelte/icons/clock';
  import Globe from '@lucide/svelte/icons/globe';
  import MapPin from '@lucide/svelte/icons/map-pin';
  import Mic from '@lucide/svelte/icons/mic';
  import { storageUrl } from '@/lib/utils';

  export type Engagement = {
    id: number;
    title: string;
    organizer: string;
    roleLabel: string;
    formatLabel: string;
    location: string | null;
    startsAt: string;
    endsAt: string | null;
    registrationUrl: string | null;
    description: string | null;
    poster: string | null;
    isUpcoming: boolean;
  };

  /**
   * One row of the speaking list: date and status on top, title, organizer and
   * summary, a meta line (time, place), role/format tags on the right and the
   * poster as a thumbnail. Shared by the home section and `/speaking`.
   */
  let {
    engagement,
    eager = false,
    onposter,
    onzones,
  }: {
    engagement: Engagement;
    eager?: boolean;
    /** Opens the poster in a lightbox; without it the poster is not clickable. */
    onposter?: (engagement: Engagement) => void;
    /** Opens the time-zone converter for upcoming events that have a time. */
    onzones?: (engagement: Engagement) => void;
  } = $props();

  const dateFormatter = new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });

  /** Parse "YYYY-MM-DD HH:mm" as a wall-clock date, ignoring the viewer's timezone. */
  function parse(value: string): Date {
    const [date, time = '00:00'] = value.split(' ');
    const [year, month, day] = date.split('-').map(Number);
    const [hour, minute] = time.split(':').map(Number);

    return new Date(year, month - 1, day, hour, minute);
  }

  const pad = (value: number): string => String(value).padStart(2, '0');
  const clock = (date: Date): string =>
    `${pad(date.getHours())}.${pad(date.getMinutes())}`;

  const hasTime = $derived(engagement.startsAt.slice(11) !== '00:00');
  const dateLabel = $derived(dateFormatter.format(parse(engagement.startsAt)));
  const timeLabel = $derived(
    hasTime
      ? `${clock(parse(engagement.startsAt))}${
          engagement.endsAt ? ` – ${clock(parse(engagement.endsAt))}` : ''
        } WIB`
      : null,
  );
  const poster = $derived(storageUrl(engagement.poster));
</script>

<article class="speaking-item" class:is-past={!engagement.isUpcoming}>
  <div class="speaking-item-main">
    <p class="speaking-item-date">
      <CalendarDays size={15} aria-hidden="true" />
      <span>{dateLabel}</span>
      <span class="speaking-item-status">
        {engagement.isUpcoming ? 'Akan datang' : 'Selesai'}
      </span>
    </p>

    <h3 class="speaking-item-title">{engagement.title}</h3>
    <p class="speaking-item-organizer">
      <Mic size={14} aria-hidden="true" />
      {engagement.organizer}
    </p>

    {#if engagement.description}
      <p class="speaking-item-summary">{engagement.description}</p>
    {/if}

    <div class="speaking-item-foot">
      <ul class="speaking-item-meta">
        {#if timeLabel}
          <li><Clock size={14} aria-hidden="true" />{timeLabel}</li>
        {/if}
        {#if engagement.location}
          <li><MapPin size={14} aria-hidden="true" />{engagement.location}</li>
        {/if}
        {#if engagement.isUpcoming && hasTime && onzones}
          <li>
            <button type="button" onclick={() => onzones(engagement)}>
              <Globe size={14} aria-hidden="true" />Lihat waktu di zona waktu Anda
            </button>
          </li>
        {/if}
      </ul>

      <div class="speaking-item-tags">
        <span class="speaking-item-tag is-role">{engagement.roleLabel}</span>
        <span class="speaking-item-tag">{engagement.formatLabel}</span>
      </div>
    </div>

    {#if engagement.isUpcoming && engagement.registrationUrl}
      <a
        href={engagement.registrationUrl}
        target="_blank"
        rel="noopener noreferrer"
        class="btn btn-sm btn-primary align-self-start"
      >
        Daftar Sekarang
      </a>
    {/if}
  </div>

  {#if poster}
    {#if onposter}
      <button
        type="button"
        class="speaking-item-poster"
        aria-label={`Lihat poster ${engagement.title}`}
        onclick={() => onposter(engagement)}
      >
        <img
          src={poster}
          alt=""
          loading={eager ? 'eager' : 'lazy'}
          fetchpriority={eager ? 'high' : undefined}
          decoding="async"
        />
      </button>
    {:else}
      <div class="speaking-item-poster">
        <img src={poster} alt="" loading="lazy" decoding="async" />
      </div>
    {/if}
  {/if}
</article>
