<script lang="ts">
  import { Link } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import { formatBytes } from '@/lib/media';
  import { formatDate } from '@/lib/utils';
  import { index as commentsIndex } from '@/wayfinder/routes/admin/blog-comments';
  import { index as contactMessagesIndex } from '@/wayfinder/routes/admin/contact-messages';
  import { index as postsIndex } from '@/wayfinder/routes/admin/posts';
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
    { label: 'Artikel terbit', value: blog.posts_published, hint: `${blog.posts_draft} draf` },
    { label: 'Komentar menunggu', value: blog.comments_pending, hint: `${blog.comments_total} total`, href: commentsIndex().url, alert: blog.comments_pending > 0 },
    ...(site
      ? [
          { label: 'Pesan belum dibaca', value: site.inbox.unread, hint: `${site.inbox.total} total`, href: contactMessagesIndex().url, alert: site.inbox.unread > 0 },
          { label: 'Ulasan menunggu', value: site.ratings_pending, hint: 'Ulasan portofolio', href: ratingsIndex().url, alert: site.ratings_pending > 0 },
          { label: 'Pengguna', value: site.users.total, hint: `${site.users.admin} admin · ${site.users.redaktur} redaktur` },
        ]
      : [{ label: 'Tag', value: blog.tags_total, hint: 'Tag artikel' }]),
  ]);

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

<AdminPageHeader title="Dasbor" subtitle="Ringkasan seluruh data dan sistem." />

<div class="row g-3 mb-4">
  {#each stats as stat (stat.label)}
    <div class="col-6 col-lg">
      <svelte:element this={stat.href ? 'a' : 'div'} href={stat.href} class="card h-100 text-reset text-decoration-none">
        <div class="card-body">
          <div class="text-body-secondary small">{stat.label}</div>
          <div class="fs-2 fw-bold" class:text-danger={stat.alert}>{stat.value}</div>
          <div class="text-body-secondary small">{stat.hint}</div>
        </div>
      </svelte:element>
    </div>
  {/each}
</div>

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
