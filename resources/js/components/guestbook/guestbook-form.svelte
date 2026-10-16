<script lang="ts">
  import { localizeForm, t } from '@/lib/i18n';
  import { Form, page } from '@inertiajs/svelte';
  import CaptchaSubmitRow from '@/components/captcha-submit-row.svelte';
  import { Field } from '@/components/ui/field';
  import RichTextEditor from '@/components/ui/lazy-rich-text-editor.svelte';
  import { store } from '@/wayfinder/routes/guestbook';

  let {
    parentId = null,
    label,
    submitLabel,
    placeholder,
    onSuccess,
  }: {
    parentId?: number | null;
    label?: string;
    submitLabel?: string;
    placeholder?: string;
    onSuccess?: () => void;
  } = $props();

  const isGuest = $derived(!page.props.auth?.user);

  /** Remounting the form is what clears the rich-text editor after a successful send. */
  let formKey = $state(0);
</script>

{#key formKey}
  <Form
    {...localizeForm(store.form())}
    class="d-flex flex-column gap-3"
    novalidate
    resetOnSuccess
    onSuccess={() => {
      formKey++;
      onSuccess?.();
    }}
  >
    {#snippet children({ errors, processing })}
      {#if parentId}
        <input type="hidden" name="parent_id" value={parentId} />
      {/if}

      {#if isGuest}
        <div class="row g-3">
          <Field.Group class="col-md-6">
            <Field.Label for={`guest-name-${parentId ?? 'root'}`}
              
                >{t('form.name')}</Field.Label
            >
            <Field.Input
              id={`guest-name-${parentId ?? 'root'}`}
              name="guest_name"
              placeholder={t('form.name_placeholder')}
              invalid={!!errors.guest_name}
            />
            <Field.Feedback message={errors.guest_name} />
          </Field.Group>
          <Field.Group class="col-md-6">
            <Field.Label for={`guest-email-${parentId ?? 'root'}`}
              
                >{t('form.email')}</Field.Label
            >
            <Field.Input
              id={`guest-email-${parentId ?? 'root'}`}
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
        <Field.Label>{label ?? t('guestbook_form.label')}</Field.Label>
        <RichTextEditor
          name="body"
          minimal
          placeholder={placeholder ?? t('guestbook_form.placeholder')}
          invalid={!!errors.body || !!errors.parent_id}
        />
        <Field.Feedback message={errors.body ?? errors.parent_id} />
      </Field.Group>

      <CaptchaSubmitRow error={errors['cf-turnstile-response']}>
        <button type="submit" class="btn btn-primary" disabled={processing}
          
          >{submitLabel ?? t('guestbook_form.submit')}</button
        >
      </CaptchaSubmitRow>

      {#if isGuest}
        <p class="small opacity-75 mb-0">
          {t('guestbook_form.guest_note')}
        </p>
      {/if}
    {/snippet}
  </Form>
{/key}
