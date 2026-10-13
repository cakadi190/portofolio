<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import GuestbookItem from '@/components/guestbook/guestbook-item.svelte';
  import type { GuestbookNode } from '@/components/guestbook/types';
  import HeaderPage from '@/components/header-page.svelte';
  import GuestbookForm from '@/components/guestbook/guestbook-form.svelte';

  let { entries }: { entries: GuestbookNode[] } = $props();

  let replyingTo = $state<number | null>(null);

  function countAll(nodes: GuestbookNode[]): number {
    return nodes.reduce((sum, node) => sum + 1 + countAll(node.replies), 0);
  }

  const total = $derived(countAll(entries));
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
              <a href="/disclaimer">penyangkalan</a>.
            </p>
          </div>

          <h3 class="mb-4">Pesan ({total})</h3>

          {#if entries.length}
            <ul class="list-unstyled mb-0">
              {#each entries as entry (entry.id)}
                <GuestbookItem
                  {entry}
                  {replyingTo}
                  onReply={(id) => (replyingTo = id)}
                />
              {/each}
            </ul>
          {:else}
            <p class="opacity-75">Belum ada pesan. Jadilah yang pertama.</p>
          {/if}
        </div>
      </div>
    </div>
  </section>
</div>
