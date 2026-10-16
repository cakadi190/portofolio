<script lang="ts">
  import { router } from '@inertiajs/svelte';
  import { update } from '@/wayfinder/routes/locale';
  import { currentLocale, localeAlternates, localeOptions, t } from '@/lib/i18n';

  const options = $derived(localeOptions());
  const current = $derived(options.find((option) => option.code === currentLocale()) ?? options[0]);
  const alternates = $derived(localeAlternates());

  function choose(code: string): void {
    // Reload so text computed once (menus, labels) is rebuilt in the new language.
    router.post(update.url(), { locale: code }, { onSuccess: () => window.location.reload() });
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
              class="dropdown-item"
              class:active={option.code === current.code}
              href={alternates[option.code]}
              hreflang={option.code}
              data-track="language_change"
              data-track-label={option.code}
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
