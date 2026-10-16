<script lang="ts">
  import { page, router } from '@inertiajs/svelte';
  import { onMount } from 'svelte';
  import { currentLocale } from '@/lib/i18n';
  import { update } from '@/wayfinder/routes/locale';

  type Suggestion = {
    locale: string;
    url: string;
    messages: { prompt: string; accept: string; dismiss: string };
  };

  /**
   * Offers a first-time visitor the language their browser prefers. It is a dismissible bar
   * drawn only in the browser (never part of the server-rendered HTML, so it cannot affect
   * the first paint), written in the suggested language, and never redirects on its own.
   */
  const suggestion = $derived(
    (page.props as { languageSuggestion?: Suggestion | null }).languageSuggestion ?? null,
  );

  let mounted = $state(false);
  let dismissed = $state(false);

  onMount(() => {
    mounted = true;
  });

  /** Remembers the choice in the `locale` cookie, so the offer is not repeated. */
  function remember(locale: string, then?: () => void): void {
    router.post(update.url(), { locale }, { preserveScroll: true, preserveState: true, onSuccess: then });
  }

  function accept(current: Suggestion): void {
    remember(current.locale, () => window.location.assign(current.url));
  }

  function dismiss(): void {
    dismissed = true;
    remember(currentLocale());
  }
</script>

{#if mounted && suggestion && !dismissed}
  <div class="lang-suggest" role="region" aria-label={suggestion.messages.prompt} lang={suggestion.locale}>
    <span>{suggestion.messages.prompt}</span>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-sm btn-primary" onclick={() => accept(suggestion)}>
        {suggestion.messages.accept}
      </button>
      <button type="button" class="btn btn-sm btn-outline-secondary" onclick={dismiss}>
        {suggestion.messages.dismiss}
      </button>
    </div>
  </div>
{/if}

<style>
  .lang-suggest {
    position: fixed;
    inset-inline: 0.75rem;
    bottom: 0.75rem;
    z-index: 1045;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem 1rem;
    max-width: 40rem;
    margin-inline: auto;
    padding: 0.75rem 1rem;
    border: 1px solid var(--neo-border-color);
    border-radius: 1rem;
    background: var(--neo-body-bg);
    box-shadow: 0 0.5rem 1.5rem rgb(0 0 0 / 0.18);
    font-size: 0.9rem;
  }
</style>
