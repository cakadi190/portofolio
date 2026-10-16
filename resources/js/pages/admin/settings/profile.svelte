<script lang="ts">
  import TranslationBar from '@/components/admin/translation-bar.svelte';
  import TranslationHiddenInputs from '@/components/admin/translation-hidden-inputs.svelte';
  import { createTranslatable, fieldName } from '@/lib/translatable.svelte';
  import { Form } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import SettingsAccountNav from '@/components/admin/settings-account-nav.svelte';
  import SettingsLayout from '@/components/admin/settings-layout.svelte';
  import MediaField from '@/components/media/media-field.svelte';
  import { Field } from '@/components/ui/field';
  import RichTextEditor from '@/components/ui/rich-text-editor.svelte';
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
      username: string | null;
      email: string;
      phone: string | null;
      gender: string | null;
      avatar: string | null;
      bio: string | null;
      translations: Record<string, Record<string, string>>;
    };
    genders: Option[];
    mustVerifyEmail?: boolean;
  } = $props();

  // svelte-ignore state_referenced_locally
  const translatable = createTranslatable(profile.translations ?? {}, { bio: 'html' });
</script>

<AppHead title="Profil Saya" />

<div id="profile-settings-page">
  <AdminPageHeader
    title="Profil Saya"
    subtitle="Perbarui informasi akun dan foto profil Anda."
  />

  <SettingsLayout>
    {#snippet nav()}
      <SettingsAccountNav current="profile" />
    {/snippet}

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
            <Field.Input placeholder="Masukkan nama"
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
            <Field.Label for="profile-username">Username</Field.Label>
            <div class="input-group">
              <span class="input-group-text">@</span>
              <Field.Input
                placeholder="username"
                id="profile-username"
                name="username"
                autocomplete="username"
                autocapitalize="none"
                spellcheck="false"
                value={profile.username}
                invalid={!!errors.username}
              />
            </div>
            <Field.Feedback message={errors.username} />
            <p class="text-muted small mb-0">
              Tampil sebagai @username dan menjadi alamat profil penulis:
              /blog/@{profile.username ?? 'username'}. 3–30 karakter: huruf kecil,
              angka, titik, garis bawah, atau strip.
            </p>
          </Field.Group>

          <Field.Group>
            <Field.Label for="profile-email">Email</Field.Label>
            <Field.Input placeholder="nama@contoh.com"
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
                <Field.Input placeholder="08xxxxxxxxxx"
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

          <Field.Group>
            <Field.Label for="profile-bio">Deskripsi</Field.Label>
            <TranslationBar {translatable} picker />
            <TranslationHiddenInputs {translatable} fields={['bio']} />
            <RichTextEditor
              name={fieldName('bio', translatable.locale)}
              minimal
              value={translatable.values.bio[translatable.locale]}
              contentKey={translatable.contentKey}
              onchange={(html) => (translatable.values.bio[translatable.locale] = html)}
              placeholder="Ceritakan singkat tentang Anda..."
              invalid={!!errors[fieldName('bio', translatable.locale)]}
            />
            <Field.Feedback message={errors[fieldName('bio', translatable.locale)]} />
            <p class="text-muted small mb-0">
              Tampil di halaman profil penulis publik.
            </p>
          </Field.Group>

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
  </SettingsLayout>
</div>
