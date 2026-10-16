<script lang="ts">
  import { t } from '@/lib/i18n';
  import { Form } from '@inertiajs/svelte';
  import FormModal from '@/components/form-modal.svelte';
  import { Field } from '@/components/ui/field';
  import RichTextEditor from '@/components/ui/rich-text-editor.svelte';
  import { toEditorHtml } from '@/components/admin/guestbook-thread';
  import Select from '@/components/ui/select.svelte';
  import { update } from '@/wayfinder/routes/admin/guestbook-entries';

  type Option = { value: string; label: string };

  let {
    open = $bindable(false),
    entry,
    statuses,
  }: {
    open: boolean;
    entry: { id: number; body: string; status: string } | null;
    statuses: Option[];
  } = $props();
</script>

<FormModal
  bind:open
  title={t('admin.ubah_pesan')}
  subtitle={t('admin.sunting_isi_atau_ubah_status_moderasi')}
>
  {#snippet children()}
    {#if entry}
      {#key entry}
        <Form
          {...update.form(entry.id)}
          class="d-flex flex-column gap-3"
          novalidate
          onSuccess={() => (open = false)}
        >
          {#snippet children({ errors, processing })}
            <Field.Group>
              <Field.Label>{t('admin.isi_pesan')}</Field.Label>
              <RichTextEditor
                name="body"
                minimal
                value={toEditorHtml(entry.body)}
                placeholder={t('admin.tulis_isi_pesan')}
                invalid={!!errors.body}
              />
              <Field.Feedback message={errors.body} />
            </Field.Group>

            <Field.Group>
              <Field.Label for="edit-entry-status">{t('admin.status')}</Field.Label>
              <Select
                id="edit-entry-status"
                name="status"
                items={statuses}
                value={entry.status}
                required
                searchable={false}
                invalid={!!errors.status}
              />
              <Field.Feedback message={errors.status} />
            </Field.Group>

            <div class="d-flex justify-content-end gap-2">
              <button
                type="button"
                class="btn btn-outline-secondary"
                onclick={() => (open = false)}>{t('admin.batal')}</button
              >
              <button
                type="submit"
                class="btn btn-primary"
                disabled={processing}>{t('admin.simpan')}</button
              >
            </div>
          {/snippet}
        </Form>
      {/key}
    {/if}
  {/snippet}
</FormModal>
