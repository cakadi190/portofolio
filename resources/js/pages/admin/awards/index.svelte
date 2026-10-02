<script lang="ts">
  import DatePicker from '@/components/ui/date-picker.svelte';
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import { Field } from '@/components/ui/field';
  import Select from '@/components/ui/select.svelte';
  import Icon from '@iconify/svelte';
  import { Form } from '@inertiajs/svelte';
  import {
    destroy,
    store,
    update,
    index,
  } from '@/wayfinder/routes/admin/awards';
  import type { Paginated, TableFilters } from '@/types/pagination';

  type Award = {
    id: number;
    event_name: string;
    title: string;
    type: string;
    icon: string;
    year: number;
    rank: number | null;
    awarded_at: string | null;
  };

  let {
    awards,
    types,
    filters,
  }: {
    filters: TableFilters;
    awards: Paginated<Award>;
    types: { value: string; label: string }[];
  } = $props();

  const typeLabels = $derived(
    Object.fromEntries(types.map((type) => [type.value, type.label])),
  );

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

<DataTable
  data={awards}
  {filters}
  url={index().url}
  columns={[
    { label: 'Ikon' },
    { label: 'Judul', key: 'title', sortable: true },
    { label: 'Jenis', key: 'type', sortable: true },
    { label: 'Acara', key: 'event_name', sortable: true },
    { label: 'Tahun', key: 'year', sortable: true },
    { label: 'Peringkat', key: 'rank', sortable: true },
    { label: 'Aksi', align: 'end' },
  ]}
  emptyTitle="Belum ada penghargaan"
  emptyText="Tambahkan penghargaan pertama Anda."
>
  {#snippet row(award)}
    <tr>
      <td>
        <Icon icon={award.icon} width={28} height={28} />
      </td>
      <td>{award.title}</td>
      <td class="text-nowrap">{typeLabels[award.type] ?? award.type}</td>
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
  {/snippet}
</DataTable>

<FormModal
  bind:open={createOpen}
  title="Tambah Penghargaan"
  subtitle="Catat penghargaan atau sertifikat baru."
>
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
          <Field.Input placeholder="Masukkan judul"
            id="create-title"
            name="title"
            required
            invalid={!!errors.title}
          />
          <Field.Feedback message={errors.title} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-type">Jenis Penghargaan</Field.Label>
          <Select
            id="create-type"
            name="type"
            items={types}
            required
            invalid={!!errors.type}
          />
          <Field.Feedback message={errors.type} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-event_name">Nama Acara</Field.Label>
          <Field.Input placeholder="Masukkan nama acara"
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
              <Field.Input placeholder="0"
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
              <Field.Input placeholder="0"
                id="create-rank"
                name="rank"
                type="number"
                invalid={!!errors.rank}
              />
              <Field.Feedback message={errors.rank} />
            </Field.Group>
          </div>
        </div>

        <Field.Group>
          <Field.Label for="create-awarded_at">Tanggal Diberikan</Field.Label>
          <DatePicker
            id="create-awarded_at"
            name="awarded_at"
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
          <button type="submit" class="btn btn-primary" disabled={processing}
            >Simpan</button
          >
        </div>
      {/snippet}
    </Form>
  {/snippet}
</FormModal>

<FormModal
  bind:open={editOpen}
  title="Ubah Penghargaan"
  subtitle={editingAward?.title ?? ''}
>
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
            <Field.Input placeholder="Masukkan judul"
              id="edit-title"
              name="title"
              required
              value={editingAward.title}
              invalid={!!errors.title}
            />
            <Field.Feedback message={errors.title} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-type">Jenis Penghargaan</Field.Label>
            <Select
              id="edit-type"
              name="type"
              items={types}
              required
              value={editingAward.type}
              invalid={!!errors.type}
            />
            <Field.Feedback message={errors.type} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-event_name">Nama Acara</Field.Label>
            <Field.Input placeholder="Masukkan nama acara"
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
                <Field.Input placeholder="0"
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
                <Field.Input placeholder="0"
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
            <DatePicker
              id="edit-awarded_at"
              name="awarded_at"
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
            <button type="submit" class="btn btn-primary" disabled={processing}
              >Simpan</button
            >
          </div>
        {/snippet}
      </Form>
    {/if}
  {/snippet}
</FormModal>
