<script lang="ts">
  import ThumbsUp from '@lucide/svelte/icons/thumbs-up';
  import ExternalLink from '@lucide/svelte/icons/external-link';

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
  }: Props = $props();

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

        {#if description}
          <p class="mt-3 mb-0">{description}</p>
        {/if}

        <div class="row mt-2 g-3">
          <div class="col-md-6">
            <div class="card card-body">
              <p class="text-muted mb-2">Nama Kafe / Warkop</p>
              <h5 class="mb-0">{name}</h5>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card card-body">
              <p class="text-muted mb-2">Wilayah</p>
              <h5 class="mb-0">{region ?? '-'}</h5>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card card-body">
              <p class="text-muted mb-2">Jam Buka</p>
              <h5 class="mb-0">{opensAt ?? '-'} s/d {closesAt ?? '-'}</h5>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card card-body">
              <p class="text-muted mb-2">Biaya Parkir</p>
              <h5 class="mb-0">{parkFeeLabel}</h5>
            </div>
          </div>

          <div class="col-md-12">
            <div class="card card-body">
              <div class="d-flex gap-2 justify-content-between">
                <p class="text-muted mb-2">Alamat</p>
                {#if mapUrl}
                  <a href={mapUrl} target="_blank" rel="noopener" class="d-flex align-items-center gap-1">
                    Arahkan Saya
                    <ExternalLink size={14} />
                  </a>
                {/if}
              </div>
              <h5 class="mb-0">{address}</h5>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
