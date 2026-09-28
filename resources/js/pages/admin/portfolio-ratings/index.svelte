<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import { Link } from '@inertiajs/svelte';
  import { create, destroy, edit } from '@/wayfinder/routes/admin/portfolio-ratings';
  import type { Paginated } from '@/types/pagination';

  type RatingRow = {
    id: number;
    rating: number;
    comment: string | null;
    portfolio: { id: number; name: string };
  };

  let { portfolioRatings }: { portfolioRatings: Paginated<RatingRow> } = $props();
</script>

<AppHead title="Ulasan Portofolio" />

<AdminPageHeader
  title="Ulasan Portofolio"
  subtitle="Kelola ulasan yang masuk untuk portofolio Anda."
  createHref={create().url}
/>

{#if portfolioRatings.data.length === 0}
  <EmptyState title="Belum ada ulasan" text="Ulasan portofolio akan muncul di sini." />
{:else}
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Portofolio</th>
          <th>Rating</th>
          <th>Komentar</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        {#each portfolioRatings.data as rating (rating.id)}
          <tr>
            <td>{rating.portfolio.name}</td>
            <td>{rating.rating} / 5</td>
            <td>{rating.comment ?? '—'}</td>
            <td class="text-end">
              <div class="d-inline-flex gap-2">
                <Link href={edit(rating.id).url} class="btn btn-sm btn-outline-secondary">
                  Ubah
                </Link>
                <AdminDeleteButton
                  href={destroy(rating.id).url}
                  label={`Hapus ulasan untuk "${rating.portfolio.name}"?`}
                />
              </div>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>

  <SimplePaginator
    currentPage={portfolioRatings.current_page}
    lastPage={portfolioRatings.last_page}
    prevPageUrl={portfolioRatings.prev_page_url}
    nextPageUrl={portfolioRatings.next_page_url}
  />
{/if}
