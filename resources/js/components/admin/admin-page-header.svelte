<script lang="ts">
  import Plus from '@lucide/svelte/icons/plus';
  import { Link } from '@inertiajs/svelte';

  let {
    title,
    subtitle,
    createHref,
    createLabel = 'Tambah',
  }: {
    title: string;
    subtitle?: string;
    createHref?: string;
    createLabel?: string;
  } = $props();
</script>

<div class="d-flex align-items-start justify-content-between gap-3 mb-4 flex-wrap">
  <div>
    <h1 class="h4 mb-1">{title}</h1>
    {#if subtitle}
      <p class="text-muted mb-0">{subtitle}</p>
    {/if}
  </div>

  {#if createHref}
    <Link href={createHref} class="btn btn-primary d-inline-flex align-items-center gap-2">
      <Plus size={16} />
      {createLabel}
    </Link>
  {/if}
</div>
