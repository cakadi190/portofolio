<script lang="ts">
  import { Form } from '@inertiajs/svelte';
  import Paperclip from '@lucide/svelte/icons/paperclip';
  import Send from '@lucide/svelte/icons/send';
  import X from '@lucide/svelte/icons/x';
  import MediaPickerModal from '@/components/media/media-picker-modal.svelte';
  import { Field } from '@/components/ui/field';
  import RichTextEditor from '@/components/ui/lazy-rich-text-editor.svelte';
  import { formatBytes } from '@/lib/media';
  import type { MediaItem } from '@/types/media';
  import { reply } from '@/wayfinder/routes/admin/contact-messages';

  /**
   * Compose an emailed reply to a contact message: optional subject (blank
   * falls back to the default), a simple WYSIWYG body and optional media
   * library attachments.
   */
  let {
    messageId,
    defaultSubject,
    onSent,
    onCancel,
  }: {
    messageId: number;
    defaultSubject: string;
    onSent: () => void;
    onCancel: () => void;
  } = $props();

  let attachments = $state<MediaItem[]>([]);
  let pickerOpen = $state(false);

  function attach(media: MediaItem): void {
    if (
      attachments.length < 5 &&
      !attachments.some((item) => item.path === media.path)
    ) {
      attachments = [...attachments, media];
    }
  }
</script>

<Form
  {...reply.form(messageId)}
  class="d-flex flex-column gap-3"
  novalidate
  onSuccess={onSent}
>
  {#snippet children({ errors, processing })}
    <Field.Group>
      <Field.Label for="reply-subject">Subjek (opsional)</Field.Label>
      <Field.Input
        id="reply-subject"
        name="subject"
        placeholder={defaultSubject}
        maxlength={150}
        invalid={!!errors.subject}
      />
      <Field.Feedback message={errors.subject} />
    </Field.Group>

    <Field.Group>
      <Field.Label>Isi Balasan</Field.Label>
      <RichTextEditor
        name="body"
        minimal
        placeholder="Tulis balasan untuk pengirim…"
        invalid={!!errors.body}
      />
      <Field.Feedback message={errors.body} />
    </Field.Group>

    <Field.Group>
      <Field.Label>Lampiran (opsional)</Field.Label>
      {#each attachments as media (media.path)}
        <input type="hidden" name="attachments[]" value={media.path} />
      {/each}
      <ul class="list-unstyled d-flex flex-column gap-2 mb-0">
        {#each attachments as media (media.path)}
          <li
            class="d-flex align-items-center gap-2 border rounded-3 px-3 py-2"
          >
            <Paperclip size={16} />
            <span class="flex-grow-1 text-truncate">{media.name}</span>
            <small class="opacity-75">{formatBytes(media.size)}</small>
            <button
              type="button"
              class="btn btn-sm btn-link text-danger p-0"
              aria-label={`Hapus lampiran ${media.name}`}
              onclick={() =>
                (attachments = attachments.filter(
                  (item) => item.path !== media.path,
                ))}
            >
              <X size={16} />
            </button>
          </li>
        {/each}
      </ul>
      <div>
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2"
          disabled={attachments.length >= 5}
          onclick={() => (pickerOpen = true)}
        >
          <Paperclip size={16} /> Pilih dari Pustaka Media
        </button>
        <small class="d-block opacity-75 mt-1"
          >Maksimal 5 berkas (gambar atau PDF).</small
        >
      </div>
      <Field.Feedback message={errors.attachments ?? errors['attachments.0']} />
    </Field.Group>

    <div class="d-flex justify-content-end gap-2">
      <button type="button" class="btn btn-outline-secondary" onclick={onCancel}
        >Batal</button
      >
      <button
        type="submit"
        class="btn btn-primary d-inline-flex align-items-center gap-2"
        disabled={processing}
      >
        <Send size={16} /> Kirim Balasan
      </button>
    </div>
  {/snippet}
</Form>

<MediaPickerModal
  bind:open={pickerOpen}
  accept="all"
  multiple
  title="Pilih Lampiran"
  onSelect={attach}
/>
