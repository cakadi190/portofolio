<script lang="ts">
  import { Link } from '@inertiajs/svelte';
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
    new Intl.NumberFormat('id-ID').format(value);

  const groups = [
    { key: 'blog', label: 'Blog', prefix: '/blog' },
    { key: 'notes', label: 'Catatan', prefix: '/notes' },
  ] as const;

  const stats = $derived([
    {
      value: format(siteViews),
      suffix: '',
      label: 'Tayangan halaman di seluruh situs',
    },
    {
      value: format(blog.views),
      suffix: '',
      label: `Tayangan dari ${format(blog.count)} artikel blog`,
    },
    {
      value: format(notes.views),
      suffix: '',
      label: `Tayangan dari ${format(notes.count)} catatan singkat`,
    },
    { value: format(guestbookCount), suffix: '', label: 'Pesan di buku tamu' },
  ]);
</script>

<AppHead title="Statistik Situs" />

<div id="statistic-page">
  <HeaderPage
    title="Statistik Situs"
    subtitle="Angka tayangan halaman situs, artikel, dan catatan singkat."
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
                <th>Judul</th>
                <th class="text-end">Tayangan</th>
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
                    >Belum ada data.</td
                  >
                </tr>
              {/each}
            </tbody>
            <tfoot>
              <tr class="fw-semibold">
                <td>Total</td>
                <td class="text-end">{format(data.views)}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      {/each}

      <p class="small opacity-75">
        Angka berasal dari pengunjung manusia (bot tidak dihitung). Satu
        pengunjung dihitung sekali dalam rentang beberapa detik per halaman.
        <Link href="/guestbook">Tulis pesan di buku tamu</Link>.
      </p>
    </div>
  </section>
</div>
