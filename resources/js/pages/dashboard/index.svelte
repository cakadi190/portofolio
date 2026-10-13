<script lang="ts">
  import { Link, page } from '@inertiajs/svelte';
  import Activity from '@lucide/svelte/icons/activity';
  import Eye from '@lucide/svelte/icons/eye';
  import FileText from '@lucide/svelte/icons/file-text';
  import Mail from '@lucide/svelte/icons/mail';
  import MessageSquare from '@lucide/svelte/icons/message-square';
  import Plus from '@lucide/svelte/icons/plus';
  import Star from '@lucide/svelte/icons/star';
  import TrendingDown from '@lucide/svelte/icons/trending-down';
  import TrendingUp from '@lucide/svelte/icons/trending-up';
  import Users from '@lucide/svelte/icons/users';
  import AppHead from '@/components/app-head.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import { formatBytes } from '@/lib/media';
  import { formatDate } from '@/lib/utils';
  import { index as commentsIndex } from '@/wayfinder/routes/admin/blog-comments';
  import { index as contactMessagesIndex } from '@/wayfinder/routes/admin/contact-messages';
  import { create as postsCreate, index as postsIndex } from '@/wayfinder/routes/admin/posts';
  import { index as visitorAnalyticsIndex } from '@/wayfinder/routes/admin/visitor-analytics';
  import { index as ratingsIndex } from '@/wayfinder/routes/admin/portfolio-ratings';
  import { index as speakingIndex } from '@/wayfinder/routes/admin/speaking-engagements';

  type Summary = {
    blog: {
      posts_total: number;
      posts_published: number;
      posts_draft: number;
      tags_total: number;
      comments_pending: number;
      comments_total: number;
      monthly: { month: string; posts: number; comments: number }[];
      recent_posts: {
        id: number;
        title: string;
        author: string | null;
        is_published: boolean;
        date: string | null;
      }[];
    };
    site: {
      counts: Record<string, number>;
      media_size: number;
      users: { total: number; admin: number; redaktur: number; user: number };
      inbox: {
        unread: number;
        total: number;
        latest: {
          id: number;
          name: string;
          email: string;
          reason: string;
          is_read: boolean;
          date: string | null;
        }[];
      };
      traffic: {
        series: { day: string; views: number }[];
        total: number;
        today: number;
        previous_total: number;
        top_pages: { path: string; views: number }[];
      };
      ratings_pending: number;
      upcoming_speaking: {
        id: number;
        title: string;
        organizer: string;
        starts_at: string;
      }[];
    } | null;
    system: {
      app_name: string;
      environment: string;
      debug: boolean;
      php_version: string;
      laravel_version: string;
      timezone: string;
      database_driver: string;
      database_size: number | null;
      cache_driver: string;
      queue_driver: string;
      session_driver: string;
      mail_mailer: string;
      jobs_pending: number | null;
      jobs_failed: number | null;
    } | null;
  };

  let { summary }: { summary: Summary } = $props();

  const countLabels: Record<string, string> = {
    portfolios: 'Portofolio',
    services: 'Layanan',
    technologies: 'Teknologi',
    awards: 'Penghargaan',
    certifications: 'Sertifikasi',
    educations: 'Pendidikan',
    careers: 'Karier',
    organizations: 'Organisasi',
    coffee_places: 'Kedai Kopi',
    speaking_engagements: 'Pembicara & Mentoring',
    media: 'Berkas Media',
  };

  const blog = $derived(summary.blog);
  const site = $derived(summary.site);
  const system = $derived(summary.system);

  const stats = $derived([
    { label: 'Artikel terbit', value: blog.posts_published, hint: `${blog.posts_draft} draf`, icon: FileText, tone: 'primary', href: postsIndex().url, alert: false },
    { label: 'Komentar menunggu', value: blog.comments_pending, hint: `${blog.comments_total} total`, icon: MessageSquare, tone: 'warning', href: commentsIndex().url, alert: blog.comments_pending > 0 },
    ...(site
      ? [
          { label: 'Pesan belum dibaca', value: site.inbox.unread, hint: `${site.inbox.total} total`, icon: Mail, tone: 'danger', href: contactMessagesIndex().url, alert: site.inbox.unread > 0 },
          { label: 'Ulasan menunggu', value: site.ratings_pending, hint: 'Ulasan portofolio', icon: Star, tone: 'info', href: ratingsIndex().url, alert: site.ratings_pending > 0 },
          { label: 'Pengguna', value: site.users.total, hint: `${site.users.admin} admin · ${site.users.redaktur} redaktur`, icon: Users, tone: 'success', href: undefined, alert: false },
        ]
      : [{ label: 'Tag', value: blog.tags_total, hint: 'Tag artikel', icon: FileText, tone: 'info', href: undefined, alert: false }]),
  ]);

  const userName = $derived((page.props as { auth?: { user?: { name?: string } } }).auth?.user?.name?.split(' ')[0] ?? '');
  const greeting = $derived.by(() => {
    const hour = new Date().getHours();
    return hour < 11 ? 'Selamat pagi' : hour < 15 ? 'Selamat siang' : hour < 18 ? 'Selamat sore' : 'Selamat malam';
  });

  const attention = $derived(
    [
      blog.comments_pending > 0 && { text: `${blog.comments_pending} komentar menunggu moderasi`, href: commentsIndex().url },
      site && site.inbox.unread > 0 && { text: `${site.inbox.unread} pesan belum dibaca`, href: contactMessagesIndex().url },
      site && site.ratings_pending > 0 && { text: `${site.ratings_pending} ulasan menunggu persetujuan`, href: ratingsIndex().url },
    ].filter((item): item is { text: string; href: string } => item !== false && item !== null),
  );

  const monthlyMax = $derived(Math.max(1, ...blog.monthly.flatMap((entry) => [entry.posts, entry.comments])));
  const monthLabel = (value: string) => new Intl.DateTimeFormat('id-ID', { month: 'short' }).format(new Date(`${value}-01T00:00:00`));

  const traffic = $derived(site?.traffic ?? null);
  const maxViews = $derived(Math.max(1, ...(traffic?.series.map((point) => point.views) ?? [0])));
  const trafficChange = $derived.by(() => {
    if (!traffic || traffic.previous_total === 0) {
      return null;
    }
    return Math.round(((traffic.total - traffic.previous_total) / traffic.previous_total) * 100);
  });
  const shortDay = (value: string) => new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short' }).format(new Date(`${value}T00:00:00`));

  const systemRows = $derived(
    system
      ? [
          ['Lingkungan', system.environment],
          ['Mode debug', system.debug ? 'Aktif' : 'Nonaktif'],
          ['PHP', system.php_version],
          ['Laravel', system.laravel_version],
          ['Zona waktu', system.timezone],
          ['Database', `${system.database_driver}${system.database_size !== null ? ` (${formatBytes(system.database_size)})` : ''}`],
          ['Cache', system.cache_driver],
          ['Antrean', `${system.queue_driver}${system.jobs_pending !== null ? ` · ${system.jobs_pending} menunggu` : ''}`],
          ['Antrean gagal', system.jobs_failed === null ? '-' : String(system.jobs_failed)],
          ['Sesi', system.session_driver],
          ['Surel', system.mail_mailer],
        ]
      : [],
  );

  const speakingDate = (value: string) =>
    new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(value.replace(' ', 'T')));
</script>

<AppHead title="Dasbor" />

<section class="dash-hero mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">{greeting}{userName ? `, ${userName}` : ''} 👋</h1>
    <p class="mb-0 opacity-75">
      {#if attention.length}
        Ada {attention.length} hal yang perlu perhatianmu hari ini.
      {:else}
        Semua beres. Tidak ada yang menunggu tindakanmu.
      {/if}
    </p>
    {#if attention.length}
      <div class="d-flex flex-wrap gap-2 mt-3">
        {#each attention as item (item.text)}
          <Link href={item.href} class="dash-chip">{item.text}</Link>
        {/each}
      </div>
    {/if}
  </div>
  <div class="d-flex flex-wrap gap-2 align-self-start">
    <Link href={postsCreate().url} class="btn btn-light btn-sm d-inline-flex align-items-center gap-1"><Plus size={16} /> Artikel baru</Link>
    {#if site}
      <Link href={visitorAnalyticsIndex().url} class="btn btn-outline-light btn-sm d-inline-flex align-items-center gap-1"><Activity size={16} /> Analitik</Link>
    {/if}
  </div>
</section>

<div class="row g-3 mb-4">
  {#each stats as stat (stat.label)}
    <div class="col-6 col-lg">
      <svelte:element this={stat.href ? 'a' : 'div'} href={stat.href} class="card dash-stat h-100 text-reset text-decoration-none" class:dash-stat-alert={stat.alert}>
        <div class="card-body d-flex justify-content-between align-items-start gap-2">
          <div>
            <div class="text-body-secondary small">{stat.label}</div>
            <div class="fs-2 fw-bold lh-1 my-1">{stat.value}</div>
            <div class="text-body-secondary small">{stat.hint}</div>
          </div>
          <span class="dash-icon dash-icon-{stat.tone}"><stat.icon size={20} /></span>
        </div>
      </svelte:element>
    </div>
  {/each}
</div>

<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong>Aktivitas blog 6 bulan terakhir</strong>
    <span class="small text-body-secondary d-flex gap-3">
      <span><span class="dash-dot dash-dot-posts"></span> Artikel</span>
      <span><span class="dash-dot dash-dot-comments"></span> Komentar</span>
    </span>
  </div>
  <div class="card-body">
    <div class="dash-bars" role="img" aria-label="Grafik aktivitas blog bulanan">
      {#each blog.monthly as entry (entry.month)}
        <div class="dash-bar-col" title="{monthLabel(entry.month)}: {entry.posts} artikel, {entry.comments} komentar">
          <div class="dash-pair">
            <div class="dash-bar" style="height: {Math.max(4, (entry.posts / monthlyMax) * 100)}%"></div>
            <div class="dash-bar dash-bar-alt" style="height: {Math.max(4, (entry.comments / monthlyMax) * 100)}%"></div>
          </div>
          <span class="dash-bar-label">{monthLabel(entry.month)}</span>
        </div>
      {/each}
    </div>
  </div>
</div>

{#if traffic}
  <div class="row g-3 mb-4">
    <div class="col-lg-8">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong class="d-inline-flex align-items-center gap-2"><Eye size={16} /> Tayangan 14 hari terakhir</strong>
          <Link href={visitorAnalyticsIndex().url} class="small">Detail</Link>
        </div>
        <div class="card-body">
          <div class="d-flex align-items-baseline gap-3 mb-3">
            <span class="fs-2 fw-bold lh-1">{traffic.total}</span>
            <span class="text-body-secondary small">tayangan · {traffic.today} hari ini</span>
            {#if trafficChange !== null}
              <span class="badge {trafficChange >= 0 ? 'text-bg-success' : 'text-bg-danger'} d-inline-flex align-items-center gap-1">
                {#if trafficChange >= 0}<TrendingUp size={12} />{:else}<TrendingDown size={12} />{/if}
                {Math.abs(trafficChange)}%
              </span>
            {/if}
          </div>
          <div class="dash-bars" role="img" aria-label="Grafik tayangan harian">
            {#each traffic.series as point (point.day)}
              <div class="dash-bar-col" title="{shortDay(point.day)}: {point.views} tayangan">
                <div class="dash-bar" style="height: {Math.max(4, (point.views / maxViews) * 100)}%"></div>
                <span class="dash-bar-label">{new Date(`${point.day}T00:00:00`).getDate()}</span>
              </div>
            {/each}
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header"><strong>Halaman terpopuler</strong></div>
        <ul class="list-group list-group-flush">
          {#each traffic.top_pages as top (top.path)}
            <li class="list-group-item d-flex justify-content-between gap-2">
              <span class="text-truncate">{top.path}</span>
              <span class="badge text-bg-light border">{top.views}</span>
            </li>
          {:else}
            <li class="list-group-item text-body-secondary">Belum ada data kunjungan.</li>
          {/each}
        </ul>
      </div>
    </div>
  </div>
{/if}

<div class="row g-3 mb-4">
  <div class="col-lg-{site ? 6 : 12}">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Artikel terbaru</strong>
        <Link href={postsIndex().url} class="small">Lihat semua</Link>
      </div>
      <ul class="list-group list-group-flush">
        {#each blog.recent_posts as post (post.id)}
          <li class="list-group-item d-flex justify-content-between align-items-start gap-2">
            <div>
              <div class="fw-semibold">{post.title}</div>
              <div class="small text-body-secondary">{post.author ?? 'Tanpa penulis'} · {formatDate(post.date)}</div>
            </div>
            <span class="badge {post.is_published ? 'text-bg-success' : 'text-bg-secondary'}">{post.is_published ? 'Terbit' : 'Draf'}</span>
          </li>
        {:else}
          <li class="list-group-item text-body-secondary">Belum ada artikel.</li>
        {/each}
      </ul>
    </div>
  </div>

  {#if site}
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong>Pesan masuk terbaru</strong>
          <Link href={contactMessagesIndex().url} class="small">Lihat semua</Link>
        </div>
        <ul class="list-group list-group-flush">
          {#each site.inbox.latest as message (message.id)}
            <li class="list-group-item d-flex justify-content-between align-items-start gap-2">
              <div>
                <div class:fw-bold={!message.is_read}>{message.name}</div>
                <div class="small text-body-secondary">{message.reason} · {formatDate(message.date)}</div>
              </div>
              {#if !message.is_read}<span class="badge text-bg-danger">Baru</span>{/if}
            </li>
          {:else}
            <li class="list-group-item text-body-secondary">Belum ada pesan.</li>
          {/each}
        </ul>
      </div>
    </div>
  {/if}
</div>

{#if site}
  <div class="row g-3 mb-4">
    <div class="col-lg-7">
      <div class="card h-100">
        <div class="card-header"><strong>Seluruh data</strong></div>
        <div class="card-body">
          <div class="row row-cols-2 row-cols-md-3 g-3">
            {#each Object.entries(site.counts) as [key, value] (key)}
              <div class="col">
                <div class="fs-4 fw-bold">{value}</div>
                <div class="small text-body-secondary">{countLabels[key] ?? key}</div>
              </div>
            {/each}
            <div class="col">
              <div class="fs-4 fw-bold">{formatBytes(site.media_size)}</div>
              <div class="small text-body-secondary">Ukuran pustaka media</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong>Kegiatan mendatang</strong>
          <Link href={speakingIndex().url} class="small">Lihat semua</Link>
        </div>
        <ul class="list-group list-group-flush">
          {#each site.upcoming_speaking as engagement (engagement.id)}
            <li class="list-group-item">
              <div class="fw-semibold">{engagement.title}</div>
              <div class="small text-body-secondary">{speakingDate(engagement.starts_at)} · {engagement.organizer}</div>
            </li>
          {:else}
            <li class="list-group-item text-body-secondary">Belum ada kegiatan mendatang.</li>
          {/each}
        </ul>
      </div>
    </div>
  </div>
{/if}

{#if system}
  <div class="card mb-4">
    <div class="card-header"><strong>Sistem</strong></div>
    <div class="card-body">
      <dl class="row row-cols-1 row-cols-md-3 g-3 mb-0">
        {#each systemRows as [label, value] (label)}
          <div class="col">
            <dt class="small text-body-secondary fw-normal">{label}</dt>
            <dd class="mb-0 fw-semibold">{value}</dd>
          </div>
        {/each}
      </dl>
    </div>
  </div>
{/if}
