<script lang="ts">
  import { t } from '@/lib/i18n';
  import { usePasskeyRegister } from '@laravel/passkeys/svelte';
  import { router } from '@inertiajs/svelte';
  import Check from '@lucide/svelte/icons/check';
  import X from '@lucide/svelte/icons/x';
  import KeyRound from '@lucide/svelte/icons/key-round';
  import Pencil from '@lucide/svelte/icons/pencil';
  import Trash2 from '@lucide/svelte/icons/trash-2';
  import ModalConfirmation from '@/components/modal-confirmation.svelte';
  import { Field } from '@/components/ui/field';
  import { destroy } from '@/wayfinder/routes/passkey';
  import { update } from '@/wayfinder/routes/settings/passkeys';
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
  let renamingId = $state<number | string | null>(null);
  let renameValue = $state('');
  let renameError = $state<string | undefined>(undefined);
  let isRenaming = $state(false);

  function startRename(passkey: Passkey) {
    renamingId = passkey.id;
    renameValue = passkey.name;
    renameError = undefined;
  }

  function cancelRename() {
    renamingId = null;
    renameError = undefined;
  }

  function submitRename(event: SubmitEvent) {
    event.preventDefault();

    if (renamingId === null || !renameValue.trim()) {
      return;
    }

    isRenaming = true;

    router.patch(
      update.url(Number(renamingId)),
      { name: renameValue.trim() },
      {
        preserveScroll: true,
        onSuccess: () => cancelRename(),
        onError: (errors) => (renameError = errors.name),
        onFinish: () => (isRenaming = false),
      },
    );
  }

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
      <h2 class="h5 mb-1">{t('security.passkey')}</h2>
      <p class="text-muted small mb-3">
        {t('security.kelola_passkey_anda_untuk_masuk_tanpa')}
      </p>

      <div class="border rounded mb-3">
        {#if passkeys.length > 0}
          {#each passkeys as passkey (passkey.id)}
            <div
              class="d-flex align-items-center justify-content-between p-3 border-bottom"
            >
              <div class="d-flex align-items-center gap-3 flex-grow-1">
                <div
                  class="d-flex align-items-center justify-content-center bg-body-secondary rounded-3 flex-shrink-0"
                  style="width: 2.5rem; height: 2.5rem;"
                >
                  <KeyRound size={20} />
                </div>
                {#if renamingId === passkey.id}
                  <form
                    id={`passkey-rename-form-${passkey.id}`}
                    onsubmit={submitRename}
                    class="flex-grow-1"
                  >
                    <Field.Input
                      id={`passkey-rename-${passkey.id}`}
                      bind:value={renameValue}
                      invalid={!!renameError}
                      autofocus
                    />
                    <Field.Feedback message={renameError} />
                  </form>
                {:else}
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
                {/if}
              </div>

              <div class="btn-group ms-3 flex-shrink-0" role="group">
                {#if renamingId === passkey.id}
                  <button
                    type="submit"
                    form={`passkey-rename-form-${passkey.id}`}
                    class="btn btn-square btn-outline-primary btn-sm"
                    disabled={isRenaming || !renameValue.trim()}
                    aria-label={t('security.simpan_nama_passkey')}
                  >
                    <Check size={16} />
                  </button>
                  <button
                    type="button"
                    class="btn btn-square btn-outline-secondary btn-sm"
                    onclick={cancelRename}
                    aria-label={t('security.batal_ubah_nama')}
                  >
                    <X size={16} />
                  </button>
                {:else}
                  <button
                    type="button"
                    class="btn btn-square btn-outline-secondary btn-sm"
                    onclick={() => startRename(passkey)}
                    aria-label={t('security.ubah_nama_passkey')}
                  >
                    <Pencil size={16} />
                  </button>
                {/if}
                <button
                  type="button"
                  class="btn btn-square btn-outline-danger btn-sm"
                  onclick={() => openDeleteModal(passkey)}
                  aria-label={t('security.hapus_passkey')}
                >
                  <Trash2 size={16} />
                </button>
              </div>
            </div>
          {/each}
        {:else}
          <div class="text-center p-4">
            <KeyRound size={28} class="text-muted mb-2" />
            <p class="fw-medium mb-1">{t('security.belum_ada_passkey')}</p>
            <p class="text-muted small mb-0">
              {t('security.tambahkan_passkey_untuk_masuk_tanpa_kata')}
            </p>
          </div>
        {/if}
      </div>

      {#if !passkeyRegister.isSupported}
        <p class="text-muted small mb-0">
          {t('security.passkey_tidak_didukung_oleh_browser_ini')}
        </p>
      {:else if !showRegisterForm}
        <button
          type="button"
          class="btn btn-outline-primary"
          onclick={() => (showRegisterForm = true)}
        >
          {t('security.tambah_passkey')}
        </button>
      {:else}
        <form
          onsubmit={handleRegisterSubmit}
          class="border rounded p-3 bg-body-secondary"
        >
          <Field.Group>
            <Field.Label for="passkey-name">{t('security.nama_passkey')}</Field.Label>
            <Field.Input
              id="passkey-name"
              bind:value={name}
              placeholder={t('security.mis_macbook_pro_iphone')}
              autofocus
            />
            <Field.Feedback message={passkeyRegister.error ?? undefined} />
          </Field.Group>

          <div class="d-flex gap-2 mt-3">
            <button
              type="submit"
              class="btn btn-primary"
              disabled={passkeyRegister.isLoading || !name.trim()}
            >
              {passkeyRegister.isLoading ? t('security.mendaftarkan') : t('security.daftarkan_passkey')}
            </button>
            <button
              type="button"
              class="btn btn-link"
              onclick={() => {
                showRegisterForm = false;
                name = '';
              }}
            >
              {t('security.batal')}
            </button>
          </div>
        </form>
      {/if}
    </div>
  </div>
{/if}

<ModalConfirmation
  bind:open={showDeleteModal}
  title={t('security.hapus_passkey')}
  description={deleting
    ? `Apakah Anda yakin ingin menghapus passkey "${deleting.name}"? Anda tidak akan bisa lagi menggunakannya untuk masuk.`
    : ''}
  confirmLabel={t('security.hapus')}
  processing={isDeleting}
  actions={{
    confirm: confirmDelete,
    deny: () => (deleting = null),
  }}
/>
