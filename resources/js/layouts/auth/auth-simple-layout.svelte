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

<div
  class="auth-layout d-flex flex-column align-items-center justify-content-center gap-4 p-4 p-md-5"
>
  <div class="auth-layout-inner w-100">
    <div class="card card-body rounded-4 p-4 d-flex flex-column gap-4">
      <div class="d-flex flex-column align-items-center gap-3">
        <Link
          href={home()}
          class="d-flex flex-column align-items-center gap-2 fw-medium"
        >
          <div
            class="auth-layout-logo mb-1 d-flex align-items-center justify-content-center rounded-2"
          >
            <AppLogoIcon class="auth-layout-logo-icon text-body" />
          </div>
          <span class="visually-hidden">{title}</span>
        </Link>
        <div class="d-flex flex-column gap-2 text-center">
          <h1 class="fs-3 mb-0 fw-medium">{title}</h1>
          <p class="text-center mb-0 small text-muted">
            {description}
          </p>
        </div>
      </div>
      {@render children?.()}
    </div>
  </div>
</div>

<style lang="scss">
  .auth-layout {
    min-height: 100vh;
    background-image: radial-gradient(
      var(--bs-border-color) 1px,
      transparent 1px
    );
    background-size: 1.5rem 1.5rem;

    &-inner {
      max-width: 24rem;
    }

    &-logo {
      height: 2.25rem;
      width: 2.25rem;
    }
  }

  :global(.auth-layout-logo-icon) {
    width: 2.25rem;
    height: 2.25rem;
  }
</style>
