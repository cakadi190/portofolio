<script lang="ts">
  import Icon from '@iconify/svelte';
  import AppHead from '@/components/app-head.svelte';
  import HeaderPage from '@/components/header-page.svelte';

  type Award = {
    eventName: string;
    title: string;
    icon: string | null;
    year: number;
    rank: number | null;
    awardedAt: string | null;
  };

  let { awards }: { awards: Award[] } = $props();

  const rankColor: Record<number, string> = { 1: '#ffd700', 2: '#c0c0c0', 3: '#cd7f32' };

  function isNew(awardedAt: string | null): boolean {
    if (!awardedAt) {
      return false;
    }

    const diffDays = (Date.now() - new Date(awardedAt).getTime()) / (1000 * 60 * 60 * 24);

    return diffDays <= 30;
  }
</script>

<AppHead title="Penghargaan">
  <meta name="description" content="Berikut beberapa daftar penghargaan yang sudah saya raih dan capai." />
</AppHead>

<div id="achievements-page">
  <HeaderPage title="Penghargaan Saya" subtitle="Biar gak dikira gak punya pencapaian apa-apa" />

  <section class="need-space pt-0">
    <div class="container">
      <ul class="list-group list-group-flush">
        {#each awards as award (award.eventName + award.title)}
          <li class="list-group-item py-4">
            <div class="d-flex gap-3 align-items-start align-items-lg-center">
              <div class="text-center align-items-start align-items-lg-center">
                <Icon
                  icon={award.icon ?? 'fa6-solid:trophy'}
                  width={48}
                  height={48}
                  style={award.rank && rankColor[award.rank] ? `color: ${rankColor[award.rank]}` : 'opacity: .75'}
                />
              </div>
              <div class="content">
                <h5 class="mb-1">
                  {award.eventName}
                  {#if isNew(award.awardedAt)}
                    <span class="badge bg-success ms-2">Baru</span>
                  {/if}
                </h5>
                <p class="mb-0 opacity-75">{award.title}</p>
              </div>
              <div class="year ms-auto badge p-2 px-3 fw-normal lh-1 bg-primary rounded-pill" style="font-size: 1rem">
                {award.year}
              </div>
            </div>
          </li>
        {/each}
      </ul>
    </div>
  </section>
</div>
