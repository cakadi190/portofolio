<script lang="ts">
  import { Link } from '@inertiajs/svelte';

  let {
    href = '/',
    class: linkClass = '',
    height = 32,
  }: {
    href?: string;
    class?: string;
    height?: number | string;
  } = $props();
</script>

<Link {href} class="d-inline-flex app-brand {linkClass}">
  <img
    class="site-logo logo-light"
    src="/images/brands/logo-color.svg"
    alt="Logo CatatanCakadi"
    height={height}
  />
  <img
    class="site-logo logo-dark"
    src="/images/brands/logo-white.svg"
    alt="Logo CatatanCakadi"
    height={height}
  />
</Link>
