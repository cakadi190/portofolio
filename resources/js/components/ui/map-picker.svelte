<script lang="ts">
  import 'leaflet/dist/leaflet.css';
  import L from 'leaflet';
  import markerIcon from 'leaflet/dist/images/marker-icon.png';
  import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
  import markerShadow from 'leaflet/dist/images/marker-shadow.png';
  import { onMount } from 'svelte';
  import { Field } from '@/components/ui/field';

  /**
   * Leaflet (OpenStreetMap) picker bound to two native inputs named
   * `latitude` / `longitude`, so Inertia's `<Form>` collects them from
   * FormData. Click or drag the marker to set coordinates, or type them
   * manually — the marker follows the inputs.
   */
  let {
    idPrefix,
    latitude = null,
    longitude = null,
    latitudeError,
    longitudeError,
  }: {
    idPrefix: string;
    latitude?: number | string | null;
    longitude?: number | string | null;
    latitudeError?: string;
    longitudeError?: string;
  } = $props();

  const DEFAULT_CENTER: L.LatLngTuple = [-2.5489, 118.0149];
  const DEFAULT_ZOOM = 5;
  const PLACED_ZOOM = 16;

  let lat = $state<string>(latitude === null ? '' : String(latitude));
  let lng = $state<string>(longitude === null ? '' : String(longitude));
  let container: HTMLDivElement;
  let map: L.Map | undefined;
  let marker: L.Marker | undefined;

  function parseCoordinate(raw: string, limit: number): number | null {
    if (raw.trim() === '') {
      return null;
    }

    const parsed = Number(raw);

    return Number.isFinite(parsed) && Math.abs(parsed) <= limit ? parsed : null;
  }

  function setCoordinates(latValue: number, lngValue: number): void {
    lat = latValue.toFixed(15);
    lng = lngValue.toFixed(15);
    syncMarker(false);
  }

  function syncMarker(recenter: boolean): void {
    if (!map) {
      return;
    }

    const latValue = parseCoordinate(lat, 90);
    const lngValue = parseCoordinate(lng, 180);

    if (latValue === null || lngValue === null) {
      marker?.remove();
      marker = undefined;

      return;
    }

    const position: L.LatLngTuple = [latValue, lngValue];

    if (marker) {
      marker.setLatLng(position);
    } else {
      marker = L.marker(position, { draggable: true }).addTo(map);
      marker.on('dragend', () => {
        const { lat: newLat, lng: newLng } = marker!.getLatLng();
        setCoordinates(newLat, newLng);
      });
    }

    if (recenter) {
      map.setView(position, Math.max(map.getZoom(), PLACED_ZOOM));
    }
  }

  function useCurrentLocation(): void {
    navigator.geolocation?.getCurrentPosition((position) => {
      setCoordinates(position.coords.latitude, position.coords.longitude);
      map?.setView([position.coords.latitude, position.coords.longitude], PLACED_ZOOM);
    });
  }

  onMount(() => {
    L.Marker.prototype.options.icon = L.icon({
      iconUrl: markerIcon,
      iconRetinaUrl: markerIcon2x,
      shadowUrl: markerShadow,
      iconSize: [25, 41],
      iconAnchor: [12, 41],
      shadowSize: [41, 41],
    });

    map = L.map(container, { center: DEFAULT_CENTER, zoom: DEFAULT_ZOOM });

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap',
    }).addTo(map);

    map.on('click', (event: L.LeafletMouseEvent) => {
      setCoordinates(event.latlng.lat, event.latlng.lng);
    });

    syncMarker(true);

    const observer = new ResizeObserver(() => map?.invalidateSize());
    observer.observe(container);

    return () => {
      observer.disconnect();
      map?.remove();
      map = undefined;
      marker = undefined;
    };
  });
</script>

<div class="d-flex flex-column gap-3">
  <div>
    <div
      bind:this={container}
      class="border rounded"
      style="height:16rem;z-index:0;"
      role="application"
      aria-label="Peta pemilih lokasi"
    ></div>
    <div class="d-flex justify-content-between align-items-center mt-1">
      <small class="text-muted">Klik peta atau seret penanda untuk mengatur lokasi.</small>
      <button type="button" class="btn btn-sm btn-outline-secondary" onclick={useCurrentLocation}>
        Lokasi saya
      </button>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-sm-6">
      <Field.Group>
        <Field.Label for={`${idPrefix}-latitude`}>Latitude</Field.Label>
        <Field.Input
          id={`${idPrefix}-latitude`}
          name="latitude"
          type="text"
          inputmode="decimal"
          placeholder="-6.200000000000000"
          bind:value={lat}
          oninput={() => syncMarker(true)}
          invalid={!!latitudeError}
        />
        <Field.Feedback message={latitudeError} />
      </Field.Group>
    </div>
    <div class="col-sm-6">
      <Field.Group>
        <Field.Label for={`${idPrefix}-longitude`}>Longitude</Field.Label>
        <Field.Input
          id={`${idPrefix}-longitude`}
          name="longitude"
          type="text"
          inputmode="decimal"
          placeholder="106.816666000000000"
          bind:value={lng}
          oninput={() => syncMarker(true)}
          invalid={!!longitudeError}
        />
        <Field.Feedback message={longitudeError} />
      </Field.Group>
    </div>
  </div>
</div>
