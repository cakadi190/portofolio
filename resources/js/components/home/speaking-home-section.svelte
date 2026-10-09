<script lang="ts">
  import ArrowRight from '@lucide/svelte/icons/arrow-right';
  import { Link } from '@inertiajs/svelte';
  import CardBlog from '@/components/card-blog.svelte';

  type Speaking = {
    id: number;
    title: string;
    organizer: string;
    roleLabel: string;
    formatLabel: string;
    location: string | null;
    startsAt: string;
    poster: string | null;
  };

  let { speakings = [] }: { speakings?: Speaking[] } = $props();

  const dateFormatter = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });

  /** Format "YYYY-MM-DD HH:mm" as a wall-clock date, ignoring the viewer's timezone. */
  function formatDate(value: string): string {
    const [year, month, day] = value.slice(0, 10).split('-').map(Number);

    return dateFormatter.format(new Date(year, month - 1, day));
  }
</script>

<section class="need-space" id="speaking-home-section">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
      <div>
        <h2 class="mb-1">Pembicara &amp; Mentoring</h2>
        <p class="mb-0 opacity-75">Acara tempat saya berbagi ilmu seputar pengembangan web.</p>
      </div>
      <Link href="/speaking" class="btn btn-outline-primary d-flex align-items-center gap-2">
        <span>Lihat Selengkapnya</span>
        <ArrowRight size={16} />
      </Link>
    </div>

    {#if speakings.length}
      <div class="row">
        {#each speakings as speaking (speaking.id)}
          <div class="col-md-6 col-lg-4 mb-4">
            <CardBlog
              href="/speaking"
              title={speaking.title}
              slug={String(speaking.id)}
              excerpt={`${speaking.organizer} · ${formatDate(speaking.startsAt)}`}
              coverImage={speaking.poster}
              categories={[{ name: speaking.roleLabel, color: null }]}
              tags={[speaking.formatLabel, ...(speaking.location ? [speaking.location] : [])]}
            />
          </div>
        {/each}
      </div>
    {:else}
      <div class="text-center opacity-75">Belum ada acara yang tersedia saat ini.</div>
    {/if}
  </div>
</section>
