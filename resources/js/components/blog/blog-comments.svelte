<script lang="ts">
  import { localizeForm, localizeHref } from '@/lib/i18n';
  import { Form, InfiniteScroll, page } from '@inertiajs/svelte';
  import CaptchaSubmitRow from '@/components/captcha-submit-row.svelte';
  import { Field } from '@/components/ui/field';
  import RichTextEditor from '@/components/ui/lazy-rich-text-editor.svelte';
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

  const isGuest = $derived(!page.props.auth?.user);
</script>

<section
  id="komentar"
  class="mt-5 pt-5 border-top"
  aria-labelledby="komentar-title"
>
  <h2 id="komentar-title" class="h3 mb-4">Komentar ({total})</h2>

  {#key formKey}
    <Form
      {...localizeForm(store.form(postSlug))}
      class="d-flex flex-column gap-3 mb-5"
      resetOnSuccess
      onSuccess={() => formKey++}
    >
      {#snippet children({ errors, processing })}
        {#if isGuest}
          <div class="row g-3">
            <Field.Group class="col-md-6">
              <Field.Label for="comment-guest-name">Nama</Field.Label>
              <Field.Input
                id="comment-guest-name"
                name="guest_name"
                placeholder="Mis: Cak Adi"
                invalid={!!errors.guest_name}
              />
              <Field.Feedback message={errors.guest_name} />
            </Field.Group>
            <Field.Group class="col-md-6">
              <Field.Label for="comment-guest-email">Email</Field.Label>
              <Field.Input
                id="comment-guest-email"
                name="guest_email"
                type="email"
                placeholder="nama@email.com"
                invalid={!!errors.guest_email}
              />
              <Field.Feedback message={errors.guest_email} />
            </Field.Group>
          </div>
        {/if}
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
        <CaptchaSubmitRow error={errors['cf-turnstile-response']}>
          <button type="submit" class="btn btn-primary" disabled={processing}
            >Kirim Komentar</button
          >
        </CaptchaSubmitRow>
        <p class="small opacity-75 mb-0">
          Komentar ditinjau dulu sebelum tampil dan email tidak ditampilkan.
          Baca
          <a href={localizeHref('/disclaimer')}>kebijakan komentar</a>.
        </p>
      {/snippet}
    </Form>
  {/key}

  {#if comments.data.length}
    <InfiniteScroll data="comments" buffer={300}>
      <ul class="list-unstyled mb-0">
        {#each comments.data as comment (comment.id)}
          <BlogCommentItem
            {comment}
            {postSlug}
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
