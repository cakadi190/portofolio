<script lang="ts">
  import { Link } from '@inertiajs/svelte';
  import type { Snippet } from 'svelte';
  import AppLogoIcon from '@/components/app-logo-icon.svelte';
  import { home } from '@/routes';

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

<div class="auth-layout auth-layout-card">
  <div class="auth-layout-container">
    <div class="auth-layout-body">
      <div class="auth-layout-header">
        <Link href={home()} class="auth-layout-brand">
          <div class="auth-layout-logo">
            <AppLogoIcon class="auth-layout-logo-icon" />
          </div>
          <span class="visually-hidden">{title}</span>
        </Link>
        <div class="auth-layout-heading">
          <h1 class="auth-layout-title">{title}</h1>
          <p class="auth-layout-description">
            {description}
          </p>
        </div>
      </div>
      {@render children?.()}
    </div>

    <div class="auth-layout-footer">
      Hak Cipta 2026 <Link href="/">Gettix</Link>. Operated under
      <a href="https://www.batamtix.com">PT Batam Experience Indonesia</a>.
    </div>
  </div>
</div>

<style lang="scss">
  .auth-layout {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 1.5rem;
    min-height: 100vh;
    padding: 1.5rem;
    background-image: radial-gradient(
      var(--bs-border-color) 1px,
      transparent 1px
    );
    background-size: 1.5rem 1.5rem;

    @media (width >= 768px) {
      padding: 3rem;
    }

    &-container {
      width: 100%;
      max-width: 25rem;

      @media (width <= 992px) {
        max-width: 100%;
      }
    }

    &-body {
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
      padding: 1.5rem;
      background-color: var(--bs-card-bg, var(--bs-body-bg));
      background-clip: border-box;
      border: var(--bs-card-border-width, 1px) solid
        var(--bs-card-border-color, var(--bs-border-color));
      border-radius: var(--bs-border-radius-xl);
    }

    &-header {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 1rem;
    }

    &-logo {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 2.25rem;
      width: 2.25rem;
      margin-bottom: 0.25rem;
      border-radius: var(--bs-border-radius);
    }

    &-heading {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      text-align: center;
    }

    &-title {
      margin-bottom: 0;
      font-size: 1.75rem;
      font-weight: 500;
    }

    &-description {
      margin-bottom: 0;
      text-align: center;
      font-size: 0.875rem;
      color: var(--bs-secondary-color);
    }

    &-footer {
      margin-top: 1rem;
      text-align: center;
    }
  }

  :global(.auth-layout-logo-icon) {
    width: 2.25rem;
    height: 2.25rem;
    color: var(--bs-body-color);
  }

  :global(.auth-layout-brand) {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
  }
</style>
