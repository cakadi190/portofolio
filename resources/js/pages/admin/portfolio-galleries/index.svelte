<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import { Link } from '@inertiajs/svelte';
  import { create, destroy, edit } from '@/wayfinder/routes/admin/portfolio-galleries';
  import type { Paginated } from '@/types/pagination';

  type GalleryRow = {
    id: number;
    image_url: string;
    description: string | null;
    portfolio: { id: number; name: string };
  };

  let { portfolioGalleries }: { portfolioGalleries: Paginated<GalleryRow> } = $props();
</script>

<AppHead title="Galeri Portofolio" />

<AdminPageHeader
  title="Galeri Portofolio"
  subtitle="Kelola foto galeri untuk tiap portofolio."
  createHref={create().url}
/>

{#if portfolioGalleries.data.length === 0}
  <EmptyState title="Belum ada galeri" text="Tambahkan foto galeri pertama Anda." />
{:else}
  <div class="row g-3">
    {#each portfolioGalleries.data as gallery (gallery.id)}
      <div class="col-sm-6 col-lg-4">
        <div class="card h-100">
          <img
            src={`/storage/${gallery.image_url}`}
            alt={gallery.description ?? gallery.portfolio.name}
            class="card-img-top"
            style="height: 10rem; object-fit: cover;"
          />
          <div class="card-body">
            <p class="fw-semibold mb-1">{gallery.portfolio.name}</p>
            <p class="text-muted small mb-3">{gallery.description ?? '—'}</p>
            <div class="d-flex gap-2">
              <Link href={edit(gallery.id).url} class="btn btn-sm btn-outline-secondary">
                Ubah
              </Link>
              <AdminDeleteButton
                href={destroy(gallery.id).url}
                label={`Hapus foto galeri "${gallery.portfolio.name}"?`}
              />
            </div>
          </div>
        </div>
      </div>
    {/each}
  </div>

  <div class="mt-3">
    <SimplePaginator
      currentPage={portfolioGalleries.current_page}
      lastPage={portfolioGalleries.last_page}
      prevPageUrl={portfolioGalleries.prev_page_url}
      nextPageUrl={portfolioGalleries.next_page_url}
    />
  </div>
{/if}
