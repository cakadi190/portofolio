<script lang="ts">
  import { Form, Link, page } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import GuestbookItem from '@/components/guestbook/guestbook-item.svelte';
  import type { GuestbookNode } from '@/components/guestbook/types';
  import HeaderPage from '@/components/header-page.svelte';
  import Turnstile from '@/components/turnstile.svelte';
  import { Field } from '@/components/ui/field';
  import { login, register } from '@/wayfinder/routes';
  import { store } from '@/wayfinder/routes/guestbook';

  let { entries }: { entries: GuestbookNode[] } = $props();

  let replyingTo = $state<number | null>(null);

  const canWrite = $derived(!!page.props.auth?.user);

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
          {#if canWrite}
            <Form
              {...store.form()}
              class="d-flex flex-column gap-3 mb-5"
              resetOnSuccess
            >
              {#snippet children({ errors, processing })}
                <Field.Group>
                  <Field.Label for="guestbook-body">Tulis pesan</Field.Label>
                  <textarea
                    id="guestbook-body"
                    name="body"
                    rows="4"
                    maxlength="1000"
                    required
                    placeholder="Halo, saya mampir dari..."
                    class="form-control"
                    class:is-invalid={!!errors.body}
                  ></textarea>
                  <Field.Feedback message={errors.body} />
                </Field.Group>
                <Turnstile error={errors['cf-turnstile-response']} />
                <div>
                  <button
                    type="submit"
                    class="btn btn-primary"
                    disabled={processing}>Kirim Pesan</button
                  >
                </div>
                <p class="small opacity-75 mb-0">
                  Pesan dimoderasi dan mencerminkan pendapat penulisnya. Baca
                  <a href="/disclaimer">penyangkalan</a>.
                </p>
              {/snippet}
            </Form>
          {:else}
            <div class="card rounded-4 mb-5">
              <div
                class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3"
              >
                <span>Masuk atau daftar untuk menulis di buku tamu.</span>
                <div class="d-flex gap-2">
                  <Link href={login().url} class="btn btn-primary">Masuk</Link>
                  <Link href={register().url} class="btn btn-outline-secondary"
                    >Daftar</Link
                  >
                </div>
              </div>
            </div>
          {/if}

          <h3 class="mb-4">Pesan ({total})</h3>

          {#if entries.length}
            <ul class="list-unstyled mb-0">
              {#each entries as entry (entry.id)}
                <GuestbookItem
                  {entry}
                  {canWrite}
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
