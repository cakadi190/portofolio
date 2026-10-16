<script lang="ts">
  import { t } from '@/lib/i18n';
  import AppHead from '@/components/app-head.svelte';
  import HeaderPage from '@/components/header-page.svelte';
</script>

<AppHead title={t('about.site.title')} />

<div>
  <HeaderPage title={t('about.site.title')} subtitle={t('about.site.subtitle')} />

  <section class="need-space pt-0">
    <div class="container">
      <p class="opacity-75 mb-0">{t('about.no_content')}</p>
    </div>
  </section>
</div>
