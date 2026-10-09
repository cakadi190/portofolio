<script lang="ts">
  import { Link } from '@inertiajs/svelte';
  import KeyRound from '@lucide/svelte/icons/key-round';
  import LogOut from '@lucide/svelte/icons/log-out';
  import UserRound from '@lucide/svelte/icons/user-round';
  import AppLogoIcon from '@/components/app-logo-icon.svelte';
  import LogoutAction from '@/components/logout-action.svelte';
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
        <div class="avatar">
          {#if avatarUrl}
            <img
              src={avatarUrl}
              alt={userName ?? ''}
              class="w-100 rounded h-100 object-fit-cover"
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

    <li>
      <Link href="/settings/profile" class="dropdown-item">
        <UserRound size={16} aria-hidden="true" />
        Profil Saya
      </Link>
    </li>
    <li>
      <Link href="/settings/security" class="dropdown-item">
        <KeyRound size={16} aria-hidden="true" />
        Keamanan
      </Link>
    </li>

    <li><hr class="dropdown-divider" /></li>

    <li>
      <LogoutAction
        href={logoutHref}
        class="dropdown-item dropdown-item-danger logout-actions"
      >
        <LogOut size={16} aria-hidden="true" />
        Keluar
      </LogoutAction>
    </li>
  </ul>
</li>
