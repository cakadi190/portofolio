<script lang="ts">
  import Wifi from '@lucide/svelte/icons/wifi';
  import ThumbsUp from '@lucide/svelte/icons/thumbs-up';
  import ExternalLink from '@lucide/svelte/icons/external-link';
  import CoffeeMap from '@/components/coffee-map.svelte';
  import Lightbox from '@/components/ui/lightbox.svelte';

  type Props = {
    id: number;
    name: string;
    region: string | null;
    description: string | null;
    image: string | null;
    address: string;
    mapUrl: string | null;
    opensAt: string | null;
    closesAt: string | null;
    parkFee: number | null;
    isRecommended: boolean;
    latitude: string | number | null;
    longitude: string | number | null;
    wifiProvider: string | null;
    wifiSpeed: string | null;
    wifiSpeedColor: string | null;
    priceTier: string | null;
    priceTierColor: string | null;
    facilities: string[];
    galleries: { url: string; title: string | null }[];
  };

  let {
    id,
    name,
    region,
    description,
    image,
    address,
    mapUrl,
    opensAt,
    closesAt,
    parkFee,
    isRecommended,
    latitude,
    longitude,
    wifiProvider,
    wifiSpeed,
    wifiSpeedColor,
    priceTier,
    priceTierColor,
    facilities,
    galleries,
  }: Props = $props();

  let lightboxOpen = $state(false);
  let lightboxIndex = $state(0);

  const lightboxImages = $derived(
    galleries.map((gallery, position) => ({
      url: gallery.url,
      title: gallery.title || `${name} - ${position + 1}`,
    })),
  );

  function openLightbox(position: number): void {
    lightboxIndex = position;
    lightboxOpen = true;
  }

  const hasCoordinates = $derived(latitude !== null && longitude !== null);

  // svelte-ignore state_referenced_locally
  const modalId = `coffee-place-${id}`;

  function truncate(text: string | null, length: number): string {
    if (!text) {
      return '';
    }

    return text.length > length ? `${text.slice(0, length)}…` : text;
  }

  const parkFeeLabel = $derived(
    parkFee && parkFee > 0
      ? new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(
          parkFee,
        )
      : 'Gratis',
  );
</script>

<div class="wrapper">
  <button
    type="button"
    data-bs-toggle="modal"
    data-bs-target={`#${modalId}`}
    class="card h-100 overflow-hidden rounded-4 text-start bg-transparent p-0 w-100"
  >
    <div class="rounded-3 card-img-top">
      <img loading="lazy" src={image ?? '/images/coffee-default.webp'} alt={name} />
    </div>

    <div class="card-body p-4">
      <div class="d-flex gap-2 mb-2 align-items-center justify-content-between">
        <h5 class="card-title mb-0">
          <span class="me-2">{name}</span>
          {#if isRecommended}
            <ThumbsUp size={16} class="text-warning" />
          {/if}
        </h5>
      </div>

      <div class="metadata">
        <span>{region}</span>
        <span>{opensAt ?? '-'} s/d {closesAt ?? '-'}</span>
      </div>

      <p class="card-text mb-0 text-muted">{truncate(description, 100)}</p>
    </div>
  </button>
</div>

<div class="modal fade" id={modalId} tabindex="-1" role="dialog" aria-labelledby={`label-${modalId}`} aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id={`label-${modalId}`}>{name}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="thumbnail">
          <img loading="lazy" src={image ?? '/images/coffee-default.webp'} class="rounded-3 w-100 border" alt={name} />
        </div>

        {#if isRecommended}
          <span class="badge text-bg-warning mt-3 d-inline-flex align-items-center gap-1">
            <ThumbsUp size={12} /> Direkomendasikan
          </span>
        {/if}

        {#if description}
          <p class="mt-3 mb-0">{description}</p>
        {/if}

        {#if galleries.length > 0}
          <div class="row g-2 mt-1">
            {#each galleries as gallery, position (gallery.url)}
              <div class="col-4 col-md-3">
                <button
                  type="button"
                  class="d-block w-100 p-0 border rounded-3 overflow-hidden bg-transparent"
                  aria-label={`Pratinjau ${gallery.title || `foto ${position + 1}`}`}
                  onclick={() => openLightbox(position)}
                >
                  <img
                    loading="lazy"
                    src={gallery.url}
                    alt={gallery.title ?? `${name} ${position + 1}`}
                    class="w-100"
                    style="aspect-ratio: 1 / 1; object-fit: cover;"
                  />
                </button>
              </div>
            {/each}
          </div>

          <Lightbox bind:open={lightboxOpen} bind:index={lightboxIndex} images={lightboxImages} />
        {/if}

        <div class="row mt-2 g-2">
          {#snippet info(label: string, value: string)}
            <div class="col-6 col-md-3">
              <div class="border rounded-3 px-3 py-2 h-100">
                <small class="text-muted d-block">{label}</small>
                <span class="fw-semibold">{value}</span>
              </div>
            </div>
          {/snippet}

          {@render info('Wilayah', region ?? '-')}
          {@render info('Jam Buka', `${opensAt ?? '-'} s/d ${closesAt ?? '-'}`)}
          {@render info('Parkir', parkFeeLabel)}

          <div class="col-6 col-md-3">
            <div class="border rounded-3 px-3 py-2 h-100">
              <small class="text-muted d-block">Harga</small>
              {#if priceTier}
                <span class={`badge text-bg-${priceTierColor ?? 'secondary'}`}>{priceTier}</span>
              {:else}
                <span class="fw-semibold">-</span>
              {/if}
            </div>
          </div>

          <div class="col-12">
            <div class="border rounded-3 px-3 py-2 d-flex align-items-center gap-3">
              <Wifi size={20} class={wifiSpeed ? `text-${wifiSpeedColor ?? 'secondary'}` : 'text-muted'} />
              <div class="flex-grow-1">
                <small class="text-muted d-block">Wi-Fi</small>
                <span class="fw-semibold">{wifiProvider ?? '-'}</span>
              </div>
              {#if wifiSpeed}
                <span class={`badge text-bg-${wifiSpeedColor ?? 'secondary'}`}>{wifiSpeed}</span>
              {/if}
            </div>
          </div>

          {#if facilities.length > 0}
            <div class="col-md-12">
              <div class="card card-body">
                <p class="text-muted mb-2">Fasilitas</p>
                <div class="d-flex flex-wrap gap-2">
                  {#each facilities as facility (facility)}
                    <span class="badge bg-secondary-subtle text-body border">{facility}</span>
                  {/each}
                </div>
              </div>
            </div>
          {/if}

          <div class="col-md-12">
            <div class="border rounded-3 px-3 py-2">
              <div class="d-flex gap-2 justify-content-between">
                <small class="text-muted">Alamat</small>
                {#if mapUrl}
                  <a href={mapUrl} target="_blank" rel="noopener" class="d-flex align-items-center gap-1">
                    Arahkan Saya
                    <ExternalLink size={14} />
                  </a>
                {/if}
              </div>
              <span class="fw-semibold">{address}</span>
              {#if hasCoordinates}
                <div class="mt-3">
                  <CoffeeMap latitude={latitude!} longitude={longitude!} label={name} />
                </div>
              {/if}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
