<script lang="ts">
  import { t } from '@/lib/i18n';
  import { Link, router } from '@inertiajs/svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import AppHead from '@/components/app-head.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import {
    create,
    destroy,
    edit,
    index,
    restore,
  } from '@/wayfinder/routes/admin/posts';
  import type { InfiniteList, TableFilters } from '@/types/pagination';

  type Post = {
    id: number;
    title: string;
    slug: string;
    is_published: boolean;
    published_at: string | null;
    created_at: string;
  };

  let {
    posts,
    filters,
    trashed,
    trashedCount,
  }: {
    posts: InfiniteList<Post>;
    filters: TableFilters;
    trashed: boolean;
    trashedCount: number;
  } = $props();
</script>

<AppHead title={t('admin-posts.artikel_blog')} />

<AdminPageHeader
  title={t('admin-posts.artikel_blog')}
  subtitle={t('admin-posts.kelola_artikel_blog_anda')}
  createHref={create().url}
  createLabel={t('admin-posts.tulis_artikel')}
/>

<ul class="nav nav-pills mb-3 gap-2">
  <li class="nav-item">
    <Link href={index().url} class={`nav-link ${trashed ? '' : 'active'}`}
      >{t('admin-posts.semua')}</Link
    >
  </li>
  <li class="nav-item">
    <Link
      href={index({ query: { trashed: 1 } }).url}
      class={`nav-link ${trashed ? 'active' : ''}`}
      >Sampah{trashedCount > 0 ? ` (${trashedCount})` : ''}</Link
    >
  </li>
</ul>

<DataTable
  data={posts}
  scrollKey="posts"
  {filters}
  url={trashed ? index({ query: { trashed: 1 } }).url : index().url}
  columns={[
    { label: t('ui.judul'), key: 'title', sortable: true },
    { label: t('admin.status'), key: 'is_published', sortable: true },
    { label: t('admin-posts.tanggal'), key: 'published_at', sortable: true },
    { label: t('admin-media.aksi'), align: 'end' },
  ]}
  emptyTitle={trashed ? t('admin-posts.sampah_kosong') : t('admin-posts.belum_ada_artikel')}
  emptyText={trashed
    ? t('admin-posts.artikel_yang_dipindahkan_ke_sampah_muncul')
    : t('admin-posts.tulis_artikel_pertama_anda')}
>
  {#snippet row(post)}
    {@const scheduled =
      post.is_published &&
      !!post.published_at &&
      new Date(post.published_at).getTime() > Date.now()}
    <tr>
      <td>
        <Link href={edit(post.id).url} class="fw-semibold text-decoration-none">
          {post.title}
        </Link>
      </td>
      <td>
        <span
          class={`badge ${scheduled ? 'text-bg-info' : post.is_published ? 'text-bg-success' : 'text-bg-secondary'}`}
        >
          {scheduled ? t('admin.terjadwal') : post.is_published ? t('admin-certifications.terbit') : t('admin.draf')}
        </span>
      </td>
      <td class="text-secondary">
        {new Date(post.published_at ?? post.created_at).toLocaleDateString(
          'id-ID',
          { day: 'numeric', month: 'short', year: 'numeric' },
        )}
      </td>
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          {#if trashed}
            <button
              type="button"
              class="btn btn-sm btn-outline-secondary"
              onclick={() =>
                router.patch(
                  restore(post.id).url,
                  {},
                  { preserveScroll: true },
                )}>{t('admin-posts.pulihkan')}</button
            >
            <AdminDeleteButton
              href={destroy(post.id).url}
              label={`Hapus permanen artikel "${post.title}"?`}
              buttonLabel={t('admin-posts.hapus_permanen')}
              confirmLabel={t('admin-posts.hapus_permanen')}
            />
          {:else}
            <Link
              href={edit(post.id).url}
              class="btn btn-sm btn-outline-secondary">{t('admin.ubah')}</Link
            >
            <AdminDeleteButton
              href={destroy(post.id).url}
              label={`Pindahkan artikel "${post.title}" ke sampah?`}
              description={t('admin-posts.artikel_tidak_lagi_tampil_di_situs')}
              buttonLabel={t('admin-posts.sampah')}
              confirmLabel={t('admin-posts.pindahkan')}
            />
          {/if}
        </div>
      </td>
    </tr>
  {/snippet}
</DataTable>
