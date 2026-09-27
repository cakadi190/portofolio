<script lang="ts">
  import Moon from '@lucide/svelte/icons/moon';
  import Sun from '@lucide/svelte/icons/sun';
  import { THEME_STORAGE_KEY } from '@/lib/theme';

  let isDark = $state(false);

  $effect(() => {
    isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
  });

  function toggleTheme(): void {
    const next = isDark ? 'light' : 'dark';

    document.documentElement.setAttribute('data-bs-theme', next);

    try {
      window.localStorage.setItem(THEME_STORAGE_KEY, next);
    } catch {
      // Private mode / blocked storage — theme just won't persist.
    }

    isDark = next === 'dark';
  }
</script>

<button
  type="button"
  class="btn btn-link nav-link theme-toggle-btn d-flex align-items-center justify-content-center p-2 rounded-circle"
  onclick={toggleTheme}
  aria-label={`Ubah ke mode ${isDark ? 'terang' : 'gelap'}`}
  title="Ubah Tema"
>
  {#if isDark}
    <Moon size={20} class="theme-icon text-warning" />
  {:else}
    <Sun size={20} class="theme-icon text-primary" />
  {/if}
</button>
