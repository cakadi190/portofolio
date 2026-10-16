<script lang="ts">
  import { t } from '@/lib/i18n';
  import type { Snippet } from 'svelte';
  import ChevronLeft from '@lucide/svelte/icons/chevron-left';
  import Link from '@/components/link.svelte';

  let {
    title,
    subtitle,
    backTo,
    children,
  }: {
    title: string;
    subtitle: string;
    backTo?: string;
    children?: Snippet;
  } = $props();

</script>

<header class="page-masthead">
  <div class="container">
    <div class="row">
      <div class="col-xxl-6 col-lg-8">
        {#if backTo}
          <Link
            href={backTo}
            class="btn btn-link btn-navigation d-flex align-items-center gap-2"
          >
            <ChevronLeft size={16} />
            <span>{t('action.back')}</span>
          </Link>
        {/if}

        <h1>{title}</h1>
        <h2 class="h4 opacity-75 fw-normal lh-base mt-3 mb-3">{subtitle}</h2>

        {@render children?.()}
      </div>
    </div>
  </div>
</header>
