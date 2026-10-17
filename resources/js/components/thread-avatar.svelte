<script lang="ts">
  import { storageUrl } from '@/lib/utils';

  let {
    name,
    src = null,
    isStaff = false,
  }: {
    name: string;
    src?: string | null;
    isStaff?: boolean;
  } = $props();

  const imageUrl = $derived(storageUrl(src));
  /** Mid-tone hues that keep white initials readable in both themes. */
  const PALETTE = [
    '#2563eb',
    '#7c3aed',
    '#db2777',
    '#dc2626',
    '#d97706',
    '#16a34a',
    '#0d9488',
    '#0891b2',
    '#4f46e5',
    '#9333ea',
  ];

  /** Same name, same colour: derived from the name, so nothing is stored or fetched. */
  const color = $derived(
    PALETTE[
      [...name.trim().toLowerCase()].reduce(
        (hash, char) => (hash * 31 + char.charCodeAt(0)) >>> 0,
        7,
      ) % PALETTE.length
    ],
  );
  const initial = $derived(name.trim().charAt(0).toUpperCase() || '?');
</script>

<span
  class="thread-avatar"
  class:is-staff={isStaff}
  class:has-initial={!imageUrl}
  style:--thread-avatar-bg={imageUrl ? null : color}
  aria-hidden="true"
>
  {#if imageUrl}
    <img src={imageUrl} alt="" loading="lazy" />
  {:else}
    {initial}
  {/if}
</span>
