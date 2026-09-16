<script module lang="ts">
  export const layout = {
    title: 'Atur ulang kata sandi',
    description: 'Silakan masukkan kata sandi baru Anda di bawah ini',
  };
</script>

<script lang="ts">
  import { Form } from '@inertiajs/svelte';
  import AppHead from '@/components/app-head.svelte';
  import InputError from '@/components/input-error.svelte';
  import { Input } from '@/components/ui/input';
  import { update } from '@/routes/password';

  let {
    token,
    email,
    passwordRules,
  }: {
    token: string;
    email: string;
    passwordRules: string;
  } = $props();
</script>

<AppHead title="Atur ulang kata sandi" />

<Form
  {...update.form()}
  transform={(data) => ({ ...data, token, email })}
  resetOnSuccess={['password', 'password_confirmation']}
>
  {#snippet children({ errors, processing })}
    <div class="d-flex flex-column gap-4">
      <div class="d-flex flex-column gap-2">
        <label class="form-label" for="email">Email</label>
        <Input
          id="email"
          type="email"
          name="email"
          autocomplete="email"
          value={email}
          class="mt-1 d-block w-100"
          readonly
        />
        <InputError message={errors.email} class="mt-2" />
      </div>

      <div class="d-flex flex-column gap-2">
        <label class="form-label" for="password">Kata sandi</label>
        <Input.Password
          id="password"
          name="password"
          autocomplete="new-password"
          class="mt-1 d-block w-100"
          placeholder="Kata sandi"
          passwordrules={passwordRules}
        />
        <InputError message={errors.password} />
      </div>

      <div class="d-flex flex-column gap-2">
        <label class="form-label" for="password_confirmation">Konfirmasi kata sandi</label>
        <Input.Password
          id="password_confirmation"
          name="password_confirmation"
          autocomplete="new-password"
          class="mt-1 d-block w-100"
          placeholder="Konfirmasi kata sandi"
          passwordrules={passwordRules}
        />
        <InputError message={errors.password_confirmation} />
      </div>

      <button
        type="submit"
        class="btn btn-primary mt-3 w-100"
        disabled={processing}
        data-test="reset-password-button"
      >
        Atur ulang kata sandi
      </button>
    </div>
  {/snippet}
</Form>
