<script lang="ts">
  import AppLogoIcon from '@/components/app-logo-icon.svelte';
  import LanguageSwitcher from '@/components/language-switcher.svelte';
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

        <LanguageSwitcher />
      </div>
      <div class="panel-body">
        <div class="panel-content">
          <h1 class="content-title">{title}</h1>
          <p class="content-description">{description}</p>
        </div>

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
      background-color: var(--neo-primary);
      background-image: radial-gradient(
        rgba(255, 255, 255, 0.125) 2px,
        transparent 2px
      );
      background-size: 1.5rem 1.5rem;

      @media (width <= 768px) {
        display: none;
      }
    }

    .auth-panel-form {
      height: 100svh;
      display: flex;
      flex-direction: column;
      padding-inline: var(--#{$prefix}-branding-margin);
      padding-block: calc(var(--#{$prefix}-branding-margin) * 2.5);

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

          .panel-content {
            margin-bottom: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;

            > * {
              margin-bottom: 0;
            }

            .content-title {
              font-size: clamp(1.25rem, 5vw, 1.75rem);
            }
            .content-description {
              font-size: 1rem;
              color: var(--#{$prefix}-secondary-color);
            }
          }
        }

        .panel-footer {
          text-align: center;
        }
      }
    }
  }
</style>
