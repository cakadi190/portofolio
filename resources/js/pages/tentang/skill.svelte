<script lang="ts">
  import { t } from '@/lib/i18n';
  import AppHead from '@/components/app-head.svelte';
  import HeaderPage from '@/components/header-page.svelte';
</script>

<AppHead title={t('about.skills_page.title')} />

<div>
  <HeaderPage backTo="/about" title={t('about.skills_page.title')} subtitle={t('about.skills_page.subtitle')} />

  <section class="need-space pt-0">
    <div class="container">
      <p class="opacity-75 mb-0">{t('about.no_content')}</p>
    </div>
  </section>
</div>
