<script lang="ts">
  import { t } from '@/lib/i18n';
  import '../../../../css/content.scss';
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import ContactReplyForm from '@/components/admin/contact-reply-form.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import { router } from '@inertiajs/svelte';
  import { formatDate } from '@/lib/utils';
  import {
    destroy,
    index,
    update,
  } from '@/wayfinder/routes/admin/contact-messages';
  import type { Paginated, TableFilters } from '@/types/pagination';

  type ContactMessage = {
    id: number;
    name: string;
    email: string;
    reason: string;
    reason_label: string;
    locale: string;
    locale_label: string;
    message: string;
    default_subject: string;
    replies: {
      id: number;
      subject: string;
      status: 'queued' | 'sent' | 'failed';
      status_label: string;
      error: string | null;
      author: string | null;
      attachments: string[];
      created_at: string;
    }[];
    read_at: string | null;
    created_at: string;
  };

  let {
    messages,
    filters,
  }: { messages: Paginated<ContactMessage>; filters: TableFilters } =
    $props();

  let viewOpen = $state(false);
  let viewing = $state<ContactMessage | null>(null);
  let replying = $state(false);
  const replyBadge = {
    queued: 'text-bg-secondary',
    sent: 'text-bg-success',
    failed: 'text-bg-danger',
  };

  function toggleRead(message: ContactMessage): void {
    router.put(update(message.id).url, {}, { preserveScroll: true });
  }

  function openMessage(message: ContactMessage): void {
    viewing = message;
    replying = false;
    viewOpen = true;

    if (!message.read_at) {
      toggleRead(message);
    }
  }
</script>

<AppHead title={t('admin-contact-messages.pesan_masuk')} />

<AdminPageHeader
  title={t('admin-contact-messages.pesan_masuk')}
  subtitle={t('admin-contact-messages.pesan_yang_dikirim_pengunjung_lewat_formulir')}
/>

<DataTable
  data={messages}
  {filters}
  url={index().url}
  columns={[
    { label: t('admin-contact-messages.pengirim'), key: 'name', sortable: true },
    { label: t('admin-contact-messages.keperluan') },
    { label: t('admin-contact-messages.bahasa_pengirim') },
    { label: t('admin-blog-comments.dikirim'), key: 'created_at', sortable: true },
    { label: t('admin.status') },
    { label: t('admin-media.aksi'), align: 'end' },
  ]}
  emptyTitle={t('admin.belum_ada_pesan')}
  emptyText={t('admin.pesan_dari_pengunjung_akan_muncul_di')}
>
  {#snippet row(message)}
    <tr class:fw-semibold={!message.read_at}>
      <td
        >{message.name}<br /><small class="opacity-75 fw-normal"
          >{message.email}</small
        ></td
      >
      <td>{message.reason_label}</td>
      <td><span class="badge text-bg-light border">{message.locale.toUpperCase()}</span></td>
      <td>{formatDate(message.created_at)}</td>
      <td>
        <span
          class="badge"
          class:text-bg-primary={!message.read_at}
          class:text-bg-secondary={!!message.read_at}
        >
          {message.read_at ? t('admin-contact-messages.dibaca') : t('dashboard.baru')}
        </span>
      </td>
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            onclick={() => openMessage(message)}
          >
            {t('admin.lihat')}
          </button>
          <AdminDeleteButton
            href={destroy(message.id).url}
            label={t('admin-contact-messages.delete_confirm', { name: message.name })}
          />
        </div>
      </td>
    </tr>
  {/snippet}
</DataTable>

<FormModal
  bind:open={viewOpen}
  title={t('admin-contact-messages.pesan_dari_pengunjung')}
  subtitle={viewing ? `${viewing.name} · ${viewing.email}` : ''}
  size="lg"
>
  {#snippet children()}
    {#if viewing}
      <p class="mb-2">
        <span class="badge text-bg-info">{viewing.reason_label}</span>
        <span class="badge text-bg-light border"
          >{t('admin-contact-messages.bahasa_pengirim')}: {viewing.locale_label}</span
        >
      </p>
      <div class="wysiwyg-content-wrapper border rounded-3 p-3 mb-3">
        <!-- eslint-disable-next-line svelte/no-at-html-tags -->
        {@html viewing.message}
      </div>
      {#if viewing.replies.length > 0}
        <h6 class="mb-2">{t('admin-contact-messages.riwayat_balasan')}</h6>
        <ul class="list-unstyled d-flex flex-column gap-2 mb-3">
          {#each viewing.replies as item (item.id)}
            <li class="border rounded-3 px-3 py-2">
              <div
                class="d-flex justify-content-between align-items-start gap-2"
              >
                <span class="fw-semibold">{item.subject}</span>
                <span class={`badge ${replyBadge[item.status]}`}
                  >{item.status_label}</span
                >
              </div>
              <small class="opacity-75">
                {formatDate(item.created_at)}{item.author
                  ? ` · ${item.author}`
                  : ''}
                {item.attachments.length > 0
                  ? ` · ${item.attachments.length} lampiran`
                  : ''}
              </small>
              {#if item.error}
                <small class="d-block text-danger">{item.error}</small>
              {/if}
            </li>
          {/each}
        </ul>
      {/if}

      {#if replying}
        <ContactReplyForm
          messageId={viewing.id}
          defaultSubject={viewing.default_subject}
          localeLabel={viewing.locale_label}
          onSent={() => (viewOpen = false)}
          onCancel={() => (replying = false)}
        />
      {:else}
        <div class="d-flex justify-content-end gap-2">
          <button
            type="button"
            class="btn btn-outline-primary"
            onclick={() => (replying = true)}>{t('admin-contact-messages.balas')}</button
          >
          <button
            type="button"
            class="btn btn-primary"
            onclick={() => (viewOpen = false)}>{t('admin.tutup')}</button
          >
        </div>
      {/if}
    {/if}
  {/snippet}
</FormModal>
