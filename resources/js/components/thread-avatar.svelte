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
  const initial = $derived(name.trim().charAt(0).toUpperCase() || '?');
</script>

<span class="thread-avatar" class:is-staff={isStaff} aria-hidden="true">
  {#if imageUrl}
    <img src={imageUrl} alt="" loading="lazy" />
  {:else}
    {initial}
  {/if}
</span>
