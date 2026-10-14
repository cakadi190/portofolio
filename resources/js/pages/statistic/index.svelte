<script lang="ts">
  import { Link } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import HeaderPage from '@/components/header-page.svelte';

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

  const groups = $derived([
    { key: 'blog', label: 'Blog', unit: 'artikel', prefix: '/blog', data: blog },
    {
      key: 'notes',
      label: 'Catatan',
      unit: 'catatan',
      prefix: '/notes',
      data: notes,
    },
  ]);
</script>

<AppHead title="Statistik Situs" />

<div id="statistic-page">
  <HeaderPage
    title="Statistik Situs"
    subtitle="Angka tayangan halaman situs, artikel, dan catatan singkat."
  />

  <section class="need-space pt-0">
    <div class="container">
      <div class="row g-3 mb-5">
        <div class="col-md-6 col-lg-3">
          <div class="card rounded-4 h-100">
            <div class="card-body">
              <div class="opacity-75">Tayangan situs</div>
              <div class="display-6 fw-semibold">{format(siteViews)}</div>
            </div>
          </div>
        </div>
        {#each groups as group (group.key)}
          <div class="col-md-6 col-lg-3">
            <div class="card rounded-4 h-100">
              <div class="card-body">
                <div class="opacity-75">{group.label}</div>
                <div class="display-6 fw-semibold">
                  {format(group.data.views)}
                </div>
                <small class="opacity-75"
                  >tayangan &middot; {format(group.data.count)}
                  {group.unit}</small
                >
              </div>
            </div>
          </div>
        {/each}
        <div class="col-md-6 col-lg-3">
          <div class="card rounded-4 h-100">
            <div class="card-body">
              <div class="opacity-75">Buku tamu</div>
              <div class="display-6 fw-semibold">{format(guestbookCount)}</div>
              <small class="opacity-75"
                >pesan &middot; <Link href="/guestbook">tulis pesan</Link></small
              >
            </div>
          </div>
        </div>
      </div>

      {#each groups as group (group.key)}
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
              {#each group.data.rows as row (row.slug)}
                <tr>
                  <td>
                    <Link href={`${group.prefix}/${row.slug}`}>{row.title}</Link>
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
                <td class="text-end">{format(group.data.views)}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      {/each}

      <p class="small opacity-75">
        Angka berasal dari pengunjung manusia (bot tidak dihitung). Satu
        pengunjung dihitung sekali dalam rentang beberapa detik per halaman.
      </p>
    </div>
  </section>
</div>
