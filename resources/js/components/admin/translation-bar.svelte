<script lang="ts">
  import Languages from '@lucide/svelte/icons/languages';
  import { onMount } from 'svelte';
  import { localeOptions, t } from '@/lib/i18n';
  import type { createTranslatable } from '@/lib/translatable.svelte';

  /**
   * Language tabs shown above the title: `[icon] Content language [🇮🇩] [🇺🇸]`. It is
   * placed inside the editor document, so it is as wide as the title and the editor
   * (and follows the device preview width).
   */
  let { translatable }: { translatable: ReturnType<typeof createTranslatable> } = $props();

  let bar = $state<HTMLDivElement>();
  let sentinel = $state<HTMLDivElement>();
  let stuck = $state(false);

  /** Detects the sticky state (set by the editor) so the bar can turn into a floating pill. */
  onMount(() => {
    if (!bar || !sentinel || typeof IntersectionObserver === 'undefined') return;

    const style = getComputedStyle(bar);

    if (style.position !== 'sticky') return;

    const top = Math.round(parseFloat(style.top) || 0);
    const observer = new IntersectionObserver(
      ([entry]) => (stuck = !entry.isIntersecting && entry.boundingClientRect.top < top),
      { rootMargin: `-${top}px 0px 0px 0px`, threshold: 0 },
    );

    observer.observe(sentinel);

    return () => observer.disconnect();
  });

  const flags = $derived(
    Object.fromEntries(localeOptions().map((option) => [option.code, option.flag])),
  );
</script>

<div class="tr-sentinel" bind:this={sentinel} aria-hidden="true"></div>
<div class="tr-bar" class:is-stuck={stuck} bind:this={bar}>
  <span class="tr-label">
    <Languages size={18} aria-hidden="true" />
    <span class="tr-label-text">{t('content.language.label')}</span>
  </span>

  <div class="tr-tabs" role="tablist" aria-label={t('content.language.label')}>
    {#each translatable.languages as option (option.code)}
      <button
        type="button"
        role="tab"
        class="tr-tab"
        class:on={translatable.locale === option.code}
        aria-selected={translatable.locale === option.code}
        title={option.label}
        onclick={() => (translatable.locale = option.code)}
      >
        <span class="fi {flags[option.code]} fis" aria-hidden="true"></span>
        <span class="visually-hidden">{option.label}</span>
        {#if !translatable.isTranslated(option.code)}
          <span class="tr-dot" title={t('translate.missing')} role="img" aria-label={t('translate.missing')}></span>
        {/if}
      </button>
    {/each}
  </div>
</div>

<style>
  .tr-bar {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-bottom-color: var(--neo-border-color);
    border-radius: 0;
    transition:
      padding 0.3s cubic-bezier(0.4, 0, 0.2, 1),
      border-color 0.3s ease,
      border-radius 0.3s cubic-bezier(0.4, 0, 0.2, 1),
      box-shadow 0.3s ease,
      background-color 0.3s ease;
  }

  .tr-sentinel {
    height: 1px;
    margin-bottom: -1px;
    pointer-events: none;
  }

  /* Pinned under the top bar: a floating pill so content scrolling behind it stays readable. */
  .tr-bar.is-stuck {
    padding-inline: 1rem;
    border-color: var(--neo-border-color);
    border-radius: 999px;
    box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.08);
  }

  .tr-bar.is-stuck .tr-tab {
    margin-bottom: 0;
  }

  @media (prefers-reduced-motion: reduce) {
    .tr-bar,
    .tr-tab,
    .tr-tab :global(.fi) {
      transition: none;
    }
  }

  .tr-label {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    white-space: nowrap;
    color: var(--neo-secondary-color);
  }

  .tr-tabs {
    display: flex;
    flex: 1 1 auto;
    min-width: 0;
    gap: 0.25rem;
  }

  .tr-tab {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border: 0;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    background: transparent;
    color: inherit;
    font-size: 1rem;
    opacity: 0.7;
    transition:
      opacity 0.2s ease,
      border-color 0.25s ease,
      background-color 0.2s ease;
  }

  .tr-tab :global(.fi) {
    transition: transform 0.2s ease;
  }

  .tr-tab:hover :global(.fi),
  .tr-tab.on :global(.fi) {
    transform: scale(1.08);
  }

  .tr-tab:hover,
  .tr-tab:focus-visible {
    opacity: 1;
  }

  .tr-tab.on {
    border-bottom-color: var(--neo-primary, currentColor);
    opacity: 1;
  }

  .tr-dot {
    width: 0.4375rem;
    height: 0.4375rem;
    border-radius: 50%;
    background: var(--neo-warning, #f59e0b);
  }

  /* Phones: the label sits above the tabs, which share the full width. */
  @media (max-width: 575.98px) {
    .tr-bar {
      flex-direction: column;
      align-items: stretch;
      gap: 0;
    }

    .tr-bar.is-stuck {
      border-radius: 1rem;
    }

    .tr-label {
      padding-block: 0.5rem 0.25rem;
    }

    .tr-tab {
      flex: 1 1 0;
    }
  }
</style>
