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

        <div class="dropdown">
          <button
            class="btn btn-outline-primary dropdown-toggle"
            type="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
          >
            Bahasa Indonesia
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="#">Action</a></li>
            <li><a class="dropdown-item" href="#">Another action</a></li>
            <li><a class="dropdown-item" href="#">Something else here</a></li>
          </ul>
        </div>
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
    }

    .auth-panel-form {
      height: calc(100svh - var(--#{$prefix}-branding-margin));
      display: flex;
      flex-direction: column;

      .panel-inner {
        display: flex;
        flex-direction: column;
        max-width: 24rem;
        margin-inline: auto;
        padding-block: var(--#{$prefix}-branding-margin);

        .panel-header {
          display: flex;
          align-items: center;
          justify-content: space-between;
          gap: 0.5rem;
          width: 100%;
        }
      }
    }
  }
</style>
