<script lang="ts">
  import Plus from '@lucide/svelte/icons/plus';
  import { Link } from '@inertiajs/svelte';

  let {
    title,
    subtitle,
    createHref,
    createLabel = 'Tambah',
    onCreate,
  }: {
    title: string;
    subtitle?: string;
    createHref?: string;
    createLabel?: string;
    onCreate?: () => void;
  } = $props();
</script>

<div class="d-flex align-items-start justify-content-between gap-3 mb-4 flex-wrap">
  <div>
    <h1 class="h4 mb-1">{title}</h1>
    {#if subtitle}
      <p class="text-muted mb-0">{subtitle}</p>
    {/if}
  </div>

  {#if onCreate}
    <button
      type="button"
      class="btn btn-primary d-inline-flex align-items-center gap-2"
      onclick={onCreate}
    >
      <Plus size={16} />
      {createLabel}
    </button>
  {:else if createHref}
    <Link href={createHref} class="btn btn-primary d-inline-flex align-items-center gap-2">
      <Plus size={16} />
      {createLabel}
    </Link>
  {/if}
</div>
