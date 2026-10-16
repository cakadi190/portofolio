<script lang="ts">
  import { t, intlLocale, localizeHref } from '@/lib/i18n';
  import AppHead from '@/components/app-head.svelte';
  import HeaderPage from '@/components/header-page.svelte';
  import { index } from '@/wayfinder/routes/clarifications';

  let {
    clarification,
  }: {
    clarification: {
      title: string;
      withdrawnAt: string;
      withdrawalNote: string | null;
    };
  } = $props();

  const withdrawnDate = $derived(
    new Date(clarification.withdrawnAt).toLocaleDateString(intlLocale(), {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }),
  );
</script>

<AppHead title={t('clarifications.withdrawn.title')} />

<div id="clarification-withdrawn">
  <HeaderPage backTo={index().url} title={t('clarifications.withdrawn.title')} subtitle={clarification.title} />

  <section class="need-space pt-0">
    <div class="container">
      <div class="alert alert-secondary" role="status">
        {t('clarifications.withdrawn.text', { date: withdrawnDate })}
      </div>
      {#if clarification.withdrawalNote}
        <p class="opacity-75" style="white-space: pre-line">{clarification.withdrawalNote}</p>
      {/if}
      <a href={localizeHref(index().url)}>{t('clarifications.withdrawn.more')}</a>
    </div>
  </section>
</div>
