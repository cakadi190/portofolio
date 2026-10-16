<script lang="ts">
  import { localizeForm, localizeHref, t } from '@/lib/i18n';
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
  <h2 id="komentar-title" class="h3 mb-4">{t('comments.title', { total })}</h2>

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
              <Field.Label for="comment-guest-name">{t('form.name')}</Field.Label>
              <Field.Input
                id="comment-guest-name"
                name="guest_name"
                placeholder={t('form.name_placeholder')}
                invalid={!!errors.guest_name}
              />
              <Field.Feedback message={errors.guest_name} />
            </Field.Group>
            <Field.Group class="col-md-6">
              <Field.Label for="comment-guest-email">{t('form.email')}</Field.Label>
              <Field.Input
                id="comment-guest-email"
                name="guest_email"
                type="email"
                placeholder={t('form.email_placeholder')}
                invalid={!!errors.guest_email}
              />
              <Field.Feedback message={errors.guest_email} />
            </Field.Group>
          </div>
        {/if}
        <Field.Group>
          <Field.Label>{t('comments.write')}</Field.Label>
          <RichTextEditor
            name="body"
            minimal
            placeholder={t('comments.placeholder')}
            invalid={!!errors.body}
          />
          <Field.Feedback message={errors.body} />
        </Field.Group>
        <CaptchaSubmitRow error={errors['cf-turnstile-response']}>
          <button type="submit" class="btn btn-primary" disabled={processing}
            
            >{t('comments.submit')}</button
          >
        </CaptchaSubmitRow>
        <p class="small opacity-75 mb-0">
          {t('comments.note')}
          <a href={localizeHref('/disclaimer')}>{t('comments.policy')}</a>.
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
          {t('comments.loading')}
        </div>
      {/snippet}
    </InfiniteScroll>
  {:else}
    <p class="opacity-75">{t('comments.empty')}</p>
  {/if}
</section>
