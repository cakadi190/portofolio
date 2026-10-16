<script lang="ts">
  import { localeOptions, t } from '@/lib/i18n';
  import type { createTranslatable } from '@/lib/translatable.svelte';

  /**
   * Shown above the content while a non-default language is being edited: tells the writer
   * so. The language itself is
   * picked from the dropdown in the editor's top bar.
   */
  let {
    translatable,
    picker = false,
  }: {
    translatable: ReturnType<typeof createTranslatable>;
    /** Show the language dropdown here, for forms without the editor's top bar. */
    picker?: boolean;
  } = $props();

  const current = $derived(localeOptions().find((option) => option.code === translatable.locale));
</script>

{#if picker || !translatable.isDefault}
  <div class="tr-bar">
    {#if picker}
      <div class="dropdown dropdown-uncaret">
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary dropdown-toggle d-inline-flex align-items-center gap-2"
          data-bs-toggle="dropdown"
          aria-expanded="false"
          aria-label={`${t('content.language.label')}: ${t(`content.language.${translatable.locale}`)}`}
        >
          <span class="fi {current?.flag} fis" aria-hidden="true"></span>
          <span class="fw-bold">{translatable.locale.toUpperCase()}</span>
        </button>
        <ul class="dropdown-menu">
          {#each translatable.languages as option (option.code)}
            <li>
              <button
                type="button"
                class="dropdown-item"
                class:active={translatable.locale === option.code}
                onclick={() => (translatable.locale = option.code)}
              >
                {option.label}
              </button>
            </li>
          {/each}
        </ul>
      </div>
    {/if}

    {#if !translatable.isDefault}
      <span class="tr-badge">
        <span class="fi {current?.flag} fis" aria-hidden="true"></span>
        {t(`content.language.${translatable.locale}`)}
        {#if !translatable.isTranslated(translatable.locale)}
          <span class="tr-dot" title={t('translate.missing')} role="img" aria-label={t('translate.missing')}></span>
        {/if}
      </span>
    {/if}
  </div>
{/if}

<style>
  .tr-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
  }

  .tr-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.75rem;
    border: 1px solid var(--neo-border-color);
    border-radius: 999px;
    background: var(--neo-tertiary-bg);
    font-size: 0.8125rem;
    font-weight: 600;
  }

  .tr-dot {
    width: 0.4375rem;
    height: 0.4375rem;
    border-radius: 50%;
    background: var(--neo-warning, #f59e0b);
  }
</style>
