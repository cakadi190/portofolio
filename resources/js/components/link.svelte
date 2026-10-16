<script lang="ts">
  import { Link } from '@inertiajs/svelte';
  import type { ComponentProps } from 'svelte';
  import { localizeTarget } from '@/lib/i18n';

  /**
   * Drop-in replacement for Inertia's `Link` on public pages: keeps the visitor's
   * language prefix (`/blog` → `/en/blog`) in server-rendered `href`s and in navigation.
   */
  let { href = '', children, ...rest }: ComponentProps<typeof Link> = $props();

  const target = $derived(localizeTarget(href));
</script>

<Link href={target} {...rest}>
  {@render children?.()}
</Link>
