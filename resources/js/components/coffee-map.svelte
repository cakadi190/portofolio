<script lang="ts">
  import 'leaflet/dist/leaflet.css';
  import L from 'leaflet';
  import markerIcon from 'leaflet/dist/images/marker-icon.png';
  import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
  import markerShadow from 'leaflet/dist/images/marker-shadow.png';
  import { onMount } from 'svelte';

  /**
   * Read-only Leaflet map showing a single coffee place marker.
   */
  let {
    latitude,
    longitude,
    label,
  }: {
    latitude: number | string;
    longitude: number | string;
    label: string;
  } = $props();

  let container: HTMLDivElement;

  onMount(() => {
    const position: L.LatLngTuple = [Number(latitude), Number(longitude)];

    const map = L.map(container, { center: position, zoom: 16, scrollWheelZoom: false });

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap',
    }).addTo(map);

    L.marker(position, {
      title: label,
      icon: L.icon({
        iconUrl: markerIcon,
        iconRetinaUrl: markerIcon2x,
        shadowUrl: markerShadow,
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        shadowSize: [41, 41],
      }),
    }).addTo(map);

    const observer = new ResizeObserver(() => map.invalidateSize());
    observer.observe(container);

    return () => {
      observer.disconnect();
      map.remove();
    };
  });
</script>

<div
  bind:this={container}
  class="border rounded-3"
  style="height:16rem;z-index:0;"
  role="application"
  aria-label={`Peta lokasi ${label}`}
></div>
