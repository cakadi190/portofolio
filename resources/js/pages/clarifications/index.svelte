<script lang="ts">
  import { t, intlLocale } from '@/lib/i18n';
  import Link from '@/components/link.svelte';
  import AppHead from '@/components/app-head.svelte';
  import VerdictBadge from '@/components/clarification/verdict-badge.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import HeaderPage from '@/components/header-page.svelte';
  import InfiniteFeed from '@/components/infinite-feed.svelte';
  import type { InfiniteList } from '@/types/pagination';
  import { index, show } from '@/wayfinder/routes/clarifications';

  type Clarification = {
    title: string;
    slug: string;
    claim: string;
    summary: string | null;
    coverImage: string | null;
    verdict: string;
    verdictLabel: string;
    verdictColor: string;
    publishedAt: string | null;
  };

  let {
    clarifications,
    verdicts,
    activeVerdict,
  }: {
    clarifications: InfiniteList<Clarification>;
    verdicts: { value: string; label: string }[];
    activeVerdict: string | null;
  } = $props();

  function formatDate(value: string | null): string {
    return value
      ? new Date(value).toLocaleDateString(intlLocale(), {
          day: 'numeric',
          month: 'long',
          year: 'numeric',
        })
      : '';
  }
</script>

<AppHead title={t('clarifications.title')} />

<div id="clarifications-page">
  <HeaderPage
    title={t('clarifications.title')}
    subtitle={t('clarifications.subtitle')}
  />

  <section class="need-space pt-0">
    <div class="container">
      <div class="d-flex flex-wrap gap-2 mb-4" aria-label={t('clarifications.filter')}>
        <Link
          href={index().url}
          class={`btn btn-sm ${activeVerdict ? 'btn-outline-secondary' : 'btn-secondary'}`}
        >
          {t('clarifications.all')}
        </Link>
        {#each verdicts as verdict (verdict.value)}
          <Link
            href={index({ query: { verdict: verdict.value } }).url}
            class={`btn btn-sm ${activeVerdict === verdict.value ? 'btn-secondary' : 'btn-outline-secondary'}`}
          >
            {verdict.label}
          </Link>
        {/each}
      </div>

      {#if clarifications.data.length === 0}
        <EmptyState
          title={t('clarifications.empty_title')}
          text={t('clarifications.empty_text')}
        />
      {:else}
        <InfiniteFeed data="clarifications">
        <div class="row">
          {#each clarifications.data as item, index (item.slug)}
            <div class="col-md-6 col-lg-4 mb-4">
              <Link
                href={show(item.slug).url}
                class="card h-100 overflow-hidden rounded-4 card-blog"
              >
                {#if item.coverImage}
                  <img
                    loading={index === 0 ? 'eager' : 'lazy'}
                    fetchpriority={index === 0 ? 'high' : undefined}
                    decoding="async"
                    src={item.coverImage}
                    class="rounded-3 card-img-top"
                    alt={item.title}
                  />
                {/if}

                <div class="card-body p-4">
                  <div class="mb-2">
                    <VerdictBadge label={item.verdictLabel} color={item.verdictColor} />
                  </div>
                  <h2 class="h5 card-title">{item.title}</h2>
                  <p class="small opacity-75 fst-italic mb-2">
                    {t('clarifications.claim', { claim: item.claim })}
                  </p>
                  {#if item.summary}
                    <div class="card-text mb-3 opacity-75">{item.summary}</div>
                  {/if}
                  <div class="small opacity-75">{formatDate(item.publishedAt)}</div>
                </div>
              </Link>
            </div>
          {/each}
        </div>
        </InfiniteFeed>
      {/if}
    </div>
  </section>
</div>
