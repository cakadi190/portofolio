<script lang="ts">
  import { localizeHref } from '@/lib/i18n';
  import AppHead from '@/components/app-head.svelte';
  import ResumeViewer from '@/components/resume/resume-viewer.svelte';
  import { router } from '@inertiajs/svelte';

  let {
    title,
    description,
    pdfUrl,
    downloadName,
    info,
  }: {
    title: string;
    description: string;
    pdfUrl: string;
    downloadName: string;
    info: { label: string; value: string; href?: string }[];
  } = $props();
</script>

<AppHead {title}>
  <meta name="description" content={description} />
  <meta property="og:title" content={title} />
  <meta property="og:description" content={description} />
  <meta name="robots" content="noindex" />
</AppHead>

{#key pdfUrl}
  <ResumeViewer
    url={pdfUrl}
    title="Sertifikasi"
    subtitle={title}
    {downloadName}
    {info}
    onclose={() => router.visit(localizeHref('/about#certification'))}
  />
{/key}
