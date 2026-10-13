<script lang="ts">
  import { Form, page } from '@inertiajs/svelte';
  import Turnstile from '@/components/turnstile.svelte';
  import { Field } from '@/components/ui/field';
  import RichTextEditor from '@/components/ui/rich-text-editor.svelte';
  import { store } from '@/wayfinder/routes/guestbook';

  let {
    parentId = null,
    label = 'Tulis pesan',
    submitLabel = 'Kirim Pesan',
    placeholder = 'Halo, saya mampir dari…',
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
    {...store.form()}
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
            <Field.Label for={`guest-name-${parentId ?? 'root'}`}>Nama</Field.Label>
            <Field.Input
              id={`guest-name-${parentId ?? 'root'}`}
              name="guest_name"
              placeholder="Mis: Cak Adi"
              invalid={!!errors.guest_name}
            />
            <Field.Feedback message={errors.guest_name} />
          </Field.Group>
          <Field.Group class="col-md-6">
            <Field.Label for={`guest-email-${parentId ?? 'root'}`}>Email</Field.Label>
            <Field.Input
              id={`guest-email-${parentId ?? 'root'}`}
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
        <Field.Label>{label}</Field.Label>
        <RichTextEditor
          name="body"
          minimal
          {placeholder}
          invalid={!!errors.body || !!errors.parent_id}
        />
        <Field.Feedback message={errors.body ?? errors.parent_id} />
      </Field.Group>

      <Turnstile error={errors['cf-turnstile-response']} />

      <div>
        <button type="submit" class="btn btn-primary" disabled={processing}>{submitLabel}</button>
      </div>

      {#if isGuest}
        <p class="small opacity-75 mb-0">
          Email tidak ditampilkan. Pesan tamu tampil setelah ditinjau.
        </p>
      {/if}
    {/snippet}
  </Form>
{/key}
