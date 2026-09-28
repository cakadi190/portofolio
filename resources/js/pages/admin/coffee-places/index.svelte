<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import { Link } from '@inertiajs/svelte';
  import { create, destroy, edit } from '@/wayfinder/routes/admin/coffee-places';
  import type { Paginated } from '@/types/pagination';

  type CoffeePlaceRow = {
    id: number;
    name: string;
    address: string;
    price_tier: string;
    is_recommended: boolean;
  };

  let { coffeePlaces }: { coffeePlaces: Paginated<CoffeePlaceRow> } = $props();
</script>

<AppHead title="Kedai Kopi" />

<AdminPageHeader
  title="Kedai Kopi"
  subtitle="Kelola daftar tempat ngopi rekomendasi."
  createHref={create().url}
/>

{#if coffeePlaces.data.length === 0}
  <EmptyState title="Belum ada kedai kopi" text="Tambahkan kedai kopi pertama Anda." />
{:else}
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Nama</th>
          <th>Alamat</th>
          <th>Tingkat Harga</th>
          <th>Rekomendasi</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        {#each coffeePlaces.data as place (place.id)}
          <tr>
            <td>{place.name}</td>
            <td>{place.address}</td>
            <td>{place.price_tier}</td>
            <td>{place.is_recommended ? 'Ya' : 'Tidak'}</td>
            <td class="text-end">
              <div class="d-inline-flex gap-2">
                <Link href={edit(place.id).url} class="btn btn-sm btn-outline-secondary">
                  Ubah
                </Link>
                <AdminDeleteButton
                  href={destroy(place.id).url}
                  label={`Hapus kedai kopi "${place.name}"?`}
                />
              </div>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>

  <SimplePaginator
    currentPage={coffeePlaces.current_page}
    lastPage={coffeePlaces.last_page}
    prevPageUrl={coffeePlaces.prev_page_url}
    nextPageUrl={coffeePlaces.next_page_url}
  />
{/if}
