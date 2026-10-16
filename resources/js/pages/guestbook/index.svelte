<script lang="ts">
  import { localizeHref } from '@/lib/i18n';
  import { InfiniteScroll } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import GuestbookItem from '@/components/guestbook/guestbook-item.svelte';
  import type { GuestbookNode } from '@/components/guestbook/types';
  import HeaderPage from '@/components/header-page.svelte';
  import GuestbookForm from '@/components/guestbook/guestbook-form.svelte';

  let {
    entries,
    totalEntries,
  }: { entries: { data: GuestbookNode[] }; totalEntries: number } = $props();

  let replyingTo = $state<number | null>(null);
</script>

<AppHead title="Buku Tamu" />

<div id="guestbook-page">
  <HeaderPage
    title="Buku Tamu"
    subtitle="Tinggalkan pesan, sapaan, atau kesan Anda di sini."
  />

  <section class="need-space pt-0">
    <div class="container">
      <div class="row">
        <div class="col-lg-8 mx-auto">
          <div class="mb-5">
            <GuestbookForm />
            <p class="small opacity-75 mt-3 mb-0">
              Pesan mencerminkan pendapat penulisnya. Baca
              <a href={localizeHref('/disclaimer')}>penyangkalan</a>.
            </p>
          </div>

          <h2 class="h3 mb-4">Pesan ({totalEntries})</h2>

          {#if entries.data.length}
            <InfiniteScroll data="entries" buffer={300}>
            <ul class="list-unstyled mb-0">
              {#each entries.data as entry (entry.id)}
                <GuestbookItem
                  {entry}
                  {replyingTo}
                  onReply={(id) => (replyingTo = id)}
                />
              {/each}
            </ul>
              {#snippet loading()}
                <div class="d-flex justify-content-center align-items-center gap-2 py-3 small opacity-75">
                  <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                  Memuat pesan…
                </div>
              {/snippet}
            </InfiniteScroll>
          {:else}
            <p class="opacity-75">Belum ada pesan. Jadilah yang pertama.</p>
          {/if}
        </div>
      </div>
    </div>
  </section>
</div>
