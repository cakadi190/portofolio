<script lang="ts">
  import { t, localizeHref } from '@/lib/i18n';
  import AppHead from '@/components/app-head.svelte';
  import HeaderPage from '@/components/header-page.svelte';
  import { index as clarifications } from '@/wayfinder/routes/clarifications';
  import { index as contact } from '@/wayfinder/routes/contact';

  const sections = $derived(
    ['opinion', 'accuracy', 'links', 'copyright', 'comments', 'clarifications', 'liability'].map((key) => ({
      key,
      title: t(`disclaimer.s.${key}.title`),
      body: t(`disclaimer.s.${key}.body`),
    })),
  );
</script>

<AppHead title={t('disclaimer.title')} />

<div>
  <HeaderPage
    title={t('disclaimer.title')}
    subtitle={t('disclaimer.subtitle')}
  />

  <section class="need-space pt-0">
    <div class="container">
      {#each sections as section (section.key)}
        <h2 class="h5 mt-4">{section.title}</h2>
        <p class="opacity-75 mb-0">{section.body}</p>
      {/each}

      <h2 class="h5 mt-4">{t('disclaimer.correction.title')}</h2>
      <p class="opacity-75 mb-0">
        {t('disclaimer.correction.before')}
        <a href={localizeHref(contact().url)}>{t('disclaimer.correction.form')}</a>. {t('disclaimer.correction.middle')}
        <a href={localizeHref(clarifications().url)}>{t('disclaimer.correction.list')}</a>.
      </p>
    </div>
  </section>
</div>
