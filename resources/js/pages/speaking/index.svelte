<script lang="ts">
  import { t } from '@/lib/i18n';
  import AppHead from '@/components/app-head.svelte';
  import HeaderPage from '@/components/header-page.svelte';
  import SpeakingItem, {
    type Engagement,
  } from '@/components/speaking-item.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import Lightbox from '@/components/ui/lightbox.svelte';
  import { zoneRows } from '@/lib/timezones';
  import { storageUrl } from '@/lib/utils';

  let { upcoming, past }: { upcoming: Engagement[]; past: Engagement[] } =
    $props();

  let zoneOpen = $state(false);
  let zoneEngagement = $state<Engagement | null>(null);

  const zones = $derived(
    zoneEngagement
      ? zoneRows(zoneEngagement.startsAt, zoneEngagement.endsAt)
      : [],
  );

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

<AppHead title={t('speaking.title')} />

<div id="speaking-page">
  <HeaderPage
    title={t('speaking.title')}
    subtitle={t('speaking.subtitle')}
  />

  <section class="need-space pt-0">
    <div class="container">
      {#if upcoming.length || past.length}
        <div class="speaking-list">
          {#if upcoming.length}
            <h2 class="speaking-group-title">{t('speaking.upcoming')}</h2>
            {#each upcoming as engagement, index (engagement.id)}
              <SpeakingItem
                {engagement}
                eager={index === 0}
                onposter={openPoster}
                onzones={openZones}
              />
            {/each}
          {/if}
          {#if past.length}
            <h2 class="speaking-group-title">{t('speaking.past')}</h2>
            {#each past as engagement (engagement.id)}
              <SpeakingItem {engagement} onposter={openPoster} />
            {/each}
          {/if}
        </div>
      {:else}
        <p class="opacity-75">{t('speaking.empty')}</p>
      {/if}
    </div>
  </section>
</div>

<FormModal
  bind:open={zoneOpen}
  title={t('speaking.zones.title')}
  subtitle={zoneEngagement?.title ?? ''}
>
  {#snippet children()}
    <p class="small opacity-75">{t('speaking.zones.note')}</p>
    <ul class="list-group">
      {#each zones as zone (zone.key)}
        <li
          class="list-group-item d-flex flex-column gap-1"
          class:active={zone.isDevice}
        >
          <span class="fw-semibold">
            {zone.label}
            {#if zone.isDevice}
              <span class="badge text-bg-light ms-1">{t('speaking.zones.yours')}</span>
            {/if}
          </span>
          <span>
            {zone.start}{#if zone.end}
              – {zone.end}{/if}
          </span>
        </li>
      {/each}
    </ul>
  {/snippet}
</FormModal>

<Lightbox
  bind:open={lightboxOpen}
  bind:index={lightboxIndex}
  images={posters}
/>
