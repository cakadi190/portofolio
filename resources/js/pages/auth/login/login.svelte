<script module lang="ts">
  export const layout = {
    title: 'Masuk ke akun Anda',
    description: 'Masukkan email dan kata sandi Anda di bawah untuk masuk',
  };
</script>

<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import LoginForm from './form.svelte';
  import PasskeyLogin from '@/components/passkey-login.svelte';
  import Separator from '@/components/ui/separator.svelte';
  import TextLink from '@/components/text-link.svelte';
  import { register } from '@/wayfinder/routes';

  let {
    status = '',
    canResetPassword,
    canUsePasskeys = false,
  }: {
    status?: string;
    canResetPassword: boolean;
    canUsePasskeys?: boolean;
  } = $props();

  let loginTabs: {
    name: 'passkey' | 'password';
    label: string;
    active: boolean;
  }[] = $state([
    { name: 'passkey', label: 'Passkey', active: true },
    { name: 'password', label: 'Akun Email', active: false },
  ]);

  function selectTab(name: 'passkey' | 'password') {
    loginTabs = loginTabs.map((tab) => ({ ...tab, active: tab.name === name }));
  }
</script>

<div id="login-inner-wrapper" class="gap-3 d-flex flex-column">
  <AppHead title="Masuk" />

  {#if status}
    <div class="mb-4 text-center small fw-medium text-success">
      {status}
    </div>
  {/if}

  {#if canUsePasskeys}
    <ul
      class="nav nav-pills justify-content-center tab-login-selector"
      role="tablist"
    >
      {#each loginTabs as tab (tab.name)}
        <li class="nav-item" role="presentation">
          <button
            type="button"
            class="nav-link"
            class:active={tab.active}
            role="tab"
            aria-selected={tab.active}
            onclick={() => selectTab(tab.name)}
          >
            {tab.label}
          </button>
        </li>
      {/each}
    </ul>

    {#if loginTabs.find((tab) => tab.active)?.name === 'password'}
      <LoginForm {canResetPassword} />
    {:else}
      <PasskeyLogin />
    {/if}
  {:else}
    <LoginForm {canResetPassword} />
  {/if}

  <Separator>Atau</Separator>

  <div class="text-center small text-muted">
    Belum punya akun?
    <TextLink href={register()}>Daftar</TextLink>
  </div>
</div>

<style lang="scss">
  .tab-login-selector {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    width: 100%;
    padding: 0.25rem;
    border-radius: 99rem;
    background: rgba(var(--neo-primary-rgb), 0.05);

    .nav-item {
      flex: 1 1 0;

      .nav-link {
        width: 100%;
        border-radius: 99rem;
        text-align: center;
        color: var(--bs-secondary-color, inherit);
        background: transparent;
        transition:
          background-color 0.2s ease,
          color 0.2s ease;

        &:hover:not(.active) {
          background: rgba(var(--neo-primary-rgb), 0.1);
        }

        &.active {
          color: #fff;
          background: rgb(var(--neo-primary-rgb));
          font-weight: 600;
        }
      }
    }
  }
</style>
