<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import HeaderPage from '@/components/header-page.svelte';
  import { index } from '@/wayfinder/routes/clarifications';

  let {
    clarification,
  }: {
    clarification: {
      title: string;
      withdrawnAt: string;
      withdrawalNote: string | null;
    };
  } = $props();

  const withdrawnDate = $derived(
    new Date(clarification.withdrawnAt).toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }),
  );
</script>

<AppHead title="Klarifikasi Ditarik" />

<div id="clarification-withdrawn">
  <HeaderPage backTo={index().url} title="Klarifikasi Ditarik" subtitle={clarification.title} />

  <section class="need-space pt-0">
    <div class="container">
      <div class="alert alert-secondary" role="status">
        Klarifikasi ini ditarik oleh pemilik situs pada {withdrawnDate} dan isinya tidak
        ditampilkan lagi.
      </div>
      {#if clarification.withdrawalNote}
        <p class="opacity-75" style="white-space: pre-line">{clarification.withdrawalNote}</p>
      {/if}
      <a href={index().url}>Lihat klarifikasi lainnya</a>
    </div>
  </section>
</div>
