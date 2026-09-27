<script lang="ts">
  import Clock from '@lucide/svelte/icons/clock';
  import GraduationCap from '@lucide/svelte/icons/graduation-cap';
  import CircleCheck from '@lucide/svelte/icons/circle-check';
  import AppHead from '@/components/app-head.svelte';
  import HeaderPage from '@/components/header-page.svelte';

  type EducationItem = {
    name: string;
    logo: string | null;
    website: string | null;
    level: string;
    grade: string | null;
    department: string | null;
    studyProgram: string | null;
    startDate: string;
    endDate: string | null;
    place: string;
    academicScore: { label: string; value: number; scale: number } | null;
  };

  type OrganizationItem = {
    name: string;
    description: string | null;
    startDate: string;
    endDate: string | null;
  };

  let { educations, organizations }: { educations: EducationItem[]; organizations: OrganizationItem[] } = $props();

  let activeTab = $state<'education' | 'organization'>('education');

  function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
  }

  function formatYear(date: string): string {
    return new Date(date).getFullYear().toString();
  }

  function hostname(url: string): string {
    try {
      return new URL(url).hostname.replace(/^www\./, '');
    } catch {
      return url;
    }
  }
</script>

<AppHead title="Pendidikan dan Organisasi">
  <meta
    name="description"
    content="Daftar riwayat pendidikan saya, yang mana saya tampilkan daftar tempat saya bersekolah dan menempuh pendidikan. Serta saya telah mengikuti kegiatan apa saja."
  />
</AppHead>

<div id="education">
  <HeaderPage
    title="Riwayat Pendidikan dan Organisasi"
    subtitle="Biar gak bingung saya ini alumni mana aja dan atau lagi sekolah dimana?"
  />

  <section class="need-space pt-0">
    <div class="container">
      <div class="row flex-lg-row flex-column-reverse gy-4">
        <div class="col-md-8">
          {#if activeTab === 'education'}
            <div class="list-group list-group-flush">
              {#each educations as item (item.name + item.startDate)}
                <div class="list-group-item py-4">
                  <div class="row align-items-start align-items-md-center">
                    <div class="col-4 col-md-3">
                      <div class="bg-white rounded-circle p-3">
                        <img
                          loading="lazy"
                          src={item.logo ?? '/images/education/default.png'}
                          alt={item.logo ? item.name : 'KEMENDIKBUD'}
                          class="w-100 ratio ratio-1x1"
                        />
                      </div>
                    </div>
                    <div class="col-8 col-md-9">
                      <div class="card-body">
                        <h3 class="card-title mb-2">{item.name}</h3>
                        <div class="card-text flex-wrap align-items-center d-flex gap-2 mb-3">
                          {#if item.department}
                            <span>
                              {item.department}{item.studyProgram ? ` / ${item.studyProgram}` : ''} &bullet;
                            </span>
                          {/if}
                          {#if item.website}
                            <a class="text-decoration-none" href={item.website} target="_blank" rel="noopener">
                              {hostname(item.website)}
                            </a>
                            <span>&bullet;</span>
                          {/if}
                          {#if item.grade}
                            <span>{item.grade}</span>
                            <span>&bullet;</span>
                          {/if}
                          <span class={`badge ${item.endDate ? 'bg-success' : 'bg-secondary'}`}>
                            {item.endDate ? 'Lulus' : 'Sedang Menempuh Pendidikan'}
                          </span>
                        </div>
                        <p class="card-text mb-2">{item.place}</p>
                        {#if item.academicScore}
                          <div class="d-flex gap-2 align-items-center mb-2">
                            <GraduationCap size={16} />
                            <div class="card-text mb-0">
                              {item.academicScore.label}:
                              <span class="fw-bold">{Number(item.academicScore.value).toFixed(2)}</span>
                              / {Number(item.academicScore.scale).toFixed(2)}
                            </div>
                          </div>
                        {/if}
                        <div class="d-flex gap-2 align-items-center mb-0">
                          <Clock size={16} />
                          <span>
                            {formatDate(item.startDate)} s/d
                            {item.endDate ? formatDate(item.endDate) : 'Sekarang'}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              {/each}
            </div>
          {:else}
            <ul class="list-group list-group-flush">
              {#each organizations as org (org.name + org.startDate)}
                <li class="list-group-item d-flex flex-column gap-3 py-4">
                  <div class="d-flex gap-3 align-items-start align-items-md-center">
                    <CircleCheck size={48} />
                    <div class="content">
                      <h3>{org.name}</h3>
                      {#if org.description}
                        <p class="mb-0">{org.description}</p>
                      {/if}
                      <p class="mb-0">
                        {formatYear(org.startDate)} - {org.endDate ? formatYear(org.endDate) : 'Sekarang'}
                      </p>
                    </div>
                  </div>
                </li>
              {/each}
            </ul>
          {/if}
        </div>
        <div class="col-md-4">
          <div class="card sticky-top overflow-hidden rounded-4">
            <div class="card-header p-4">
              <h4 class="mb-0">Navigasi</h4>
            </div>
            <div class="card-body">
              <div class="nav nav-pills flex-column align-items-stretch">
                <div class="nav-item">
                  <button
                    class={`nav-link py-3 w-100 text-start ${activeTab === 'education' ? 'active' : ''}`}
                    type="button"
                    onclick={() => (activeTab = 'education')}
                  >
                    Riwayat Pendidikan
                  </button>
                </div>
                <div class="nav-item">
                  <button
                    class={`nav-link py-3 w-100 text-start ${activeTab === 'organization' ? 'active' : ''}`}
                    type="button"
                    onclick={() => (activeTab = 'organization')}
                  >
                    Riwayat Organisasi dan Kepanitiaan
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
