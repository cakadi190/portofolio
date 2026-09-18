<script lang="ts">
  import AppLogoIcon from '@/components/app-logo-icon.svelte';
  import { Link } from '@inertiajs/svelte';
  import type { Snippet } from 'svelte';

  let {
    title = '',
    description = '',
    children,
  }: {
    title?: string;
    description?: string;
    children?: Snippet;
  } = $props();
</script>

<div class="auth-layout auth-layout-split">
  <div class="auth-panel-branding"></div>
  <div class="auth-panel-form">
    <div class="panel-inner">
      <div class="panel-header">
        <Link href="/">
          <AppLogoIcon height={32} />
        </Link>

        <div class="dropdown dropdown-uncaret">
          <button
            class="btn btn-outline-primary dropdown-toggle"
            type="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
          >
            <i class="fi fis fi-id rounded-pill"></i>
            <span>ID</span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <a class="dropdown-item" href="#">
                <i class="fi fis fi-id"></i>
                <span>Bahasa Indonesia</span>
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="#">
                <i class="fi fis fi-us"></i>
                <span>Bahasa Inggris</span>
              </a>
            </li>
          </ul>
        </div>
      </div>
      <div class="panel-body">
        {@render children?.()}
      </div>
      <div class="panel-footer">
        Hak Cipta 2026 <Link href="/">Gettix</Link>. Operated under
        <a href="https://www.batamtix.com">PT Batam Experience Indonesia</a>.
      </div>
    </div>
  </div>
</div>

<style lang="scss">
  $prefix: 'neo';

  .auth-layout {
    --#{$prefix}-branding-margin: 1.5rem;

    display: flex;

    > * {
      width: 50%;
      height: 100svh;
      display: flex;
      flex-direction: column;

      @media (width <= 992px) {
        width: 100%;
      }
    }

    .auth-panel-branding {
      margin: var(--#{$prefix}-branding-margin);
      height: calc(100svh - (var(--#{$prefix}-branding-margin) * 2));
      border-radius: 1rem;
      background: var(--neo-primary);

      @media (width <= 768px) {
        display: none;
      }
    }

    .auth-panel-form {
      height: 100svh;
      display: flex;
      flex-direction: column;
      padding: var(--#{$prefix}-branding-margin);

      .panel-inner {
        display: flex;
        flex-direction: column;
        max-width: 24rem;
        min-width: 24rem;
        flex: 1;
        margin-inline: auto;
        padding-block: var(--#{$prefix}-branding-margin);
        padding: 0;

        @media (width <= 768px) {
          width: 100%;
          max-width: unset;
          min-width: unset;
        }

        .panel-header {
          display: flex;
          align-items: center;
          justify-content: space-between;
          gap: 0.5rem;
          width: 100%;
        }

        .panel-body {
          justify-self: center;
          padding-block: 2.5rem;
          margin-block: auto;
        }

        .panel-footer {
          text-align: center;
        }
      }
    }
  }
</style>
