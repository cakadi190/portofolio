<script lang="ts">
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

  function openEdit(user: UserRow): void {
    editingUser = user;
    editOpen = true;
  }
</script>

<AppHead title="Pengguna" />

<AdminPageHeader
  title="Pengguna"
  subtitle="Kelola akun pengguna dan admin."
  onCreate={() => (createOpen = true)}
/>

<DataTable
  data={users}
  {filters}
  url={index().url}
  columns={[
    { label: 'Avatar' },
    { label: 'Nama', key: 'name', sortable: true },
    { label: 'Email', key: 'email', sortable: true },
    { label: 'Peran', key: 'account_type', sortable: true },
    { label: 'Telepon', key: 'phone', sortable: true },
    { label: 'Aksi', align: 'end' },
  ]}
  emptyTitle="Belum ada pengguna"
  emptyText="Tambahkan pengguna pertama Anda."
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
      <td>{user.account_type}</td>
      <td>{user.phone ?? '—'}</td>
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            onclick={() => openEdit(user)}
          >
            Ubah
          </button>
          <AdminDeleteButton
            href={destroy(user.id).url}
            label={`Hapus pengguna "${user.name}"?`}
          />
        </div>
      </td>
    </tr>
  {/snippet}
</DataTable>

<FormModal
  bind:open={createOpen}
  title="Tambah Pengguna"
  subtitle="Buat akun pengguna atau admin baru."
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
          <Field.Label for="create-name">Nama</Field.Label>
          <Field.Input
            id="create-name"
            name="name"
            required
            invalid={!!errors.name}
          />
          <Field.Feedback message={errors.name} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-email">Email</Field.Label>
          <Field.Input
            id="create-email"
            name="email"
            type="email"
            required
            invalid={!!errors.email}
          />
          <Field.Feedback message={errors.email} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-password">Kata Sandi</Field.Label>
          <Field.Input
            id="create-password"
            name="password"
            type="password"
            required
            invalid={!!errors.password}
          />
          <Field.Feedback message={errors.password} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-account_type">Peran</Field.Label>
          <Select
            id="create-account_type"
            name="account_type"
            items={accountTypes}
            required
            invalid={!!errors.account_type}
          />
          <Field.Feedback message={errors.account_type} />
        </Field.Group>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-phone">Telepon</Field.Label>
              <Field.Input
                id="create-phone"
                name="phone"
                invalid={!!errors.phone}
              />
              <Field.Feedback message={errors.phone} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-gender">Jenis Kelamin</Field.Label>
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
          <Field.Label for="create-avatar">Foto Profil</Field.Label>
          <MediaField name="avatar" invalid={!!errors.avatar} />
          <Field.Feedback message={errors.avatar} />
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
  title="Ubah Pengguna"
  subtitle={editingUser?.name ?? ''}
>
  {#snippet children()}
    {#if editingUser}
      <Form
        {...update.form(editingUser.id)}
        class="d-flex flex-column gap-3"
        novalidate
        onSuccess={() => (editOpen = false)}
      >
        {#snippet children({ errors, processing })}
          <Field.Group>
            <Field.Label for="edit-name">Nama</Field.Label>
            <Field.Input
              id="edit-name"
              name="name"
              required
              value={editingUser.name}
              invalid={!!errors.name}
            />
            <Field.Feedback message={errors.name} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-email">Email</Field.Label>
            <Field.Input
              id="edit-email"
              name="email"
              type="email"
              required
              value={editingUser.email}
              invalid={!!errors.email}
            />
            <Field.Feedback message={errors.email} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-password">Kata Sandi Baru</Field.Label>
            <Field.Input
              id="edit-password"
              name="password"
              type="password"
              placeholder="Kosongkan jika tidak diubah"
              invalid={!!errors.password}
            />
            <Field.Feedback message={errors.password} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-account_type">Peran</Field.Label>
            <Select
              id="edit-account_type"
              name="account_type"
              items={accountTypes}
              value={editingUser.account_type}
              required
              invalid={!!errors.account_type}
            />
            <Field.Feedback message={errors.account_type} />
          </Field.Group>

          <div class="row g-3">
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-phone">Telepon</Field.Label>
                <Field.Input
                  id="edit-phone"
                  name="phone"
                  value={editingUser.phone}
                  invalid={!!errors.phone}
                />
                <Field.Feedback message={errors.phone} />
              </Field.Group>
            </div>
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-gender">Jenis Kelamin</Field.Label>
                <Select
                  id="edit-gender"
                  name="gender"
                  items={genders}
                  value={editingUser.gender}
                  invalid={!!errors.gender}
                  placeholder="—"
                />
                <Field.Feedback message={errors.gender} />
              </Field.Group>
            </div>
          </div>

          <Field.Group>
            <Field.Label for="edit-avatar">Foto Profil</Field.Label>
            <MediaField
              name="avatar"
              value={editingUser.avatar}
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
