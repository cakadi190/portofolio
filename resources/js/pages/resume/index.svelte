<script lang="ts">
  import { localizeHref } from '@/lib/i18n';
  import AppHead from '@/components/app-head.svelte';
  import ResumeViewer from '@/components/resume/resume-viewer.svelte';
  import { router } from '@inertiajs/svelte';

  type Profile = { key: string; label: string; description: string; url: string; pdfUrl: string; downloadName: string };

  let {
    profile,
    profiles,
    pdfUrl,
    downloadName,
    title,
    description,
  }: {
    profile: string;
    profiles: Profile[];
    pdfUrl: string;
    downloadName: string;
    title: string;
    description: string;
  } = $props();

  const switchProfile = (key: string): void => {
    const target = profiles.find((item) => item.key === key);

    if (target && target.key !== profile) {
      router.visit(localizeHref(target.url), { preserveScroll: false });
    }
  };
</script>

<AppHead {title}>
  <meta name="description" content={description} />
  <meta property="og:title" content={title} />
  <meta property="og:description" content={description} />
  <meta property="og:type" content="profile" />
  <meta name="robots" content="noindex" />
</AppHead>

{#key pdfUrl}
  <ResumeViewer
    url={pdfUrl}
    title="Curriculum Vitae"
    subtitle={title}
    {downloadName}
    {profiles}
    activeProfile={profile}
    info={[
      { label: 'Profil CV', value: profiles.find((item) => item.key === profile)?.label ?? profile },
      { label: 'Nama berkas', value: downloadName },
      { label: 'Format', value: 'PDF (ATS-friendly)' },
    ]}
    onprofile={switchProfile}
    onclose={() => router.visit(localizeHref('/'))}
  />
{/key}
