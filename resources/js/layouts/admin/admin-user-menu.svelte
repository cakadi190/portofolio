<script lang="ts">
  import { router } from '@inertiajs/svelte';
  import LogOut from '@lucide/svelte/icons/log-out';
  import UserRound from '@lucide/svelte/icons/user-round';

  let {
    userName,
    userEmail,
    logoutHref = '/logout',
  }: {
    userName?: string;
    userEmail?: string;
    logoutHref?: string;
  } = $props();

  function logout(event: Event): void {
    event.preventDefault();
    router.post(logoutHref);
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
      <UserRound size={16} aria-hidden="true" />
    </div>
    <span>{userName ?? '—'}</span>
  </button>

  <ul class="dropdown-menu dropdown-menu-end">
    <li class="userinfo-badge">
      <div class="dropdown-item">
        <div class="avatar">
          <UserRound size={20} aria-hidden="true" />
        </div>
        <div class="content">
          <strong>{userName ?? '—'}</strong>
          <p class="mb-0 text-muted small">{userEmail ?? ''}</p>
        </div>
      </div>
    </li>

    <li><hr class="dropdown-divider" /></li>

    <li>
      <button
        type="button"
        class="dropdown-item dropdown-item-danger logout-actions"
        onclick={logout}
      >
        <LogOut size={16} aria-hidden="true" />
        Keluar
      </button>
    </li>
  </ul>
</li>
