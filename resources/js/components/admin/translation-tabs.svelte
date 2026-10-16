<script lang="ts">
  import Languages from '@lucide/svelte/icons/languages';
  import { canTranslate, localeOptions, t } from '@/lib/i18n';
  import type { createTranslatable } from '@/lib/translatable.svelte';

  /**
   * Language tabs of a translated editor: pick which language you are writing, see which
   * ones are still empty, and draft the active one from Indonesian automatically.
   */
  let {
    translatable,
    errors = {},
  }: {
    translatable: ReturnType<typeof createTranslatable>;
    /** Validation errors keyed by `field.locale`; a tab is flagged when its language has one. */
    errors?: Record<string, string>;
  } = $props();

  const options = $derived(localeOptions());
  const defaultLabel = $derived(options[0]?.label ?? '');
  const hasError = (code: string): boolean =>
    Object.keys(errors).some((key) => key.endsWith(`.${code}`));
</script>

<div class="tr-tabs">
  <div class="tr-list" role="tablist" aria-label={t('translate.tab_label')}>
    {#each options as option (option.code)}
      <button
        type="button"
        role="tab"
        class="tr-tab"
        class:active={translatable.locale === option.code}
        class:invalid={hasError(option.code)}
        aria-selected={translatable.locale === option.code}
        onclick={() => (translatable.locale = option.code)}
      >
        <span class="fi {option.flag} fis" aria-hidden="true"></span>
        {option.code.toUpperCase()}
        {#if !translatable.isTranslated(option.code)}
          <span class="tr-dot" title={t('translate.missing')} role="img" aria-label={t('translate.missing')}></span>
        {/if}
      </button>
    {/each}
  </div>

  {#if !translatable.isDefault && canTranslate()}
    <button
      type="button"
      class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-2"
      disabled={translatable.translating}
      onclick={() => translatable.translateFromDefault()}
    >
      <Languages size={14} aria-hidden="true" />
      {translatable.translating
        ? t('translate.working')
        : t('translate.auto_from', { language: defaultLabel })}
    </button>
  {/if}
</div>

{#if !translatable.isDefault && canTranslate()}
  <p class="tr-note">{t('translate.review_note')}</p>
{/if}

<style>
  .tr-tabs {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
  }

  .tr-list {
    display: inline-flex;
    padding: 0.1875rem;
    border: 1px solid var(--neo-border-color);
    border-radius: 0.5rem;
    background: var(--neo-tertiary-bg);
  }

  .tr-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.75rem;
    border: 0;
    border-radius: 0.375rem;
    background: transparent;
    color: var(--neo-secondary-color);
    font-size: 0.8125rem;
    font-weight: 600;
  }

  .tr-tab.active {
    background: var(--neo-body-bg);
    color: var(--neo-body-color);
    box-shadow: 0 1px 2px rgb(0 0 0 / 0.12);
  }

  .tr-tab.invalid {
    color: var(--neo-form-invalid-color, #dc3545);
  }

  .tr-dot {
    width: 0.4375rem;
    height: 0.4375rem;
    border-radius: 50%;
    background: var(--neo-warning, #f59e0b);
  }

  .tr-note {
    margin: 0 0 0.75rem;
    color: var(--neo-secondary-color);
    font-size: 0.8125rem;
  }
</style>
