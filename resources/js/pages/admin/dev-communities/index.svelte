<script lang="ts">
  import TranslatedField from '@/components/admin/translated-field.svelte';
  import { t, intlLocale } from '@/lib/i18n';
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
    translations?: Record<string, Record<string, string>>;
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

<AppHead title={t('admin-dev-communities.komunitas_dev_id')} />

<AdminPageHeader
  title={t('admin-dev-communities.komunitas_dev_id')}
  subtitle={t('admin-dev-communities.kelola_daftar_komunitas_yang_tampil_di')}
  onCreate={() => (createOpen = true)}
/>

<DataTable
  data={communities}
  scrollKey="communities"
  {filters}
  url={index().url}
  columns={[
    { label: t('admin.nama'), key: 'name', sortable: true },
    { label: t('admin.platform'), key: 'platform', sortable: true },
    { label: t('admin-dev-communities.anggota') },
    { label: t('admin-dev-communities.unggulan'), key: 'is_featured', sortable: true },
    { label: t('admin-media.aksi'), align: 'end' },
  ]}
  emptyTitle={t('admin-dev-communities.belum_ada_komunitas')}
  emptyText={t('admin-dev-communities.tambahkan_komunitas_pertama')}
>
  {#snippet row(item)}
    <tr>
      <td>
        <div class="fw-semibold">{item.name}</div>
        <a href={item.invite_url} class="small" target="_blank" rel="noopener noreferrer nofollow">{item.invite_url}</a>
      </td>
      <td>{platformLabel(item.platform)}</td>
      <td>{item.member_count?.toLocaleString(intlLocale()) ?? '-'}</td>
      <td>{item.is_featured ? t('admin.ya') : t('admin.tidak')}</td>
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick={() => openEdit(item)}>{t('admin.ubah')}</button>
          <AdminDeleteButton
            href={destroy(item.id).url}
            label={t('admin-dev-communities.delete_confirm', { name: item.name })}
          />
        </div>
      </td>
    </tr>
  {/snippet}
</DataTable>

<FormModal bind:open={createOpen} title={t('admin-dev-communities.tambah_komunitas')} subtitle={t('admin-dev-communities.catat_komunitas_baru')}>
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
          <Field.Label for="create-invite_url">{t('admin-dev-communities.tautan_undangan')}</Field.Label>
          <Field.Input placeholder="https://" id="create-invite_url" name="invite_url" type="url" required invalid={!!errors.invite_url} />
          <Field.Feedback message={errors.invite_url} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-member_count">{t('admin-dev-communities.jumlah_anggota')}</Field.Label>
          <Field.Input placeholder={t('admin-dev-communities.opsional')} id="create-member_count" name="member_count" type="number" min="0" invalid={!!errors.member_count} />
          <Field.Feedback message={errors.member_count} />
        </Field.Group>

        <TranslatedField field="description" label={t('admin.deskripsi')} id="create-description" placeholder={t('admin.masukkan_deskripsi')} multiline rows={3} {errors} />

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

<FormModal bind:open={editOpen} title={t('admin-dev-communities.ubah_komunitas')} subtitle={editing?.name ?? ''}>
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
          <Field.Label for="edit-invite_url">{t('admin-dev-communities.tautan_undangan')}</Field.Label>
          <Field.Input placeholder="https://" id="edit-invite_url" name="invite_url" type="url" required value={current.invite_url} invalid={!!errors.invite_url} />
          <Field.Feedback message={errors.invite_url} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="edit-member_count">{t('admin-dev-communities.jumlah_anggota')}</Field.Label>
          <Field.Input placeholder={t('admin-dev-communities.opsional')} id="edit-member_count" name="member_count" type="number" min="0" value={current.member_count} invalid={!!errors.member_count} />
          <Field.Feedback message={errors.member_count} />
        </Field.Group>

        <TranslatedField field="description" label={t('admin.deskripsi')} id="edit-description" placeholder={t('admin.masukkan_deskripsi')} multiline rows={3} {errors} />

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
