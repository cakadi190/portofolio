<script lang="ts">
  import type { Snippet } from 'svelte';

  let {
    title = '',
    brandFirst = false,
    children,
  }: {
    title?: string;
    brandFirst?: boolean;
    children?: Snippet;
  } = $props();

  const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
  const fullTitle = $derived(
    !title
      ? appName
      : brandFirst
        ? `${appName} • ${title}`
        : `${title} • ${appName}`,
  );
</script>

<svelte:head>
  <title>{fullTitle}</title>
  {@render children?.()}
</svelte:head>
