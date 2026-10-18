<script lang="ts">
  import { t } from '@/lib/i18n';
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import { Field } from '@/components/ui/field';
  import Select from '@/components/ui/select.svelte';
  import MediaField from '@/components/media/media-field.svelte';
  import { Form } from '@inertiajs/svelte';
  import { storageUrl } from '@/lib/utils';
  import {
    destroy,
    store,
    update,
    index,
  } from '@/wayfinder/routes/admin/users';
  import type { Paginated, TableFilters } from '@/types/pagination';

  type Option = { value: string; label: string };

  type UserRow = {
    id: number;
    name: string;
    email: string;
    account_type: string;
    phone: string | null;
    gender: string | null;
    avatar: string | null;
  };

  let {
    users,
    accountTypes,
    genders,
    filters,
  }: {
    filters: TableFilters;
    users: Paginated<UserRow>;
    accountTypes: Option[];
    genders: Option[];
  } = $props();

  let createOpen = $state(false);
  let editOpen = $state(false);
  let editingUser = $state<UserRow | null>(null);

  const roleHint =
    t('admin-users.admin_akses_penuh_redaktur_hanya_mengelola');

  function roleLabel(value: string): string {
    return accountTypes.find((type) => type.value === value)?.label ?? value;
  }

  function openEdit(user: UserRow): void {
    editingUser = user;
    editOpen = true;
  }
</script>

<AppHead title={t('admin.pengguna')} />

<AdminPageHeader
  title={t('admin.pengguna')}
  subtitle={t('admin-users.kelola_akun_pengguna_dan_admin')}
  onCreate={() => (createOpen = true)}
/>

<DataTable
  data={users}
  {filters}
  url={index().url}
  columns={[
    { label: t('admin-users.avatar') },
    { label: t('admin.nama'), key: 'name', sortable: true },
    { label: t('admin-users.email'), key: 'email', sortable: true },
    { label: t('admin.peran'), key: 'account_type', sortable: true },
    { label: t('admin-users.telepon'), key: 'phone', sortable: true },
    { label: t('admin-media.aksi'), align: 'end' },
  ]}
  emptyTitle={t('admin-users.belum_ada_pengguna')}
  emptyText={t('admin-users.tambahkan_pengguna_pertama_anda')}
>
  {#snippet row(user)}
    <tr>
      <td>
        {#if user.avatar}
          <img
            src={storageUrl(user.avatar) ?? ''}
            alt={user.name}
            style="width:2.5rem;height:2.5rem;object-fit:cover;border-radius:50%;"
          />
        {:else}
          <span class="text-muted">&mdash;</span>
        {/if}
      </td>
      <td>{user.name}</td>
      <td>{user.email}</td>
      <td>
        <span
          class="badge {user.account_type === 'admin'
            ? 'text-bg-success'
            : user.account_type === 'redaktur'
              ? 'text-bg-info'
              : 'text-bg-secondary'}"
        >
          {roleLabel(user.account_type)}
        </span>
      </td>
      <td>{user.phone ?? '—'}</td>
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            onclick={() => openEdit(user)}
          >
            {t('admin.ubah')}
          </button>
          <AdminDeleteButton
            href={destroy(user.id).url}
            label={t('admin-users.delete_confirm', { name: user.name })}
          />
        </div>
      </td>
    </tr>
  {/snippet}
</DataTable>

<FormModal
  bind:open={createOpen}
  title={t('admin-users.tambah_pengguna')}
  subtitle={t('admin-users.buat_akun_pengguna_atau_admin_baru')}
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
          <Field.Label for="create-name">{t('admin.nama')}</Field.Label>
          <Field.Input placeholder={t('admin.masukkan_nama')}
            id="create-name"
            name="name"
            required
            invalid={!!errors.name}
          />
          <Field.Feedback message={errors.name} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-email">{t('admin-users.email')}</Field.Label>
          <Field.Input placeholder="nama@contoh.com"
            id="create-email"
            name="email"
            type="email"
            required
            invalid={!!errors.email}
          />
          <Field.Feedback message={errors.email} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-password">{t('admin-users.kata_sandi')}</Field.Label>
          <Field.Input placeholder="••••••••"
            id="create-password"
            name="password"
            type="password"
            required
            invalid={!!errors.password}
          />
          <Field.Feedback message={errors.password} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-account_type">{t('admin.peran')}</Field.Label>
          <Select
            id="create-account_type"
            name="account_type"
            items={accountTypes}
            required
            invalid={!!errors.account_type}
          />
          <div class="form-text">{roleHint}</div>
<Field.Feedback message={errors.account_type} />
        </Field.Group>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-phone">{t('admin-users.telepon')}</Field.Label>
              <Field.Input placeholder={t('admin-users.08xxxxxxxxxx')}
                id="create-phone"
                name="phone"
                invalid={!!errors.phone}
              />
              <Field.Feedback message={errors.phone} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-gender">{t('admin-users.jenis_kelamin')}</Field.Label>
              <Select
                id="create-gender"
                name="gender"
                items={genders}
                invalid={!!errors.gender}
                placeholder="—"
              />
              <Field.Feedback message={errors.gender} />
            </Field.Group>
          </div>
        </div>

        <Field.Group>
          <Field.Label for="create-avatar">{t('admin-users.foto_profil')}</Field.Label>
          <MediaField name="avatar" invalid={!!errors.avatar} />
          <Field.Feedback message={errors.avatar} />
        </Field.Group>

        <div class="d-flex justify-content-end gap-2">
          <button
            type="button"
            class="btn btn-outline-secondary"
            onclick={() => (createOpen = false)}
          >
            {t('admin.batal')}
          </button>
          <button type="submit" class="btn btn-primary" disabled={processing}
            >{t('admin.simpan')}</button
          >
        </div>
      {/snippet}
    </Form>
  {/snippet}
</FormModal>

<FormModal
  bind:open={editOpen}
  title={t('admin-users.ubah_pengguna')}
  subtitle={editingUser?.name ?? ''}
>
  {#snippet children()}
    {#if editingUser}
      {@const current = editingUser}
      <Form
        {...update.form(current.id)}
        class="d-flex flex-column gap-3"
        novalidate
        onSuccess={() => (editOpen = false)}
      >
        {#snippet children({ errors, processing })}
          <Field.Group>
            <Field.Label for="edit-name">{t('admin.nama')}</Field.Label>
            <Field.Input placeholder={t('admin.masukkan_nama')}
              id="edit-name"
              name="name"
              required
              value={current.name}
              invalid={!!errors.name}
            />
            <Field.Feedback message={errors.name} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-email">{t('admin-users.email')}</Field.Label>
            <Field.Input placeholder="nama@contoh.com"
              id="edit-email"
              name="email"
              type="email"
              required
              value={current.email}
              invalid={!!errors.email}
            />
            <Field.Feedback message={errors.email} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-password">{t('admin-users.kata_sandi_baru')}</Field.Label>
            <Field.Input
              id="edit-password"
              name="password"
              type="password"
              placeholder={t('admin-users.kosongkan_jika_tidak_diubah')}
              invalid={!!errors.password}
            />
            <Field.Feedback message={errors.password} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-account_type">{t('admin.peran')}</Field.Label>
            <Select
              id="edit-account_type"
              name="account_type"
              items={accountTypes}
              value={current.account_type}
              required
              invalid={!!errors.account_type}
            />
            <div class="form-text">{roleHint}</div>
<Field.Feedback message={errors.account_type} />
          </Field.Group>

          <div class="row g-3">
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-phone">{t('admin-users.telepon')}</Field.Label>
                <Field.Input placeholder={t('admin-users.08xxxxxxxxxx')}
                  id="edit-phone"
                  name="phone"
                  value={current.phone}
                  invalid={!!errors.phone}
                />
                <Field.Feedback message={errors.phone} />
              </Field.Group>
            </div>
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-gender">{t('admin-users.jenis_kelamin')}</Field.Label>
                <Select
                  id="edit-gender"
                  name="gender"
                  items={genders}
                  value={current.gender}
                  invalid={!!errors.gender}
                  placeholder="—"
                />
                <Field.Feedback message={errors.gender} />
              </Field.Group>
            </div>
          </div>

          <Field.Group>
            <Field.Label for="edit-avatar">{t('admin-users.foto_profil')}</Field.Label>
            <MediaField
              name="avatar"
              value={current.avatar}
              invalid={!!errors.avatar}
            />
            <Field.Feedback message={errors.avatar} />
          </Field.Group>

          <div class="d-flex justify-content-end gap-2">
            <button
              type="button"
              class="btn btn-outline-secondary"
              onclick={() => (editOpen = false)}
            >
              {t('admin.batal')}
            </button>
            <button type="submit" class="btn btn-primary" disabled={processing}
              >{t('admin.simpan')}</button
            >
          </div>
        {/snippet}
      </Form>
    {/if}
  {/snippet}
</FormModal>
