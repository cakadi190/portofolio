<script lang="ts">
  import { t } from '@/lib/i18n';
  import { Link } from '@inertiajs/svelte';
  import KeyRound from '@lucide/svelte/icons/key-round';
  import LogOut from '@lucide/svelte/icons/log-out';
  import Moon from '@lucide/svelte/icons/moon';
  import Sun from '@lucide/svelte/icons/sun';
  import UserRound from '@lucide/svelte/icons/user-round';
  import AppLogoIcon from '@/components/app-logo-icon.svelte';
  import LogoutAction from '@/components/logout-action.svelte';
  import { setDocumentTheme, type Theme } from '@/lib/theme';
  import { storageUrl } from '@/lib/utils';

  let {
    userName,
    userEmail,
    userAvatar,
    logoutHref = '/logout',
  }: {
    userName?: string;
    userEmail?: string;
    userAvatar?: string | null;
    logoutHref?: string;
  } = $props();

  const avatarUrl = $derived(storageUrl(userAvatar));

  const locales = [
    { code: 'id', flag: 'fi-id', label: 'ID' },
    { code: 'en', flag: 'fi-us', label: 'EN' },
  ] as const;

  let currentLocale = $state<(typeof locales)[number]['code']>('id');
  let theme = $state<Theme>('light');

  $effect(() => {
    theme =
      document.documentElement.getAttribute('data-bs-theme') === 'dark'
        ? 'dark'
        : 'light';
  });

  function chooseTheme(next: Theme): void {
    theme = setDocumentTheme(next);
  }
</script>

<li class="nav-item dropdown userinfo">
  <button
    type="button"
    class="nav-link dropdown-toggle"
    data-bs-toggle="dropdown"
    data-bs-auto-close="outside"
    aria-expanded="false"
  >
    <div class="avatar">
      {#if avatarUrl}
        <img
          src={avatarUrl}
          alt={userName ?? ''}
          class="w-100 h-100 object-fit-cover"
        />
      {:else}
        <AppLogoIcon height={16} aria-hidden="true" />
      {/if}
    </div>
    <span>{userName ?? '—'}</span>
  </button>

  <ul class="dropdown-menu dropdown-menu-end">
    <li class="userinfo-badge">
      <Link href="/settings/profile" class="dropdown-item">
        <div class="avatar" class:avatar-fallback={!avatarUrl}>
          {#if avatarUrl}
            <img
              src={avatarUrl}
              alt={userName ?? ''}
              class="w-100 rounded-circle h-100 object-fit-cover"
            />
          {:else}
            <AppLogoIcon height={20} aria-hidden="true" />
          {/if}
        </div>
        <div class="content">
          <strong>{userName ?? '—'}</strong>
          <p class="mb-0 text-muted">{userEmail}</p>
        </div>
      </Link>
    </li>

    <li><hr class="dropdown-divider" /></li>

    <li class="userinfo-segment" role="group" aria-label={t('admin.tema')}>
      <button
        type="button"
        class:active={theme === 'light'}
        aria-label={t('admin.mode_terang')}
        aria-pressed={theme === 'light'}
        data-track="theme_toggle"
        onclick={() => chooseTheme('light')}
      >
        <Sun size={16} aria-hidden="true" />
      </button>
      <button
        type="button"
        class:active={theme === 'dark'}
        aria-label={t('admin.mode_gelap')}
        aria-pressed={theme === 'dark'}
        data-track="theme_toggle"
        onclick={() => chooseTheme('dark')}
      >
        <Moon size={16} aria-hidden="true" />
      </button>
    </li>

    <li class="userinfo-segment" role="group" aria-label={t('admin.bahasa')}>
      {#each locales as locale (locale.code)}
        <button
          type="button"
          class:active={currentLocale === locale.code}
          aria-pressed={currentLocale === locale.code}
          onclick={() => (currentLocale = locale.code)}
        >
          <span class="fi {locale.flag} fis border" aria-hidden="true"></span>
          {locale.label}
        </button>
      {/each}
    </li>

    <li><hr class="dropdown-divider" /></li>

    <li>
      <Link href="/settings/profile" class="dropdown-item">
        <UserRound size={16} aria-hidden="true" />
        {t('admin.profil_saya')}
      </Link>
    </li>
    <li>
      <Link href="/settings/security" class="dropdown-item">
        <KeyRound size={16} aria-hidden="true" />
        {t('admin.keamanan')}
      </Link>
    </li>

    <li><hr class="dropdown-divider" /></li>

    <li>
      <LogoutAction
        href={logoutHref}
        class="dropdown-item dropdown-item-danger logout-actions"
      >
        <LogOut size={16} aria-hidden="true" />
        {t('admin.keluar')}
      </LogoutAction>
    </li>
  </ul>
</li>
