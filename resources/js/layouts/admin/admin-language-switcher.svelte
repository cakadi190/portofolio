<script lang="ts">
  import { router } from '@inertiajs/svelte';
  import { currentLocale, localeOptions, t } from '@/lib/i18n';
  import { update } from '@/wayfinder/routes/locale';

  const options = $derived(localeOptions());
  const current = $derived(options.find((option) => option.code === currentLocale()) ?? options[0]);

  function choose(code: string): void {
    // Reload so text computed once (menus, labels) is rebuilt in the new language.
    router.post(update.url(), { locale: code, redirect: window.location.pathname + window.location.search }, { onSuccess: () => window.location.reload() });
  }
</script>

{#if current}
  <div class="dropdown dropdown-uncaret">
    <button
      type="button"
      class="nav-link square dropdown-toggle"
      data-bs-toggle="dropdown"
      aria-expanded="false"
      aria-label={`${t('language.choose')}: ${current.label}`}
    >
      <span class="fi {current.flag} fis border" aria-hidden="true"></span>
    </button>

    <ul class="dropdown-menu dropdown-menu-end">
      {#each options as option (option.code)}
        <li>
          <button
            type="button"
            class="dropdown-item"
            class:active={current.code === option.code}
            onclick={() => choose(option.code)}
          >
            <span class="fi {option.flag} fis border" aria-hidden="true"></span>
            <span>{option.label}</span>
          </button>
        </li>
      {/each}
    </ul>
  </div>
{/if}
