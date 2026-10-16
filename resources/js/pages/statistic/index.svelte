<script lang="ts">
  import { t, intlLocale } from '@/lib/i18n';
  import Link from '@/components/link.svelte';
  import AppHead from '@/components/app-head.svelte';
  import HeaderPage from '@/components/header-page.svelte';
  import StatsHomeSection from '@/components/home/stats-home-section.svelte';

  type Row = { title: string; slug: string; views: number };
  type Group = { count: number; views: number; rows: Row[] };

  let {
    siteViews,
    blog,
    notes,
    guestbookCount,
  }: {
    siteViews: number;
    blog: Group;
    notes: Group;
    guestbookCount: number;
  } = $props();

  const format = (value: number): string =>
    new Intl.NumberFormat(intlLocale()).format(value);

  const groups = $derived([
    { key: 'blog', label: t('statistic.group.blog'), prefix: '/blog' },
    { key: 'notes', label: t('statistic.group.notes'), prefix: '/notes' },
  ] as const);

  const stats = $derived([
    {
      value: format(siteViews),
      suffix: '',
      label: t('statistic.views.site'),
    },
    {
      value: format(blog.views),
      suffix: '',
      label: t('statistic.views.blog', { count: format(blog.count) }),
    },
    {
      value: format(notes.views),
      suffix: '',
      label: t('statistic.views.notes', { count: format(notes.count) }),
    },
    { value: format(guestbookCount), suffix: '', label: t('statistic.guestbook') },
  ]);
</script>

<AppHead title={t('statistic.title')} />

<div id="statistic-page">
  <HeaderPage
    title={t('statistic.title')}
    subtitle={t('statistic.subtitle')}
  />

  <StatsHomeSection {stats} />

  <section class="need-space pt-0">
    <div class="container">
      {#each groups as group (group.key)}
        {@const data = group.key === 'blog' ? blog : notes}
        <h2 class="h4 mb-3">{group.label}</h2>
        <div class="table-responsive mb-5">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>{t('statistic.col.title')}</th>
                <th class="text-end">{t('statistic.col.views')}</th>
              </tr>
            </thead>
            <tbody>
              {#each data.rows as row (row.slug)}
                <tr>
                  <td>
                    <Link href={`${group.prefix}/${row.slug}`}>{row.title}</Link
                    >
                  </td>
                  <td class="text-end">{format(row.views)}</td>
                </tr>
              {:else}
                <tr>
                  <td colspan="2" class="text-center opacity-75"
                    >{t('statistic.empty')}</td
                  >
                </tr>
              {/each}
            </tbody>
            <tfoot>
              <tr class="fw-semibold">
                <td>{t('statistic.total')}</td>
                <td class="text-end">{format(data.views)}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      {/each}

      <p class="small opacity-75">
        {t('statistic.footnote')}
        <Link href="/guestbook">{t('statistic.write')}</Link>.
      </p>
    </div>
  </section>
</div>
