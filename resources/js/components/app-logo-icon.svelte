<script lang="ts">
  let {
    class: className = '',
    height = 32,
    ...rest
  }: {
    class?: string;
    height?: number | string;
    [key: string]: unknown;
  } = $props();
</script>

<span class="d-inline-flex app-logo-icon {className}" {...rest}>
  <img
    class="site-logo logo-light"
    src="/images/brands/icon-color.svg"
    alt="Logo Catatan Cak Adi"
    {height}
    style="aspect-ratio: 1"
    decoding="sync"
  />
  <img
    class="site-logo logo-dark"
    src="/images/brands/icon-white.svg"
    alt="Logo Catatan Cak Adi"
    {height}
    style="aspect-ratio: 1"
    decoding="sync"
  />
</span>
