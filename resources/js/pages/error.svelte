<script lang="ts">
  import { Link } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import { home } from '@/wayfinder/routes';

  let {
    status,
    title,
    text,
    image,
  }: {
    status: number;
    title: string;
    text: string;
    image: string;
  } = $props();
</script>

<AppHead title={`${status} ${title}`} />

<section class="need-space">
  <div class="container d-flex justify-content-center align-items-center" style="min-height: 60vh">
    <div class="text-center">
      <img class="mb-4" src={image} alt={title} height="250" />
      <p class="display-4 fw-bold mb-2">{status}</p>
      <h1 class="h3">{title}</h1>
      <p class="text-muted mb-4">{text}</p>

      <div class="d-flex justify-content-center gap-2">
        <button type="button" class="btn btn-outline-secondary" onclick={() => window.history.back()}>Kembali</button>
        <Link href={home().url} class="btn btn-primary">Ke Beranda</Link>
      </div>
    </div>
  </div>
</section>
