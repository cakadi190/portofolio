<script lang="ts">
  import { t } from '@/lib/i18n';
  import { InfiniteScroll } from '@inertiajs/svelte';
  import type { Snippet } from 'svelte';

  /**
   * Loads the next page of an `Inertia::scroll()` prop as the visitor nears the
   * end of the list, with a spinner while it loads.
   */
  let {
    data,
    loadingText,
    children,
  }: {
    /** Name of the `Inertia::scroll()` prop. */
    data: string;
    loadingText?: string;
    children: Snippet;
  } = $props();
</script>

<InfiniteScroll {data} buffer={300}>
  {@render children()}

  {#snippet loading()}
    <div
      class="d-flex justify-content-center align-items-center gap-2 py-4 small opacity-75"
      role="status"
    >
      <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
      {loadingText ?? t('feed.loading')}
    </div>
  {/snippet}
</InfiniteScroll>
