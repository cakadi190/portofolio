<script lang="ts">
  import ArrowRight from '@lucide/svelte/icons/arrow-right';
  import Link from '@/components/link.svelte';
  import SpeakingItem, {
    type Engagement,
  } from '@/components/speaking-item.svelte';
  import { t } from '@/lib/i18n';

  let { speakings = [] }: { speakings?: Engagement[] } = $props();
</script>

<section class="need-space" id="speaking-home-section">
  <div class="container">
    <div
      class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2"
    >
      <div>
        <h2 class="mb-1">{t('home.speaking.title')}</h2>
        <p class="mb-0 opacity-75">{t('home.speaking.subtitle')}</p>
      </div>
      <Link
        href="/speaking"
        class="btn btn-outline-primary d-flex align-items-center gap-2"
        aria-label={`${t('home.more')}: ${t('home.speaking.title')}`}
      >
        <span>{t('home.more')}</span>
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
      <div class="text-center opacity-75">{t('home.speaking.empty')}</div>
    {/if}
  </div>
</section>
