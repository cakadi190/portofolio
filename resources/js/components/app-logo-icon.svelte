<script lang="ts">
  import iconColor from '../../images/brands/icon-color.svg?no-inline';
  import iconWhite from '../../images/brands/icon-white.svg?no-inline';

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
    src={iconColor}
    alt="Logo Catatan Cak Adi"
    {height}
    style="aspect-ratio: 1"
    decoding="sync"
  />
  <img
    class="site-logo logo-dark"
    src={iconWhite}
    alt="Logo Catatan Cak Adi"
    {height}
    style="aspect-ratio: 1"
    decoding="sync"
  />
</span>
