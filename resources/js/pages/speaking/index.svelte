<script lang="ts">
  import Icon from '@iconify/svelte';
  import AppHead from '@/components/app-head.svelte';
  import HeaderPage from '@/components/header-page.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import Lightbox from '@/components/ui/lightbox.svelte';
  import { zoneRows } from '@/lib/timezones';
  import { storageUrl } from '@/lib/utils';

  type Engagement = {
    id: number;
    title: string;
    organizer: string;
    role: string;
    roleLabel: string;
    format: string;
    formatLabel: string;
    location: string | null;
    startsAt: string;
    endsAt: string | null;
    registrationUrl: string | null;
    description: string | null;
    poster: string | null;
    isUpcoming: boolean;
  };

  let { upcoming, past }: { upcoming: Engagement[]; past: Engagement[] } =
    $props();

  const engagements = $derived([...upcoming, ...past]);

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

  function formatSchedule(engagement: Engagement): string {
    const start = parse(engagement.startsAt);
    const pad = (value: number): string => String(value).padStart(2, '0');
    const clock = (date: Date): string =>
      `${pad(date.getHours())}.${pad(date.getMinutes())}`;
    const hasTime = engagement.startsAt.slice(11) !== '00:00';
    let schedule = dateFormatter.format(start);

    if (hasTime) {
      schedule += ` · ${clock(start)}`;

      if (engagement.endsAt) {
        schedule += ` – ${clock(parse(engagement.endsAt))}`;
      }

      schedule += ' WIB';
    }

    return schedule;
  }

  let zoneOpen = $state(false);
  let zoneEngagement = $state<Engagement | null>(null);

  const zones = $derived(
    zoneEngagement
      ? zoneRows(zoneEngagement.startsAt, zoneEngagement.endsAt)
      : [],
  );

  function hasTime(engagement: Engagement): boolean {
    return engagement.startsAt.slice(11) !== '00:00';
  }

  function openZones(engagement: Engagement): void {
    zoneEngagement = engagement;
    zoneOpen = true;
  }

  let lightboxOpen = $state(false);
  let lightboxIndex = $state(0);

  const posters = $derived(
    [...upcoming, ...past]
      .filter((engagement) => engagement.poster)
      .map((engagement) => ({
        url: storageUrl(engagement.poster) ?? '',
        title: engagement.title,
      })),
  );

  function openPoster(engagement: Engagement): void {
    lightboxIndex = posters.findIndex(
      (poster) => poster.url === storageUrl(engagement.poster),
    );
    lightboxOpen = true;
  }
</script>

{#snippet card(engagement: Engagement)}
  <div class="col-md-6 col-lg-4 reveal reveal-spring reveal-bottom">
    <article
      class="card card-speaking h-100 overflow-hidden rounded-4"
      class:is-past={!engagement.isUpcoming}
    >
      {#if engagement.poster}
        <button
          type="button"
          class="card-speaking-poster btn p-0 border-0 rounded-0"
          aria-label={`Lihat poster ${engagement.title}`}
          onclick={() => openPoster(engagement)}
        >
          <img
            src={storageUrl(engagement.poster)}
            alt={`Poster ${engagement.title}`}
            class="card-img-top"
            loading="lazy"
          />
        </button>
      {/if}
      <div class="card-body p-4 d-flex flex-column gap-3">
        <div class="d-flex flex-wrap gap-2">
          <span class="badge bg-primary">{engagement.roleLabel}</span>
          {#if !engagement.isUpcoming}
            <span class="badge text-bg-secondary">Selesai</span>
          {/if}
          <span class="badge tag-badge">{engagement.formatLabel}</span>
        </div>

        <div>
          <h5 class="card-title mb-1">{engagement.title}</h5>
          <p class="mb-0 opacity-75">{engagement.organizer}</p>
        </div>

        <ul class="list-unstyled small mb-0 d-flex flex-column gap-2">
          <li class="d-flex align-items-start gap-2">
            <Icon icon="lucide:calendar" width={16} height={16} class="mt-1 flex-shrink-0" />
            <span>{formatSchedule(engagement)}</span>
          </li>
          {#if engagement.location}
            <li class="d-flex align-items-start gap-2">
              <Icon icon="lucide:map-pin" width={16} height={16} class="mt-1 flex-shrink-0" />
              <span>{engagement.location}</span>
            </li>
          {/if}
          {#if engagement.isUpcoming && hasTime(engagement)}
            <li>
              <button
                type="button"
                class="btn btn-link p-0 small text-start d-flex align-items-center gap-2"
                onclick={() => openZones(engagement)}
              >
                <Icon icon="lucide:globe" width={16} height={16} />
                Lihat waktu di zona waktu Anda
              </button>
            </li>
          {/if}
        </ul>

        {#if engagement.description}
          <p class="mb-0 small opacity-75">{engagement.description}</p>
        {/if}

        {#if engagement.isUpcoming && engagement.registrationUrl}
          <a
            href={engagement.registrationUrl}
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-primary mt-auto align-self-start"
          >
            Daftar Sekarang
          </a>
        {/if}
      </div>
    </article>
  </div>
{/snippet}

<AppHead title="Pembicara & Mentoring" />

<div id="speaking-page">
  <HeaderPage
    title="Pembicara & Mentoring"
    subtitle="Berbagi ilmu seputar pengembangan web dan teknologi"
  />

  <section class="need-space pt-0">
    <div class="container">
      <div class="row g-4">
        {#each engagements as engagement (engagement.id)}
          {@render card(engagement)}
        {:else}
          <p class="opacity-75">Belum ada acara yang terarsip.</p>
        {/each}
      </div>
    </div>
  </section>
</div>

<FormModal
  bind:open={zoneOpen}
  title="Waktu di Zona Waktu Lain"
  subtitle={zoneEngagement?.title ?? ''}
>
  {#snippet children()}
    <p class="small opacity-75">
      Jadwal resmi acara dalam WIB. Berikut padanannya di zona waktu lain agar
      Anda tidak ketinggalan.
    </p>
    <ul class="list-group">
      {#each zones as zone (zone.key)}
        <li
          class="list-group-item d-flex flex-column gap-1"
          class:active={zone.isDevice}
        >
          <span class="fw-semibold">
            {zone.label}
            {#if zone.isDevice}
              <span class="badge text-bg-light ms-1">Zona Anda</span>
            {/if}
          </span>
          <span>
            {zone.start}{#if zone.end} – {zone.end}{/if}
          </span>
        </li>
      {/each}
    </ul>
  {/snippet}
</FormModal>

<Lightbox bind:open={lightboxOpen} bind:index={lightboxIndex} images={posters} />
