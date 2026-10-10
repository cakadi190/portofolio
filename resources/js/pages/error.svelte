<script lang="ts">
  import { Link } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import { home } from '@/wayfinder/routes';

  let {
    status,
    title,
  }: {
    status: number;
    title: string;
    text: string;
    image: string;
  } = $props();
</script>

<AppHead title={`${status} ${title}`} />

<div class="container vh-100 d-flex justify-content-center align-items-center">
  <div class="text-center">
    <h1 class="display-1">{status}</h1>
    <p class="lead">{title}</p>

    <Link href={home().url} class="btn btn-primary d-block">Kembali</Link>
  </div>
</div>
