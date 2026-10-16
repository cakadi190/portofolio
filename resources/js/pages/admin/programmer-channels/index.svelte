<script lang="ts">
  import { t } from '@/lib/i18n';
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
  } from '@/wayfinder/routes/admin/programmer-channels';
  import type { InfiniteList, TableFilters } from '@/types/pagination';

  type Item = {
    id: number;
    name: string;
    platform: string;
    description: string | null;
    url: string;
    is_featured: boolean;
  };

  let {
    channels,
    platforms,
    filters,
  }: {
    channels: InfiniteList<Item>;
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

<AppHead title={t('admin-programmer-channels.channel_programmer')} />

<AdminPageHeader
  title={t('admin-programmer-channels.channel_programmer')}
  subtitle={t('admin-programmer-channels.kelola_daftar_channel_yang_tampil_di')}
  onCreate={() => (createOpen = true)}
/>

<DataTable
  data={channels}
  scrollKey="channels"
  {filters}
  url={index().url}
  columns={[
    { label: t('admin.nama'), key: 'name', sortable: true },
    { label: t('admin.platform'), key: 'platform', sortable: true },
    { label: t('admin-dev-communities.unggulan'), key: 'is_featured', sortable: true },
    { label: t('admin-media.aksi'), align: 'end' },
  ]}
  emptyTitle={t('admin-programmer-channels.belum_ada_channel')}
  emptyText={t('admin-programmer-channels.tambahkan_channel_pertama')}
>
  {#snippet row(item)}
    <tr>
      <td>
        <div class="fw-semibold">{item.name}</div>
        <a href={item.url} class="small" target="_blank" rel="noopener noreferrer nofollow">{item.url}</a>
      </td>
      <td>{platformLabel(item.platform)}</td>
      <td>{item.is_featured ? 'Ya' : 'Tidak'}</td>
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick={() => openEdit(item)}>{t('admin.ubah')}</button>
          <AdminDeleteButton
            href={destroy(item.id).url}
            label={`Hapus channel "${item.name}"?`}
          />
        </div>
      </td>
    </tr>
  {/snippet}
</DataTable>

<FormModal bind:open={createOpen} title={t('admin-programmer-channels.tambah_channel')} subtitle={t('admin-programmer-channels.catat_channel_baru')}>
  {#snippet children()}
    <Form {...store.form()} class="d-flex flex-column gap-3" novalidate onSuccess={() => (createOpen = false)}>
      {#snippet children({ errors, processing })}
        <Field.Group>
          <Field.Label for="create-name">{t('admin.nama')}</Field.Label>
          <Field.Input placeholder={t('admin.masukkan_nama')} id="create-name" name="name" required invalid={!!errors.name} />
          <Field.Feedback message={errors.name} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-platform">{t('admin.platform')}</Field.Label>
          <Select id="create-platform" name="platform" items={platforms} required invalid={!!errors.platform} />
          <Field.Feedback message={errors.platform} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-url">{t('admin-programmer-channels.tautan_channel')}</Field.Label>
          <Field.Input placeholder="https://" id="create-url" name="url" type="url" required invalid={!!errors.url} />
          <Field.Feedback message={errors.url} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-description">{t('admin.deskripsi')}</Field.Label>
          <textarea placeholder={t('admin.masukkan_deskripsi')} id="create-description" name="description" class="form-control" class:is-invalid={!!errors.description} rows="3"></textarea>
          <Field.Feedback message={errors.description} />
        </Field.Group>

        <input type="hidden" name="is_featured" value="0" />
        <Field.Input.Check id="create-is_featured" name="is_featured" value="1">
          {t('admin.tampilkan_sebagai_unggulan')}
        </Field.Input.Check>

        <div class="d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-outline-secondary" onclick={() => (createOpen = false)}>{t('admin.batal')}</button>
          <button type="submit" class="btn btn-primary" disabled={processing}>{t('admin.simpan')}</button>
        </div>
      {/snippet}
    </Form>
  {/snippet}
</FormModal>

<FormModal bind:open={editOpen} title={t('admin-programmer-channels.ubah_channel')} subtitle={editing?.name ?? ''}>
  {#snippet children()}
    {#if editing}
      {@const current = editing}
      <Form {...update.form(current.id)} class="d-flex flex-column gap-3" novalidate onSuccess={() => (editOpen = false)}>
        {#snippet children({ errors, processing })}
        <Field.Group>
          <Field.Label for="edit-name">{t('admin.nama')}</Field.Label>
          <Field.Input placeholder={t('admin.masukkan_nama')} id="edit-name" name="name" required value={current.name} invalid={!!errors.name} />
          <Field.Feedback message={errors.name} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="edit-platform">{t('admin.platform')}</Field.Label>
          <Select id="edit-platform" name="platform" items={platforms} value={current.platform} required invalid={!!errors.platform} />
          <Field.Feedback message={errors.platform} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="edit-url">{t('admin-programmer-channels.tautan_channel')}</Field.Label>
          <Field.Input placeholder="https://" id="edit-url" name="url" type="url" required value={current.url} invalid={!!errors.url} />
          <Field.Feedback message={errors.url} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="edit-description">{t('admin.deskripsi')}</Field.Label>
          <textarea placeholder={t('admin.masukkan_deskripsi')} id="edit-description" name="description" class="form-control" class:is-invalid={!!errors.description} rows="3">{current.description ?? ''}</textarea>
          <Field.Feedback message={errors.description} />
        </Field.Group>

        <input type="hidden" name="is_featured" value="0" />
        <Field.Input.Check id="edit-is_featured" name="is_featured" value="1" checked={current.is_featured}>
          {t('admin.tampilkan_sebagai_unggulan')}
        </Field.Input.Check>

        <div class="d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-outline-secondary" onclick={() => (editOpen = false)}>{t('admin.batal')}</button>
          <button type="submit" class="btn btn-primary" disabled={processing}>{t('admin.simpan')}</button>
        </div>
        {/snippet}
      </Form>
    {/if}
  {/snippet}
</FormModal>
