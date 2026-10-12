<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import ResumeViewer from '@/components/resume/resume-viewer.svelte';
  import { router } from '@inertiajs/svelte';

  let { pdfUrl, title }: { pdfUrl: string; title: string } = $props();
</script>

<AppHead {title}>
  <meta name="robots" content="noindex" />
</AppHead>

<ResumeViewer
  url={pdfUrl}
  title="Curriculum Vitae"
  subtitle={title}
  downloadName="CV-Amir-Zuhdi-Wibowo.pdf"
  onclose={() => router.visit('/')}
/>

