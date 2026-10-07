<script lang="ts">
  import { Form, Link, page } from '@inertiajs/svelte';
  import Turnstile from '@/components/turnstile.svelte';
  import { Field } from '@/components/ui/field';
  import { login, register } from '@/wayfinder/routes';
  import { store } from '@/wayfinder/routes/blog/comments';
  import BlogCommentItem from './blog-comment-item.svelte';
  import type { BlogCommentNode } from './types';

  let {
    comments,
    postSlug,
  }: {
    comments: BlogCommentNode[];
    postSlug: string;
  } = $props();

  let replyingTo = $state<number | null>(null);

  const canComment = $derived(!!page.props.auth?.user);

  function countAll(nodes: BlogCommentNode[]): number {
    return nodes.reduce((sum, node) => sum + 1 + countAll(node.replies), 0);
  }

  const total = $derived(countAll(comments));
</script>

<section id="komentar" class="mt-5 pt-5 border-top" aria-labelledby="komentar-title">
  <h3 id="komentar-title" class="mb-4">Komentar ({total})</h3>

  {#if canComment}
    <Form
      {...store.form(postSlug)}
      class="d-flex flex-column gap-3 mb-5"
      resetOnSuccess
    >
      {#snippet children({ errors, processing })}
        <Field.Group>
          <Field.Label for="comment-body">Tulis komentar</Field.Label>
          <textarea
            id="comment-body"
            name="body"
            rows="4"
            maxlength="2000"
            required
            placeholder="Bagikan pendapat Anda..."
            class="form-control"
            class:is-invalid={!!errors.body}
          ></textarea>
          <Field.Feedback message={errors.body} />
        </Field.Group>
        <Turnstile error={errors['cf-turnstile-response']} />
        <div>
          <button type="submit" class="btn btn-primary" disabled={processing}
            >Kirim Komentar</button
          >
        </div>
      {/snippet}
    </Form>
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

  {#if comments.length}
    <ul class="list-unstyled mb-0">
      {#each comments as comment (comment.id)}
        <BlogCommentItem
          {comment}
          {postSlug}
          {canComment}
          {replyingTo}
          onReply={(id) => (replyingTo = id)}
        />
      {/each}
    </ul>
  {:else}
    <p class="opacity-75">Belum ada komentar. Jadilah yang pertama.</p>
  {/if}
</section>
