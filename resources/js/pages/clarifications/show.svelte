<script lang="ts">
  import Calendar from '@lucide/svelte/icons/calendar';
  import ExternalLink from '@lucide/svelte/icons/external-link';
  import AppHead from '@/components/app-head.svelte';
  import VerdictBadge from '@/components/clarification/verdict-badge.svelte';
  import HeaderPage from '@/components/header-page.svelte';
  import { highlightCode } from '@/lib/highlight/action';

  type Clarification = {
    slug: string;
    title: string;
    claim: string;
    claimOrigin: string | null;
    verdict: string;
    verdictLabel: string;
    verdictColor: string;
    summary: string | null;
    content: string;
    apology: string | null;
    coverImage: string | null;
    sources: { title: string; url: string }[];
    updates: { date: string; note: string }[];
    publishedAt: string | null;
    updatedAt: string | null;
  };

  let { clarification }: { clarification: Clarification } = $props();

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

<AppHead title={clarification.title} />

<div id="clarification-detail">
  <HeaderPage
    backTo="/clarifications"
    title={clarification.title}
    subtitle={clarification.summary ?? formatDate(clarification.publishedAt)}
  />

  <section class="need-space pt-0">
    <div class="container">
      <div class="row">
        <div class="col-lg-9">
          {#if clarification.coverImage}
            <img
              src={clarification.coverImage}
              class="w-100 rounded-4 border overflow-hidden mt-4 mt-md-0 mb-4"
              alt={clarification.title}
            />
          {/if}

          <div class="d-flex flex-wrap align-items-center gap-3 mb-4 opacity-75">
            <div class="d-flex align-items-center gap-2">
              <Calendar size={16} />
              <span>Terbit {formatDate(clarification.publishedAt)}</span>
            </div>
            {#if clarification.updates.length}
              <span>Diperbarui {formatDate(clarification.updates[0].date)}</span>
            {/if}
          </div>

          <div class={`card border-${clarification.verdictColor} rounded-4 mb-4`}>
            <div class="card-body p-4">
              <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <span class="fw-semibold">Vonis:</span>
                <VerdictBadge
                  label={clarification.verdictLabel}
                  color={clarification.verdictColor}
                />
              </div>
              <h2 class="h6 text-uppercase opacity-75">Klaim yang beredar</h2>
              <blockquote class="mb-2 fs-5">“{clarification.claim}”</blockquote>
              {#if clarification.claimOrigin}
                <div class="small opacity-75">Asal klaim: {clarification.claimOrigin}</div>
              {/if}
            </div>
          </div>

          <div class="wysiwyg-content-wrapper" use:highlightCode={clarification.content}>
            <!-- eslint-disable-next-line svelte/no-at-html-tags -->
            {@html clarification.content}
          </div>

          {#if clarification.apology}
            <div class="card rounded-4 mt-5">
              <div class="card-header p-4">
                <h2 class="h5 mb-0">Permintaan Maaf</h2>
              </div>
              <div class="card-body p-4" style="white-space: pre-line">
                {clarification.apology}
              </div>
            </div>
          {/if}

          {#if clarification.sources.length}
            <div class="card rounded-4 mt-5">
              <div class="card-header p-4">
                <h2 class="h5 mb-0">Bukti dan Sumber</h2>
              </div>
              <ul class="list-group list-group-flush">
                {#each clarification.sources as source (source.url)}
                  <li class="list-group-item p-3 px-4">
                    <a
                      href={source.url}
                      target="_blank"
                      rel="noopener noreferrer nofollow"
                      class="d-inline-flex align-items-center gap-2"
                    >
                      {source.title}
                      <ExternalLink size={14} />
                    </a>
                  </li>
                {/each}
              </ul>
            </div>
          {/if}

          {#if clarification.updates.length}
            <div class="card rounded-4 mt-5">
              <div class="card-header p-4">
                <h2 class="h5 mb-0">Riwayat Pembaruan</h2>
              </div>
              <ul class="list-group list-group-flush">
                {#each clarification.updates as update (update.date + update.note)}
                  <li class="list-group-item p-3 px-4">
                    <div class="small opacity-75">{formatDate(update.date)}</div>
                    <div style="white-space: pre-line">{update.note}</div>
                  </li>
                {/each}
              </ul>
            </div>
          {/if}

          <p class="small opacity-75 mt-5 mb-0">
            Halaman ini adalah klarifikasi dari pemilik situs sebagai pihak yang
            bersangkutan, bukan pemeriksaan fakta independen. Bila Anda menemukan
            kekeliruan, silakan <a href="/contact">hubungi saya</a>.
          </p>
        </div>
      </div>
    </div>
  </section>
</div>
