<script lang="ts">
  import type { Snippet } from 'svelte';
  import ChevronLeft from '@lucide/svelte/icons/chevron-left';
  import { Link } from '@inertiajs/svelte';

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

  const titleDelay = backTo ? '120ms' : '60ms';
  const subtitleDelay = backTo ? '180ms' : '120ms';
</script>

<header class="page-masthead">
  <div class="container">
    <div class="row">
      <div class="col-xxl-6 col-lg-8">
        {#if backTo}
          <Link
            href={backTo}
            class="btn btn-link btn-navigation d-flex align-items-center gap-2 reveal reveal-spring reveal-left"
            style="--reveal-delay: 60ms"
          >
            <ChevronLeft size={16} />
            <span>Kembali</span>
          </Link>
        {/if}

        <h1 class="reveal reveal-spring reveal-left" style={`--reveal-delay: ${titleDelay}`}>{title}</h1>
        <h2
          class="h4 opacity-75 fw-normal lh-base mb-0 reveal reveal-spring reveal-left"
          style={`--reveal-delay: ${subtitleDelay}`}
        >
          {subtitle}
        </h2>

        {@render children?.()}
      </div>
    </div>
  </div>
</header>
