<script lang="ts">
  import { t } from '@/lib/i18n';
  import AppHead from '@/components/app-head.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';

  type Stats = {
    page_views: number;
    page_views_today: number;
    page_views_month: number;
    bot_page_views: number;
    unique_visitors: number;
    unique_visitors_today: number;
    unique_visitors_month: number;
    visits: number;
    repeat_visitors: number;
    daily: { day: string; page_views: number }[];
    top_pages: { path: string; page_views: number }[];
    top_countries: { country: string; visitors: number }[];
  };

  let { stats }: { stats: Stats } = $props();

  const fmt = (n: number): string => n.toLocaleString('id-ID');
  const tiles = $derived([
    [t('admin-visitor-analytics.tayangan_halaman'), stats.page_views],
    [t('admin-visitor-analytics.tayangan_hari_ini'), stats.page_views_today],
    [t('admin-visitor-analytics.tayangan_bulan_ini'), stats.page_views_month],
    [t('admin-visitor-analytics.pengunjung_unik'), stats.unique_visitors],
    [t('admin-visitor-analytics.unik_hari_ini'), stats.unique_visitors_today],
    [t('admin-visitor-analytics.unik_bulan_ini'), stats.unique_visitors_month],
    [t('admin-visitor-analytics.kunjungan_sesi'), stats.visits],
    [t('admin-visitor-analytics.pengunjung_berulang'), stats.repeat_visitors],
    [t('admin-visitor-analytics.tayangan_bot'), stats.bot_page_views],
  ] as [string, number][]);
  const max = $derived(Math.max(1, ...stats.daily.map((d) => Number(d.page_views))));
</script>

<AppHead title={t('admin-visitor-analytics.analitik_pengunjung')} />

<AdminPageHeader title={t('admin-visitor-analytics.analitik_pengunjung')} subtitle={t('admin-visitor-analytics.angka_diperbarui_maksimal_tiap_60_detik')} />

<div class="row g-3 mb-4">
  {#each tiles as [label, value] (label)}
    <div class="col-6 col-md-4"><div class="card card-body"><div class="text-muted small">{label}</div><div class="fs-4">{fmt(value)}</div></div></div>
  {/each}
</div>

<div class="card mb-4">
  <div class="card-header">{t('admin-visitor-analytics.tayangan_30_hari_terakhir')}</div>
  <div class="card-body d-flex align-items-end gap-1" style="height:160px" role="img" aria-label={t('admin-visitor-analytics.grafik_tayangan_harian')}>
    {#each stats.daily as day (day.day)}
      <div class="bg-primary flex-fill" style="height:{(Number(day.page_views) / max) * 100}%" title="{day.day}: {day.page_views}"></div>
    {/each}
  </div>
</div>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card"><div class="card-header">{t('admin-visitor-analytics.halaman_teratas')}</div>
      <ul class="list-group list-group-flush">
        {#each stats.top_pages as page (page.path)}<li class="list-group-item d-flex justify-content-between"><code>{page.path}</code><span>{fmt(Number(page.page_views))}</span></li>{:else}<li class="list-group-item text-muted">{t('admin-visitor-analytics.belum_ada_data')}</li>{/each}
      </ul>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card"><div class="card-header">{t('admin-visitor-analytics.negara_data_ipinfo')}</div>
      <ul class="list-group list-group-flush">
        {#each stats.top_countries as c (c.country)}<li class="list-group-item d-flex justify-content-between"><span>{c.country}</span><span>{fmt(Number(c.visitors))}</span></li>{:else}<li class="list-group-item text-muted">{t('admin-visitor-analytics.belum_ada_data_pengayaan')}</li>{/each}
      </ul>
    </div>
  </div>
</div>
