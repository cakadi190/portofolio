<script lang="ts">
  import { Form, InfiniteScroll, Link, page } from '@inertiajs/svelte';
  import Turnstile from '@/components/turnstile.svelte';
  import { Field } from '@/components/ui/field';
  import RichTextEditor from '@/components/ui/lazy-rich-text-editor.svelte';
  import { login, register } from '@/wayfinder/routes';
  import { store } from '@/wayfinder/routes/blog/comments';
  import BlogCommentItem from './blog-comment-item.svelte';
  import type { BlogCommentNode } from './types';

  let {
    comments,
    total,
    postSlug,
  }: {
    comments: { data: BlogCommentNode[] };
    total: number;
    postSlug: string;
  } = $props();

  let replyingTo = $state<number | null>(null);

  /** Remounting the form is what clears the rich-text editor after a successful send. */
  let formKey = $state(0);

  const canComment = $derived(!!page.props.auth?.user);
</script>

<section
  id="komentar"
  class="mt-5 pt-5 border-top"
  aria-labelledby="komentar-title"
>
  <h2 id="komentar-title" class="h3 mb-4">Komentar ({total})</h2>

  {#if canComment}
    {#key formKey}
      <Form
        {...store.form(postSlug)}
        class="d-flex flex-column gap-3 mb-5"
        resetOnSuccess
        onSuccess={() => formKey++}
      >
        {#snippet children({ errors, processing })}
          <Field.Group>
            <Field.Label>Tulis komentar</Field.Label>
            <RichTextEditor
              name="body"
              minimal
              placeholder="Bagikan pendapat Anda..."
              invalid={!!errors.body}
            />
            <Field.Feedback message={errors.body} />
          </Field.Group>
          <Turnstile error={errors['cf-turnstile-response']} />
          <div>
            <button type="submit" class="btn btn-primary" disabled={processing}
              >Kirim Komentar</button
            >
          </div>
          <p class="small opacity-75 mb-0">
            Komentar dimoderasi dan mencerminkan pendapat penulisnya. Baca
            <a href="/disclaimer">kebijakan komentar</a>.
          </p>
        {/snippet}
      </Form>
    {/key}
  {:else}
    <div class="card rounded-4 mb-5">
      <div
        class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3"
      >
        <span>Masuk atau daftar untuk ikut berkomentar.</span>
        <div class="d-flex gap-2">
          <Link href={login().url} class="btn btn-primary">Masuk</Link>
          <Link href={register().url} class="btn btn-outline-secondary"
            >Daftar</Link
          >
        </div>
      </div>
    </div>
  {/if}

  {#if comments.data.length}
    <InfiniteScroll data="comments" buffer={300}>
      <ul class="list-unstyled mb-0">
        {#each comments.data as comment (comment.id)}
          <BlogCommentItem
            {comment}
            {postSlug}
            {canComment}
            {replyingTo}
            onReply={(id) => (replyingTo = id)}
          />
        {/each}
      </ul>
      {#snippet loading()}
        <div
          class="d-flex justify-content-center align-items-center gap-2 py-3 small opacity-75"
        >
          <span class="spinner-border spinner-border-sm" aria-hidden="true"
          ></span>
          Memuat komentar…
        </div>
      {/snippet}
    </InfiniteScroll>
  {:else}
    <p class="opacity-75">Belum ada komentar. Jadilah yang pertama.</p>
  {/if}
</section>
