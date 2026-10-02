<script lang="ts">
  import { Form } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import MediaField from '@/components/media/media-field.svelte';
  import { Field } from '@/components/ui/field';
  import Select from '@/components/ui/select.svelte';
  import { update } from '@/wayfinder/routes/profile';

  type Option = { value: string; label: string };

  let {
    profile,
    genders,
    mustVerifyEmail = false,
  }: {
    profile: {
      name: string;
      email: string;
      phone: string | null;
      gender: string | null;
      avatar: string | null;
    };
    genders: Option[];
    mustVerifyEmail?: boolean;
  } = $props();
</script>

<AppHead title="Profil Saya" />

<div id="profile-settings-page" style="max-width: 720px;">
  <AdminPageHeader
    title="Profil Saya"
    subtitle="Perbarui informasi akun dan foto profil Anda."
  />

  <div class="card mb-4">
    <div class="card-body">
      <Form
        {...update.form()}
        class="d-flex flex-column gap-3"
        novalidate
        options={{ preserveScroll: true }}
      >
        {#snippet children({ errors, processing })}
          <Field.Group>
            <Field.Label for="profile-avatar">Foto Profil</Field.Label>
            <MediaField
              name="avatar"
              value={profile.avatar}
              invalid={!!errors.avatar}
            />
            <Field.Feedback message={errors.avatar} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="profile-name">Nama</Field.Label>
            <Field.Input
              id="profile-name"
              name="name"
              required
              autocomplete="name"
              value={profile.name}
              invalid={!!errors.name}
            />
            <Field.Feedback message={errors.name} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="profile-email">Email</Field.Label>
            <Field.Input
              id="profile-email"
              name="email"
              type="email"
              required
              autocomplete="email"
              value={profile.email}
              invalid={!!errors.email}
            />
            <Field.Feedback message={errors.email} />
            {#if mustVerifyEmail}
              <p class="text-muted small mb-0">
                Mengubah email akan mengharuskan verifikasi ulang.
              </p>
            {/if}
          </Field.Group>

          <div class="row g-3">
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="profile-phone">Telepon</Field.Label>
                <Field.Input
                  id="profile-phone"
                  name="phone"
                  autocomplete="tel"
                  value={profile.phone}
                  invalid={!!errors.phone}
                />
                <Field.Feedback message={errors.phone} />
              </Field.Group>
            </div>
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="profile-gender">Jenis Kelamin</Field.Label>
                <Select
                  id="profile-gender"
                  name="gender"
                  items={genders}
                  value={profile.gender}
                  invalid={!!errors.gender}
                  placeholder="—"
                />
                <Field.Feedback message={errors.gender} />
              </Field.Group>
            </div>
          </div>

          <div>
            <button
              type="submit"
              class="btn btn-primary"
              disabled={processing}
              data-test="update-profile-button"
            >
              Simpan
            </button>
          </div>
        {/snippet}
      </Form>
    </div>
  </div>
</div>
