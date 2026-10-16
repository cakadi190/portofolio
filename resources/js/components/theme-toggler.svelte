<script lang="ts">
  import { t } from '@/lib/i18n';
  import Moon from '@lucide/svelte/icons/moon';
  import Sun from '@lucide/svelte/icons/sun';
  import { toggleDocumentTheme } from '@/lib/theme';

  let isDark = $state(false);

  $effect(() => {
    isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
  });

  function toggleTheme(): void {
    isDark = toggleDocumentTheme() === 'dark';
  }
</script>

<button
  type="button"
  class="btn btn-link nav-link theme-toggle-btn d-flex align-items-center justify-content-center p-2 rounded-circle"
  onclick={toggleTheme}
  data-track="theme_toggle"
  aria-label={isDark ? t('theme.to_light') : t('theme.to_dark')}
  title={t('theme.title')}
>
  {#if isDark}
    <Moon size={20} class="theme-icon text-warning" />
  {:else}
    <Sun size={20} class="theme-icon text-primary" />
  {/if}
</button>
