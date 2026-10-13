<script lang="ts">
  import { Form } from '@inertiajs/svelte';
  import FormModal from '@/components/form-modal.svelte';
  import { Field } from '@/components/ui/field';
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
  title="Ubah Pesan"
  subtitle="Sunting isi atau ubah status moderasi."
>
  {#snippet children()}
    {#if entry}
      <Form
        {...update.form(entry.id)}
        class="d-flex flex-column gap-3"
        novalidate
        onSuccess={() => (open = false)}
      >
        {#snippet children({ errors, processing })}
          <Field.Group>
            <Field.Label for="edit-entry-body">Isi Pesan</Field.Label>
            <textarea
              id="edit-entry-body"
              name="body"
              rows="5"
              maxlength="1000"
              required
              class="form-control"
              class:is-invalid={!!errors.body}>{entry.body}</textarea
            >
            <Field.Feedback message={errors.body} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-entry-status">Status</Field.Label>
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
              onclick={() => (open = false)}>Batal</button
            >
            <button type="submit" class="btn btn-primary" disabled={processing}
              >Simpan</button
            >
          </div>
        {/snippet}
      </Form>
    {/if}
  {/snippet}
</FormModal>
