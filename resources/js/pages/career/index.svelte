<script lang="ts">
  import { t, intlLocale } from '@/lib/i18n';
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
    return new Date(date).toLocaleDateString(intlLocale(), { month: 'long', year: 'numeric' });
  }
</script>

<AppHead title={t('career.title')} />

<div id="career-page">
  <HeaderPage title={t('career.heading')} subtitle={t('career.subtitle')} />

  <section class="need-space pt-0">
    <div class="container">
      <ul class="list-group list-group-flush">
        {#each careers as exp (exp.company + exp.startDate)}
          <li
            class="list-group-item py-4"
          >
            <div class="d-flex gap-4">
              <div class="text-center align-items-start align-items-lg-center">
                <Briefcase size={48} />
              </div>
              <div class="content">
                <h2 class="h5 mb-1">{exp.position}</h2>
                <small class="text-muted">{exp.company} | {exp.location}</small>
                <p class="mb-0">
                  {formatDate(exp.startDate)} - {exp.endDate ? formatDate(exp.endDate) : t('time.present')}
                </p>
              </div>
            </div>
          </li>
        {/each}
      </ul>
    </div>
  </section>
</div>
