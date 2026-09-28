<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import { Link } from '@inertiajs/svelte';
  import { create, destroy, edit } from '@/wayfinder/routes/admin/users';
  import type { Paginated } from '@/types/pagination';

  type UserRow = {
    id: number;
    name: string;
    email: string;
    account_type: string;
    phone: string | null;
    avatar: string | null;
  };

  let { users }: { users: Paginated<UserRow> } = $props();
</script>

<AppHead title="Pengguna" />

<AdminPageHeader
  title="Pengguna"
  subtitle="Kelola akun pengguna dan admin."
  createHref={create().url}
/>

{#if users.data.length === 0}
  <EmptyState title="Belum ada pengguna" text="Tambahkan pengguna pertama Anda." />
{:else}
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Avatar</th>
          <th>Nama</th>
          <th>Email</th>
          <th>Peran</th>
          <th>Telepon</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        {#each users.data as user (user.id)}
          <tr>
            <td>
              {#if user.avatar}
                <img
                  src={`/storage/${user.avatar}`}
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
                <Link href={edit(user.id).url} class="btn btn-sm btn-outline-secondary">
                  Ubah
                </Link>
                <AdminDeleteButton
                  href={destroy(user.id).url}
                  label={`Hapus pengguna "${user.name}"?`}
                />
              </div>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>

  <SimplePaginator
    currentPage={users.current_page}
    lastPage={users.last_page}
    prevPageUrl={users.prev_page_url}
    nextPageUrl={users.next_page_url}
  />
{/if}
