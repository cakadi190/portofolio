<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import { Field } from '@/components/ui/field';
  import Icon from '@iconify/svelte';
  import { Form } from '@inertiajs/svelte';
  import { destroy, store, update } from '@/wayfinder/routes/admin/awards';
  import type { Paginated } from '@/types/pagination';

  type Award = {
    id: number;
    event_name: string;
    title: string;
    icon: string;
    year: number;
    rank: number | null;
    awarded_at: string | null;
  };

  let { awards }: { awards: Paginated<Award> } = $props();

  let createOpen = $state(false);
  let editOpen = $state(false);
  let editingAward = $state<Award | null>(null);

  function openEdit(award: Award): void {
    editingAward = award;
    editOpen = true;
  }
</script>

<AppHead title="Penghargaan" />

<AdminPageHeader
  title="Penghargaan"
  subtitle="Kelola penghargaan dan sertifikat Anda."
  onCreate={() => (createOpen = true)}
/>

{#if awards.data.length === 0}
  <EmptyState title="Belum ada penghargaan" text="Tambahkan penghargaan pertama Anda." />
{:else}
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Ikon</th>
          <th>Judul</th>
          <th>Acara</th>
          <th>Tahun</th>
          <th>Peringkat</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        {#each awards.data as award (award.id)}
          <tr>
            <td>
              <Icon icon={award.icon} width={28} height={28} />
            </td>
            <td>{award.title}</td>
            <td>{award.event_name}</td>
            <td>{award.year}</td>
            <td>{award.rank ?? '—'}</td>
            <td class="text-end">
              <div class="d-inline-flex gap-2">
                <button
                  type="button"
                  class="btn btn-sm btn-outline-secondary"
                  onclick={() => openEdit(award)}
                >
                  Ubah
                </button>
                <AdminDeleteButton
                  href={destroy(award.id).url}
                  label={`Hapus penghargaan "${award.title}"?`}
                />
              </div>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>

  <SimplePaginator
    currentPage={awards.current_page}
    lastPage={awards.last_page}
    prevPageUrl={awards.prev_page_url}
    nextPageUrl={awards.next_page_url}
  />
{/if}

<FormModal bind:open={createOpen} title="Tambah Penghargaan" subtitle="Catat penghargaan atau sertifikat baru.">
  {#snippet children()}
    <Form
      {...store.form()}
      class="d-flex flex-column gap-3"
      novalidate
      onSuccess={() => (createOpen = false)}
    >
      {#snippet children({ errors, processing })}
        <Field.Group>
          <Field.Label for="create-title">Judul</Field.Label>
          <Field.Input id="create-title" name="title" required invalid={!!errors.title} />
          <Field.Feedback message={errors.title} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-event_name">Nama Acara</Field.Label>
          <Field.Input
            id="create-event_name"
            name="event_name"
            required
            invalid={!!errors.event_name}
          />
          <Field.Feedback message={errors.event_name} />
        </Field.Group>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-year">Tahun</Field.Label>
              <Field.Input
                id="create-year"
                name="year"
                type="number"
                required
                invalid={!!errors.year}
              />
              <Field.Feedback message={errors.year} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-rank">Peringkat</Field.Label>
              <Field.Input id="create-rank" name="rank" type="number" invalid={!!errors.rank} />
              <Field.Feedback message={errors.rank} />
            </Field.Group>
          </div>
        </div>

        <Field.Group>
          <Field.Label for="create-awarded_at">Tanggal Diberikan</Field.Label>
          <Field.Input
            id="create-awarded_at"
            name="awarded_at"
            type="date"
            invalid={!!errors.awarded_at}
          />
          <Field.Feedback message={errors.awarded_at} />
        </Field.Group>

        <div class="d-flex justify-content-end gap-2">
          <button
            type="button"
            class="btn btn-outline-secondary"
            onclick={() => (createOpen = false)}
          >
            Batal
          </button>
          <button type="submit" class="btn btn-primary" disabled={processing}>Simpan</button>
        </div>
      {/snippet}
    </Form>
  {/snippet}
</FormModal>

<FormModal bind:open={editOpen} title="Ubah Penghargaan" subtitle={editingAward?.title ?? ''}>
  {#snippet children()}
    {#if editingAward}
      <Form
        {...update.form(editingAward.id)}
        class="d-flex flex-column gap-3"
        novalidate
        onSuccess={() => (editOpen = false)}
      >
        {#snippet children({ errors, processing })}
          <Field.Group>
            <Field.Label for="edit-title">Judul</Field.Label>
            <Field.Input
              id="edit-title"
              name="title"
              required
              value={editingAward.title}
              invalid={!!errors.title}
            />
            <Field.Feedback message={errors.title} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-event_name">Nama Acara</Field.Label>
            <Field.Input
              id="edit-event_name"
              name="event_name"
              required
              value={editingAward.event_name}
              invalid={!!errors.event_name}
            />
            <Field.Feedback message={errors.event_name} />
          </Field.Group>

          <div class="row g-3">
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-year">Tahun</Field.Label>
                <Field.Input
                  id="edit-year"
                  name="year"
                  type="number"
                  required
                  value={editingAward.year}
                  invalid={!!errors.year}
                />
                <Field.Feedback message={errors.year} />
              </Field.Group>
            </div>
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-rank">Peringkat</Field.Label>
                <Field.Input
                  id="edit-rank"
                  name="rank"
                  type="number"
                  value={editingAward.rank}
                  invalid={!!errors.rank}
                />
                <Field.Feedback message={errors.rank} />
              </Field.Group>
            </div>
          </div>

          <Field.Group>
            <Field.Label for="edit-awarded_at">Tanggal Diberikan</Field.Label>
            <Field.Input
              id="edit-awarded_at"
              name="awarded_at"
              type="date"
              value={editingAward.awarded_at}
              invalid={!!errors.awarded_at}
            />
            <Field.Feedback message={errors.awarded_at} />
          </Field.Group>

          <div class="d-flex justify-content-end gap-2">
            <button
              type="button"
              class="btn btn-outline-secondary"
              onclick={() => (editOpen = false)}
            >
              Batal
            </button>
            <button type="submit" class="btn btn-primary" disabled={processing}>Simpan</button>
          </div>
        {/snippet}
      </Form>
    {/if}
  {/snippet}
</FormModal>
