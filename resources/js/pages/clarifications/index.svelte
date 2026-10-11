<script lang="ts">
  import { Link } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import VerdictBadge from '@/components/clarification/verdict-badge.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import HeaderPage from '@/components/header-page.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import type { Paginated } from '@/types/pagination';
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
    clarifications: Paginated<Clarification>;
    verdicts: { value: string; label: string }[];
    activeVerdict: string | null;
  } = $props();

  function formatDate(value: string | null): string {
    return value
      ? new Date(value).toLocaleDateString('id-ID', {
          day: 'numeric',
          month: 'long',
          year: 'numeric',
        })
      : '';
  }
</script>

<AppHead title="Klarifikasi" />

<div id="clarifications-page">
  <HeaderPage
    title="Klarifikasi"
    subtitle="Hak jawab saya atas kabar atau klaim yang beredar. Ditulis oleh saya sendiri sebagai pihak yang bersangkutan, bukan pemeriksaan fakta independen."
  />

  <section class="need-space pt-0">
    <div class="container">
      <div class="d-flex flex-wrap gap-2 mb-4" aria-label="Saring berdasarkan vonis">
        <Link
          href={index().url}
          class={`btn btn-sm ${activeVerdict ? 'btn-outline-secondary' : 'btn-secondary'}`}
        >
          Semua
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
          title="Belum Ada Klarifikasi"
          text="Belum ada klarifikasi yang diterbitkan."
        />
      {:else}
        <div class="row">
          {#each clarifications.data as item (item.slug)}
            <div class="col-md-6 col-lg-4 mb-4">
              <Link
                href={show(item.slug).url}
                class="card h-100 overflow-hidden rounded-4 card-blog"
              >
                {#if item.coverImage}
                  <img
                    loading="lazy"
                    src={item.coverImage}
                    class="rounded-3 card-img-top"
                    alt={item.title}
                  />
                {/if}

                <div class="card-body p-4">
                  <div class="mb-2">
                    <VerdictBadge label={item.verdictLabel} color={item.verdictColor} />
                  </div>
                  <h5 class="card-title">{item.title}</h5>
                  <p class="small opacity-75 fst-italic mb-2">
                    Klaim: “{item.claim}”
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

        <SimplePaginator
          currentPage={clarifications.current_page}
          lastPage={clarifications.last_page}
          prevPageUrl={clarifications.prev_page_url}
          nextPageUrl={clarifications.next_page_url}
        />
      {/if}
    </div>
  </section>
</div>
