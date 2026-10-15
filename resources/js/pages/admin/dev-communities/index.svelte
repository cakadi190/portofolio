<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import { Field } from '@/components/ui/field';
  import Select from '@/components/ui/select.svelte';
  import { Form } from '@inertiajs/svelte';
  import {
    destroy,
    store,
    update,
    index,
  } from '@/wayfinder/routes/admin/dev-communities';
  import type { InfiniteList, TableFilters } from '@/types/pagination';

  type Item = {
    id: number;
    name: string;
    platform: string;
    description: string | null;
    invite_url: string;
    member_count: number | null;
    is_featured: boolean;
  };

  let {
    communities,
    platforms,
    filters,
  }: {
    communities: InfiniteList<Item>;
    platforms: { value: string; label: string }[];
    filters: TableFilters;
  } = $props();

  let createOpen = $state(false);
  let editOpen = $state(false);
  let editing = $state<Item | null>(null);

  function openEdit(item: Item): void {
    editing = item;
    editOpen = true;
  }

  function platformLabel(value: string): string {
    return platforms.find((platform) => platform.value === value)?.label ?? value;
  }
</script>

<AppHead title="Komunitas Dev ID" />

<AdminPageHeader
  title="Komunitas Dev ID"
  subtitle="Kelola daftar komunitas yang tampil di halaman publik."
  onCreate={() => (createOpen = true)}
/>

<DataTable
  data={communities}
  scrollKey="communities"
  {filters}
  url={index().url}
  columns={[
    { label: 'Nama', key: 'name', sortable: true },
    { label: 'Platform', key: 'platform', sortable: true },
    { label: 'Anggota' },
    { label: 'Unggulan', key: 'is_featured', sortable: true },
    { label: 'Aksi', align: 'end' },
  ]}
  emptyTitle="Belum ada komunitas"
  emptyText="Tambahkan komunitas pertama."
>
  {#snippet row(item)}
    <tr>
      <td>
        <div class="fw-semibold">{item.name}</div>
        <a href={item.invite_url} class="small" target="_blank" rel="noopener noreferrer nofollow">{item.invite_url}</a>
      </td>
      <td>{platformLabel(item.platform)}</td>
      <td>{item.member_count?.toLocaleString('id-ID') ?? '-'}</td>
      <td>{item.is_featured ? 'Ya' : 'Tidak'}</td>
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick={() => openEdit(item)}>Ubah</button>
          <AdminDeleteButton
            href={destroy(item.id).url}
            label={`Hapus komunitas "${item.name}"?`}
          />
        </div>
      </td>
    </tr>
  {/snippet}
</DataTable>

<FormModal bind:open={createOpen} title="Tambah Komunitas" subtitle="Catat komunitas baru.">
  {#snippet children()}
    <Form {...store.form()} class="d-flex flex-column gap-3" novalidate onSuccess={() => (createOpen = false)}>
      {#snippet children({ errors, processing })}
        <Field.Group>
          <Field.Label for="create-name">Nama</Field.Label>
          <Field.Input placeholder="Masukkan nama" id="create-name" name="name" required invalid={!!errors.name} />
          <Field.Feedback message={errors.name} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-platform">Platform</Field.Label>
          <Select id="create-platform" name="platform" items={platforms} required invalid={!!errors.platform} />
          <Field.Feedback message={errors.platform} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-invite_url">Tautan Undangan</Field.Label>
          <Field.Input placeholder="https://" id="create-invite_url" name="invite_url" type="url" required invalid={!!errors.invite_url} />
          <Field.Feedback message={errors.invite_url} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-member_count">Jumlah Anggota</Field.Label>
          <Field.Input placeholder="Opsional" id="create-member_count" name="member_count" type="number" min="0" invalid={!!errors.member_count} />
          <Field.Feedback message={errors.member_count} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-description">Deskripsi</Field.Label>
          <textarea placeholder="Masukkan deskripsi" id="create-description" name="description" class="form-control" class:is-invalid={!!errors.description} rows="3"></textarea>
          <Field.Feedback message={errors.description} />
        </Field.Group>

        <input type="hidden" name="is_featured" value="0" />
        <Field.Input.Check id="create-is_featured" name="is_featured" value="1">
          Tampilkan sebagai unggulan
        </Field.Input.Check>

        <div class="d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-outline-secondary" onclick={() => (createOpen = false)}>Batal</button>
          <button type="submit" class="btn btn-primary" disabled={processing}>Simpan</button>
        </div>
      {/snippet}
    </Form>
  {/snippet}
</FormModal>

<FormModal bind:open={editOpen} title="Ubah Komunitas" subtitle={editing?.name ?? ''}>
  {#snippet children()}
    {#if editing}
      {@const current = editing}
      <Form {...update.form(current.id)} class="d-flex flex-column gap-3" novalidate onSuccess={() => (editOpen = false)}>
        {#snippet children({ errors, processing })}
        <Field.Group>
          <Field.Label for="edit-name">Nama</Field.Label>
          <Field.Input placeholder="Masukkan nama" id="edit-name" name="name" required value={current.name} invalid={!!errors.name} />
          <Field.Feedback message={errors.name} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="edit-platform">Platform</Field.Label>
          <Select id="edit-platform" name="platform" items={platforms} value={current.platform} required invalid={!!errors.platform} />
          <Field.Feedback message={errors.platform} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="edit-invite_url">Tautan Undangan</Field.Label>
          <Field.Input placeholder="https://" id="edit-invite_url" name="invite_url" type="url" required value={current.invite_url} invalid={!!errors.invite_url} />
          <Field.Feedback message={errors.invite_url} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="edit-member_count">Jumlah Anggota</Field.Label>
          <Field.Input placeholder="Opsional" id="edit-member_count" name="member_count" type="number" min="0" value={current.member_count} invalid={!!errors.member_count} />
          <Field.Feedback message={errors.member_count} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="edit-description">Deskripsi</Field.Label>
          <textarea placeholder="Masukkan deskripsi" id="edit-description" name="description" class="form-control" class:is-invalid={!!errors.description} rows="3">{current.description ?? ''}</textarea>
          <Field.Feedback message={errors.description} />
        </Field.Group>

        <input type="hidden" name="is_featured" value="0" />
        <Field.Input.Check id="edit-is_featured" name="is_featured" value="1" checked={current.is_featured}>
          Tampilkan sebagai unggulan
        </Field.Input.Check>

        <div class="d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-outline-secondary" onclick={() => (editOpen = false)}>Batal</button>
          <button type="submit" class="btn btn-primary" disabled={processing}>Simpan</button>
        </div>
        {/snippet}
      </Form>
    {/if}
  {/snippet}
</FormModal>
