<script lang="ts">
  import { Form } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import ManagePasskeys from '@/components/manage-passkeys.svelte';
  import ManageTwoFactor from '@/components/manage-two-factor.svelte';
  import { Field } from '@/components/ui/field';
  import { update } from '@/wayfinder/routes/user-password';
  import type { Passkey } from '@/types/auth';

  let {
    passwordRules,
    canManageTwoFactor = false,
    requiresConfirmation = false,
    twoFactorEnabled = false,
    canManagePasskeys = false,
    passkeys = [],
  }: {
    passwordRules: string;
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
    canManagePasskeys?: boolean;
    passkeys?: Passkey[];
  } = $props();
</script>

<AppHead title="Keamanan Akun" />

<div id="security-settings-page" style="max-width: 720px;">
  <AdminPageHeader
    title="Keamanan Akun"
    subtitle="Kelola kata sandi, autentikasi dua faktor, dan passkey Anda."
  />

      <div class="card mb-4">
        <div class="card-body">
          <h2 class="h5 mb-1">Perbarui kata sandi</h2>
          <p class="text-muted small mb-3">
            Pastikan akun Anda menggunakan kata sandi yang panjang dan acak
            agar tetap aman.
          </p>

          <Form
            {...update.form()}
            options={{ preserveScroll: true }}
            resetOnSuccess
            resetOnError={['password', 'password_confirmation', 'current_password']}
          >
            {#snippet children({ errors, processing })}
              <div class="d-flex flex-column gap-3">
                <Field.Group>
                  <Field.Label for="current_password">
                    Kata sandi saat ini
                  </Field.Label>
                  <Field.Input.Password
                    id="current_password"
                    name="current_password"
                    autocomplete="current-password"
                    invalid={!!errors.current_password}
                  />
                  <Field.Feedback message={errors.current_password} />
                </Field.Group>

                <Field.Group>
                  <Field.Label for="password">Kata sandi baru</Field.Label>
                  <Field.Input.Password
                    confirmed="password_confirmation"
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    passwordrules={passwordRules}
                    invalid={!!errors.password}
                  />
                  <Field.Feedback message={errors.password} />
                </Field.Group>

                <div>
                  <button
                    type="submit"
                    class="btn btn-primary"
                    disabled={processing}
                    data-test="update-password-button"
                  >
                    Simpan
                  </button>
                </div>
              </div>
            {/snippet}
          </Form>
        </div>
      </div>

      <ManageTwoFactor
        {canManageTwoFactor}
        {requiresConfirmation}
        {twoFactorEnabled}
      />

      <ManagePasskeys {canManagePasskeys} {passkeys} />
</div>
