<script lang="ts">
  import { Form } from '@inertiajs/svelte';
  import FormModal from '@/components/form-modal.svelte';
  import { Field } from '@/components/ui/field';
  import Select from '@/components/ui/select.svelte';
  import { update } from '@/wayfinder/routes/admin/blog-comments';

  type Option = { value: string; label: string };

  let {
    open = $bindable(false),
    comment,
    statuses,
  }: {
    open: boolean;
    comment: { id: number; body: string; status: string } | null;
    statuses: Option[];
  } = $props();
</script>

<FormModal
  bind:open
  title="Ubah Komentar"
  subtitle="Sunting isi atau ubah status moderasi."
>
  {#snippet children()}
    {#if comment}
      <Form
        {...update.form(comment.id)}
        class="d-flex flex-column gap-3"
        novalidate
        onSuccess={() => (open = false)}
      >
        {#snippet children({ errors, processing })}
          <Field.Group>
            <Field.Label for="edit-comment-body">Isi Komentar</Field.Label>
            <textarea
              id="edit-comment-body"
              name="body"
              rows="5"
              maxlength="2000"
              required
              class="form-control"
              class:is-invalid={!!errors.body}>{comment.body}</textarea
            >
            <Field.Feedback message={errors.body} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-comment-status">Status</Field.Label>
            <Select
              id="edit-comment-status"
              name="status"
              items={statuses}
              value={comment.status}
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
