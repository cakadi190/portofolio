<script lang="ts">
  import { t } from '@/lib/i18n';
  import AppHead from '@/components/app-head.svelte';
  import HeaderPage from '@/components/header-page.svelte';

  let {
    title,
    description,
    imageUrl,
    info,
  }: {
    title: string;
    description: string;
    imageUrl: string;
    info: { label: string; value: string; href?: string }[];
  } = $props();
</script>

<AppHead {title}>
  <meta name="description" content={description} />
  <meta property="og:title" content={title} />
  <meta property="og:description" content={description} />
  <meta property="og:image" content={imageUrl} />
  <meta name="robots" content="noindex" />
</AppHead>

<HeaderPage {title} subtitle={description} backTo="/about#certification" />

<div class="container py-5">
  <div class="row g-4">
    <div class="col-lg-8">
      <img src={imageUrl} alt={title} class="img-fluid rounded-4 border w-100" />
    </div>
    <div class="col-lg-4">
      <div class="card rounded-4">
        <div class="card-body">
          <h2 class="h5 mb-3">{t('certifications.informasi')}</h2>
          <dl class="mb-0">
            {#each info as row (row.label)}
              <dt class="small opacity-75">{row.label}</dt>
              <dd>
                {#if row.href}
                  <a href={row.href} target="_blank" rel="noopener">{row.value}</a>
                {:else}
                  {row.value}
                {/if}
              </dd>
            {/each}
          </dl>
        </div>
      </div>
    </div>
  </div>
</div>
