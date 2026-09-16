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
  class="d-flex flex-column align-items-center justify-content-center gap-4 p-4 p-md-5"
  style="min-height: 100vh;"
>
  <div class="w-100" style="max-width: 24rem;">
    <div class="d-flex flex-column gap-4">
      <div class="d-flex flex-column align-items-center gap-3">
        <Link
          href={home()}
          class="d-flex flex-column align-items-center gap-2 fw-medium"
        >
          <div
            class="mb-1 d-flex align-items-center justify-content-center rounded-2"
            style="height: 2.25rem; width: 2.25rem;"
          >
            <AppLogoIcon
              class="text-body"
              style="width: 2.25rem; height: 2.25rem;"
            />
          </div>
          <span class="visually-hidden">{title}</span>
        </Link>
        <div class="d-flex flex-column gap-2 text-center">
          <h1 class="fs-3 fw-medium">{title}</h1>
          <p class="text-center small text-muted">
            {description}
          </p>
        </div>
      </div>
      {@render children?.()}
    </div>
  </div>
</div>
