<script lang="ts">
  import Briefcase from '@lucide/svelte/icons/briefcase';
  import AppHead from '@/components/app-head.svelte';
  import HeaderPage from '@/components/header-page.svelte';

  type Career = {
    position: string;
    company: string;
    location: string;
    startDate: string;
    endDate: string | null;
  };

  let { careers }: { careers: Career[] } = $props();

  function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
  }
</script>

<AppHead title="Karir Saya">
  <meta
    name="description"
    content="Berikut daftar riwayat karir saya yang mana saya sudah berkarir di berbagai tempat."
  />
</AppHead>

<div id="career-page">
  <HeaderPage title="Karir" subtitle="Biar gak dikira pengangguran sama orang lain." />

  <section class="need-space pt-0">
    <div class="container">
      <ul class="list-group list-group-flush">
        {#each careers as exp (exp.company + exp.startDate)}
          <li class="list-group-item py-4">
            <div class="d-flex gap-4">
              <div class="text-center align-items-start align-items-lg-center">
                <Briefcase size={48} />
              </div>
              <div class="content">
                <h5 class="mb-1">{exp.position}</h5>
                <small class="text-muted">{exp.company} | {exp.location}</small>
                <p class="mb-0">
                  {formatDate(exp.startDate)} - {exp.endDate ? formatDate(exp.endDate) : 'Sekarang'}
                </p>
              </div>
            </div>
          </li>
        {/each}
      </ul>
    </div>
  </section>
</div>
