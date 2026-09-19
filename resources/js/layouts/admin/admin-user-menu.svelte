<script lang="ts">
  import LogOut from '@lucide/svelte/icons/log-out';
  import AppLogoIcon from '@/components/app-logo-icon.svelte';
  import LogoutAction from '@/components/logout-action.svelte';

  let {
    userName,
    userEmail,
    logoutHref = '/logout',
  }: {
    userName?: string;
    userEmail?: string;
    logoutHref?: string;
  } = $props();
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
      <AppLogoIcon height={16} aria-hidden="true" />
    </div>
    <span>{userName ?? '—'}</span>
  </button>

  <ul class="dropdown-menu dropdown-menu-end">
    <li class="userinfo-badge">
      <div class="dropdown-item">
        <div class="avatar">
          <AppLogoIcon height={20} aria-hidden="true" />
        </div>
        <div class="content">
          <strong>{userName ?? '—'}</strong>
          <p class="mb-0">Lihat Profil</p>
        </div>
      </div>
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
