<script lang="ts">
  import ArrowRight from '@lucide/svelte/icons/arrow-right';
  import { Link } from '@inertiajs/svelte';
  import SpeakingItem, {
    type Engagement,
  } from '@/components/speaking-item.svelte';

  let { speakings = [] }: { speakings?: Engagement[] } = $props();
</script>

<section class="need-space" id="speaking-home-section">
  <div class="container">
    <div
      class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2"
    >
      <div>
        <h2 class="mb-1">Pembicara &amp; Mentoring</h2>
        <p class="mb-0 opacity-75">
          Acara tempat saya berbagi ilmu seputar pengembangan web.
        </p>
      </div>
      <Link
        href="/speaking"
        class="btn btn-outline-primary d-flex align-items-center gap-2"
        aria-label="Lihat Selengkapnya: Pembicara &amp; Mentoring"
      >
        <span>Lihat Selengkapnya</span>
        <ArrowRight size={16} />
      </Link>
    </div>

    {#if speakings.length}
      <div class="speaking-list">
        {#each speakings as speaking (speaking.id)}
          <SpeakingItem engagement={speaking} />
        {/each}
      </div>
    {:else}
      <div class="text-center opacity-75">
        Belum ada acara yang tersedia saat ini.
      </div>
    {/if}
  </div>
</section>
