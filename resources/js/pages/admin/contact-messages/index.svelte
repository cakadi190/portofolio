<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
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
    message: string;
    read_at: string | null;
    created_at: string;
  };

  let {
    messages,
    filters,
  }: { messages: Paginated<ContactMessage>; filters: TableFilters } = $props();

  let viewOpen = $state(false);
  let viewing = $state<ContactMessage | null>(null);

  function toggleRead(message: ContactMessage): void {
    router.put(update(message.id).url, {}, { preserveScroll: true });
  }

  function openMessage(message: ContactMessage): void {
    viewing = message;
    viewOpen = true;

    if (!message.read_at) {
      toggleRead(message);
    }
  }
</script>

<AppHead title="Pesan Masuk" />

<AdminPageHeader
  title="Pesan Masuk"
  subtitle="Pesan yang dikirim pengunjung lewat formulir di halaman Hubungi Saya."
/>

<DataTable
  data={messages}
  {filters}
  url={index().url}
  columns={[
    { label: 'Pengirim', key: 'name', sortable: true },
    { label: 'Keperluan' },
    { label: 'Dikirim', key: 'created_at', sortable: true },
    { label: 'Status' },
    { label: 'Aksi', align: 'end' },
  ]}
  emptyTitle="Belum ada pesan"
  emptyText="Pesan dari pengunjung akan muncul di sini."
>
  {#snippet row(message)}
    <tr class:fw-semibold={!message.read_at}>
      <td
        >{message.name}<br /><small class="opacity-75 fw-normal"
          >{message.email}</small
        ></td
      >
      <td>{message.reason_label}</td>
      <td>{formatDate(message.created_at)}</td>
      <td>
        <span
          class="badge"
          class:text-bg-primary={!message.read_at}
          class:text-bg-secondary={!!message.read_at}
        >
          {message.read_at ? 'Dibaca' : 'Baru'}
        </span>
      </td>
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            onclick={() => openMessage(message)}
          >
            Lihat
          </button>
          <AdminDeleteButton
            href={destroy(message.id).url}
            label={`Hapus pesan dari "${message.name}"?`}
          />
        </div>
      </td>
    </tr>
  {/snippet}
</DataTable>

<FormModal
  bind:open={viewOpen}
  title="Pesan dari Pengunjung"
  subtitle={viewing ? `${viewing.name} · ${viewing.email}` : ''}
  size="lg"
>
  {#snippet children()}
    {#if viewing}
      <p class="mb-2">
        <span class="badge text-bg-info">{viewing.reason_label}</span>
      </p>
      <div class="wysiwyg-content-wrapper border rounded-3 p-3 mb-3">
        <!-- eslint-disable-next-line svelte/no-at-html-tags -->
        {@html viewing.message}
      </div>
      <div class="d-flex justify-content-end gap-2">
        <a
          class="btn btn-outline-primary"
          href={`mailto:${viewing.email}`}>Balas via Email</a
        >
        <button
          type="button"
          class="btn btn-primary"
          onclick={() => (viewOpen = false)}>Tutup</button
        >
      </div>
    {/if}
  {/snippet}
</FormModal>
