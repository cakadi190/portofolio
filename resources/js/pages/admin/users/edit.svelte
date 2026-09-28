<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import { Field } from '@/components/ui/field';
  import FileDropzone from '@/components/ui/file-dropzone.svelte';
  import { Form } from '@inertiajs/svelte';
  import { index, update } from '@/wayfinder/routes/admin/users';

  type Option = { value: string; label: string };

  type UserForm = {
    id: number;
    name: string;
    email: string;
    account_type: string;
    phone: string | null;
    gender: string | null;
    avatar: string | null;
  };

  let {
    user,
    accountTypes,
    genders,
  }: { user: UserForm; accountTypes: Option[]; genders: Option[] } = $props();
</script>

<AppHead title="Ubah Pengguna" />

<AdminPageHeader title="Ubah Pengguna" subtitle={user.name} />

<div class="card" style="max-width: 560px;">
  <div class="card-body">
    <Form {...update.form(user.id)} class="d-flex flex-column gap-3" novalidate>
      {#snippet children({ errors, processing })}
        <Field.Group>
          <Field.Label for="name">Nama</Field.Label>
          <Field.Input id="name" name="name" required value={user.name} invalid={!!errors.name} />
          <Field.Feedback message={errors.name} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="email">Email</Field.Label>
          <Field.Input
            id="email"
            name="email"
            type="email"
            required
            value={user.email}
            invalid={!!errors.email}
          />
          <Field.Feedback message={errors.email} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="password">Kata Sandi Baru</Field.Label>
          <Field.Input
            id="password"
            name="password"
            type="password"
            placeholder="Kosongkan jika tidak diubah"
            invalid={!!errors.password}
          />
          <Field.Feedback message={errors.password} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="account_type">Peran</Field.Label>
          <select
            id="account_type"
            name="account_type"
            class="form-select"
            class:is-invalid={!!errors.account_type}
            required
            value={user.account_type}
          >
            {#each accountTypes as option (option.value)}
              <option value={option.value}>{option.label}</option>
            {/each}
          </select>
          <Field.Feedback message={errors.account_type} />
        </Field.Group>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="phone">Telepon</Field.Label>
              <Field.Input id="phone" name="phone" value={user.phone} invalid={!!errors.phone} />
              <Field.Feedback message={errors.phone} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="gender">Jenis Kelamin</Field.Label>
              <select
                id="gender"
                name="gender"
                class="form-select"
                class:is-invalid={!!errors.gender}
                value={user.gender ?? ''}
              >
                <option value="">—</option>
                {#each genders as option (option.value)}
                  <option value={option.value}>{option.label}</option>
                {/each}
              </select>
              <Field.Feedback message={errors.gender} />
            </Field.Group>
          </div>
        </div>

        <Field.Group>
          <Field.Label for="avatar">Foto Profil</Field.Label>
          <FileDropzone
            name="avatar"
            existingUrl={user.avatar ? `/storage/${user.avatar}` : null}
            invalid={!!errors.avatar}
          />
          <Field.Feedback message={errors.avatar} />
        </Field.Group>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary" disabled={processing}>Simpan</button>
          <a href={index().url} class="btn btn-outline-secondary">Batal</a>
        </div>
      {/snippet}
    </Form>
  </div>
</div>
