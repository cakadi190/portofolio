<script lang="ts">
  import { page, router } from '@inertiajs/svelte';
  import { update } from '@/wayfinder/routes/locale';
  import {
    currentLocale,
    localeAlternates,
    localeOptions,
    localizeHref,
    t,
    urlLocale,
  } from '@/lib/i18n';

  const options = $derived(localeOptions());
  const current = $derived(
    options.find((option) => option.code === currentLocale()) ?? options[0],
  );
  const alternates = $derived(localeAlternates());

  /**
   * The page being viewed in another language, built from the live URL so it never
   * lags behind client-side navigation (`/id/about` → `/en/about`, never `/en`).
   */
  function hrefFor(code: string): string {
    const url = page.url ?? '/';
    const prefix = `/${urlLocale()}`;
    const hasPrefix =
      url === prefix || /^\/[^/?#]+(?=[/?#]|$)/.exec(url)?.[0] === prefix;
    const rest = hasPrefix ? url.slice(prefix.length) : url;
    const unprefixed =
      rest === '' || rest[0] === '?' || rest[0] === '#' ? `/${rest}` : rest;

    return localizeHref(unprefixed, code);
  }

  function visit(event: MouseEvent, code: string): void {
    if (
      event.metaKey ||
      event.ctrlKey ||
      event.shiftKey ||
      event.altKey ||
      event.button !== 0
    ) {
      return;
    }

    event.preventDefault();
    router.visit(hrefFor(code));
  }

  function choose(code: string): void {
    // Reload so text computed once (menus, labels) is rebuilt in the new language.
    router.post(
      update.url(),
      {
        locale: code,
        redirect: window.location.pathname + window.location.search,
      },
      { onSuccess: () => window.location.reload() },
    );
  }
</script>

{#if current}
  <div class="dropdown dropdown-uncaret">
    <button
      class="btn btn-outline-primary dropdown-toggle"
      type="button"
      data-bs-toggle="dropdown"
      aria-expanded="false"
      aria-label={`${t('language.choose')}: ${current.label}`}
    >
      <i class="fi fis {current.flag} rounded-pill"></i>
      <span>{current.code.toUpperCase()}</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
      {#each options as option (option.code)}
        <li>
          {#if alternates}
            <a
              class="dropdown-item {option.code === current.code
                ? 'active'
                : ''}"
              href={hrefFor(option.code)}
              hreflang={option.code}
              data-track="language_change"
              data-track-label={option.code}
              onclick={(event) => visit(event, option.code)}
            >
              <i class="fi fis {option.flag}"></i>
              <span>{option.label}</span>
            </a>
          {:else}
            <button
              type="button"
              class="dropdown-item"
              class:active={option.code === current.code}
              data-track="language_change"
              data-track-label={option.code}
              onclick={() => choose(option.code)}
            >
              <i class="fi fis {option.flag}"></i>
              <span>{option.label}</span>
            </button>
          {/if}
        </li>
      {/each}
    </ul>
  </div>
{/if}
