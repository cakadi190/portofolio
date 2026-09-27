<script lang="ts">
  import { usePasskeyRegister } from '@laravel/passkeys/svelte';
  import { router } from '@inertiajs/svelte';
  import KeyRound from '@lucide/svelte/icons/key-round';
  import Trash2 from '@lucide/svelte/icons/trash-2';
  import ModalConfirmation from '@/components/modal-confirmation.svelte';
  import { Field } from '@/components/ui/field';
  import { destroy } from '@/wayfinder/routes/passkey';
  import type { Passkey } from '@/types/auth';

  let {
    canManagePasskeys = false,
    passkeys = [],
  }: {
    canManagePasskeys?: boolean;
    passkeys?: Passkey[];
  } = $props();

  let showRegisterForm = $state(false);
  let name = $state('');
  let deleting = $state<Passkey | null>(null);
  let showDeleteModal = $state(false);
  let isDeleting = $state(false);

  const passkeyRegister = usePasskeyRegister({
    onSuccess: () => {
      name = '';
      showRegisterForm = false;
      router.reload({ only: ['passkeys'] });
    },
  });

  async function handleRegisterSubmit(event: SubmitEvent) {
    event.preventDefault();

    if (!name.trim()) {
      return;
    }

    await passkeyRegister.register(name.trim());
  }

  function confirmDelete() {
    if (!deleting) {
      return;
    }

    isDeleting = true;

    router.delete(destroy.url(deleting.id), {
      preserveScroll: true,
      onFinish: () => {
        isDeleting = false;
        deleting = null;
      },
    });
  }

  function openDeleteModal(passkey: Passkey) {
    deleting = passkey;
    showDeleteModal = true;
  }
</script>

{#if canManagePasskeys}
  <div class="card mb-4">
    <div class="card-body">
      <h2 class="h5 mb-1">Passkey</h2>
      <p class="text-muted small mb-3">
        Kelola passkey Anda untuk masuk tanpa kata sandi.
      </p>

      <div class="border rounded mb-3">
        {#if passkeys.length > 0}
          {#each passkeys as passkey (passkey.id)}
            <div
              class="d-flex align-items-center justify-content-between p-3 border-bottom"
            >
              <div class="d-flex align-items-center gap-3">
                <div
                  class="d-flex align-items-center justify-content-center bg-body-secondary rounded-3"
                  style="width: 2.5rem; height: 2.5rem;"
                >
                  <KeyRound size={20} />
                </div>
                <div>
                  <div class="d-flex align-items-center gap-2">
                    <span class="fw-medium">{passkey.name}</span>
                    {#if passkey.authenticator}
                      <span class="badge text-bg-secondary text-uppercase">
                        {passkey.authenticator}
                      </span>
                    {/if}
                  </div>
                  <p class="text-muted small mb-0">
                    Ditambahkan {passkey.created_at_diff}
                    {#if passkey.last_used_at_diff}
                      &middot; Terakhir digunakan {passkey.last_used_at_diff}
                    {/if}
                  </p>
                </div>
              </div>

              <button
                type="button"
                class="btn btn-link text-danger"
                onclick={() => openDeleteModal(passkey)}
                aria-label="Hapus passkey"
              >
                <Trash2 size={16} />
              </button>
            </div>
          {/each}
        {:else}
          <div class="text-center p-4">
            <KeyRound size={28} class="text-muted mb-2" />
            <p class="fw-medium mb-1">Belum ada passkey</p>
            <p class="text-muted small mb-0">
              Tambahkan passkey untuk masuk tanpa kata sandi.
            </p>
          </div>
        {/if}
      </div>

      {#if !passkeyRegister.isSupported}
        <p class="text-muted small mb-0">
          Passkey tidak didukung oleh browser ini.
        </p>
      {:else if !showRegisterForm}
        <button
          type="button"
          class="btn btn-outline-primary"
          onclick={() => (showRegisterForm = true)}
        >
          Tambah passkey
        </button>
      {:else}
        <form
          onsubmit={handleRegisterSubmit}
          class="border rounded p-3 bg-body-secondary"
        >
          <Field.Group>
            <Field.Label for="passkey-name">Nama passkey</Field.Label>
            <Field.Input
              id="passkey-name"
              bind:value={name}
              placeholder="mis. MacBook Pro, iPhone"
              autofocus
            />
            <Field.Feedback message={passkeyRegister.error} />
          </Field.Group>

          <div class="d-flex gap-2 mt-3">
            <button
              type="submit"
              class="btn btn-primary"
              disabled={passkeyRegister.isLoading || !name.trim()}
            >
              {passkeyRegister.isLoading ? 'Mendaftarkan...' : 'Daftarkan passkey'}
            </button>
            <button
              type="button"
              class="btn btn-link"
              onclick={() => {
                showRegisterForm = false;
                name = '';
              }}
            >
              Batal
            </button>
          </div>
        </form>
      {/if}
    </div>
  </div>
{/if}

<ModalConfirmation
  bind:open={showDeleteModal}
  title="Hapus passkey"
  description={deleting
    ? `Apakah Anda yakin ingin menghapus passkey "${deleting.name}"? Anda tidak akan bisa lagi menggunakannya untuk masuk.`
    : ''}
  confirmLabel="Hapus"
  processing={isDeleting}
  actions={{
    confirm: confirmDelete,
    deny: () => (deleting = null),
  }}
/>
