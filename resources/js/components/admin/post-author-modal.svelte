<script lang="ts">
  import { Form } from '@inertiajs/svelte';
  import FormModal from '@/components/form-modal.svelte';
  import { Field } from '@/components/ui/field';
  import Select from '@/components/ui/select.svelte';
  import { update } from '@/wayfinder/routes/admin/posts/author';

  type Author = { id: number; name: string };

  let {
    open = $bindable(false),
    post,
    authors,
  }: {
    open: boolean;
    post: { id: number; title: string; user_id: number | null } | null;
    authors: Author[];
  } = $props();
</script>

<FormModal
  bind:open
  title="Ganti Penulis"
  subtitle={post?.title ?? ''}
>
  {#snippet children()}
    {#if post}
      <Form
        {...update.form(post.id)}
        class="d-flex flex-column gap-3"
        novalidate
        onSuccess={() => (open = false)}
      >
        {#snippet children({ errors, processing })}
          <Field.Group>
            <Field.Label for="post-author-select">Penulis</Field.Label>
            <Select
              id="post-author-select"
              name="user_id"
              items={authors.map((a) => ({ value: a.id, label: a.name }))}
              value={post.user_id}
              required
              invalid={!!errors.user_id}
            />
            <Field.Feedback message={errors.user_id} />
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
