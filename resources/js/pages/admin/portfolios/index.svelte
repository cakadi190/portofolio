<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import { Field } from '@/components/ui/field';
  import MediaField from '@/components/media/media-field.svelte';
  import MultiCheck from '@/components/ui/multi-check.svelte';
  import { Form } from '@inertiajs/svelte';
  import { storageUrl } from '@/lib/utils';
  import {
    destroy,
    store,
    update,
    index,
  } from '@/wayfinder/routes/admin/portfolios';
  import type { Paginated, TableFilters } from '@/types/pagination';

  type Portfolio = {
    id: number;
    name: string;
    slug: string;
    image: string;
    short_desc: string | null;
    description: string | null;
    demo_link: string | null;
    source_code: string | null;
    is_private: boolean;
    technologies_count: number;
    categories_count: number;
    technologies: { id: number; name: string }[];
    categories: { id: number; name: string }[];
    careers: { id: number }[];
  };

  let {
    portfolios,
    technologies,
    categories,
    careers,
    filters,
  }: {
    filters: TableFilters;
    portfolios: Paginated<Portfolio>;
    technologies: { id: number; name: string }[];
    categories: { id: number; name: string }[];
    careers: { id: number; position: string; company: string }[];
  } = $props();

  let createOpen = $state(false);
  let editOpen = $state(false);
  let editingPortfolio = $state<Portfolio | null>(null);
  const editingSelectedTechnologies = $derived(
    editingPortfolio?.technologies.map((t) => t.id) ?? [],
  );
  const editingSelectedCategories = $derived(
    editingPortfolio?.categories.map((c) => c.id) ?? [],
  );
  const editingSelectedCareers = $derived(
    editingPortfolio?.careers.map((c) => c.id) ?? [],
  );

  function openEdit(portfolio: Portfolio): void {
    editingPortfolio = portfolio;
    editOpen = true;
  }
</script>

<AppHead title="Portofolio" />

<AdminPageHeader
  title="Portofolio"
  subtitle="Kelola daftar portofolio Anda."
  onCreate={() => (createOpen = true)}
/>

<DataTable
  data={portfolios}
  {filters}
  url={index().url}
  columns={[
    { label: 'Sampul' },
    { label: 'Nama', key: 'name', sortable: true },
    { label: 'Teknologi' },
    { label: 'Kategori' },
    { label: 'Privat', key: 'is_private', sortable: true },
    { label: 'Aksi', align: 'end' },
  ]}
  emptyTitle="Belum ada portofolio"
  emptyText="Tambahkan portofolio pertama Anda."
>
  {#snippet row(portfolio)}
    <tr>
      <td>
        <img
          src={storageUrl(portfolio.image) ?? ''}
          alt={portfolio.name}
          loading="lazy"
          style="width:3.5rem;height:2.5rem;object-fit:cover;border-radius:0.5rem;"
        />
      </td>
      <td>{portfolio.name}</td>
      <td
        ><div class="d-flex flex-wrap gap-1">
          {#each portfolio.technologies as item (item.id)}
            <span class="badge text-bg-secondary fw-normal">{item.name}</span>
          {:else}
            <span class="text-muted">&mdash;</span>
          {/each}
        </div></td
      >
      <td
        ><div class="d-flex flex-wrap gap-1">
          {#each portfolio.categories as item (item.id)}
            <span class="badge text-bg-secondary fw-normal">{item.name}</span>
          {:else}
            <span class="text-muted">&mdash;</span>
          {/each}
        </div></td
      >
      <td>{portfolio.is_private ? 'Ya' : 'Tidak'}</td>
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            onclick={() => openEdit(portfolio)}
          >
            Ubah
          </button>
          <AdminDeleteButton
            href={destroy(portfolio.id).url}
            label={`Hapus portofolio "${portfolio.name}"?`}
          />
        </div>
      </td>
    </tr>
  {/snippet}
</DataTable>

<FormModal
  bind:open={createOpen}
  title="Tambah Portofolio"
  subtitle="Buat entri portofolio baru."
  size="lg"
>
  {#snippet children()}
    <Form
      {...store.form()}
      class="d-flex flex-column gap-3"
      novalidate
      onSuccess={() => (createOpen = false)}
    >
      {#snippet children({ errors, processing })}
        <Field.Group>
          <Field.Label for="create-name">Nama</Field.Label>
          <Field.Input
            id="create-name"
            name="name"
            required
            invalid={!!errors.name}
          />
          <Field.Feedback message={errors.name} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-slug">Slug</Field.Label>
          <Field.Input
            id="create-slug"
            name="slug"
            placeholder="Otomatis dari nama"
            invalid={!!errors.slug}
          />
          <Field.Feedback message={errors.slug} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-image">Gambar Sampul</Field.Label>
          <MediaField name="image" required invalid={!!errors.image} />
          <Field.Feedback message={errors.image} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-short_desc">Deskripsi Singkat</Field.Label>
          <Field.Input
            id="create-short_desc"
            name="short_desc"
            invalid={!!errors.short_desc}
          />
          <Field.Feedback message={errors.short_desc} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-description">Deskripsi</Field.Label>
          <textarea
            id="create-description"
            name="description"
            class="form-control"
            class:is-invalid={!!errors.description}
            rows="5"></textarea>
          <Field.Feedback message={errors.description} />
        </Field.Group>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-demo_link">Tautan Demo</Field.Label>
              <Field.Input
                id="create-demo_link"
                name="demo_link"
                type="url"
                invalid={!!errors.demo_link}
              />
              <Field.Feedback message={errors.demo_link} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-source_code">Kode Sumber</Field.Label>
              <Field.Input
                id="create-source_code"
                name="source_code"
                type="url"
                invalid={!!errors.source_code}
              />
              <Field.Feedback message={errors.source_code} />
            </Field.Group>
          </div>
        </div>

        <input type="hidden" name="is_private" value="0" />
        <Field.Input.Check id="create-is_private" name="is_private" value="1">
          Portofolio privat
        </Field.Input.Check>

        <Field.Group>
          <Field.Label for="create-technologies">Teknologi</Field.Label>
          <MultiCheck
            name="technologies"
            options={technologies.map((t) => ({ value: t.id, label: t.name }))}
          />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-categories">Kategori</Field.Label>
          <MultiCheck
            name="categories"
            options={categories.map((c) => ({ value: c.id, label: c.name }))}
          />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-careers">Karier terkait</Field.Label>
          <MultiCheck
            name="careers"
            options={careers.map((c) => ({
              value: c.id,
              label: `${c.position} — ${c.company}`,
            }))}
          />
        </Field.Group>

        <div class="d-flex justify-content-end gap-2">
          <button
            type="button"
            class="btn btn-outline-secondary"
            onclick={() => (createOpen = false)}
          >
            Batal
          </button>
          <button type="submit" class="btn btn-primary" disabled={processing}
            >Simpan</button
          >
        </div>
      {/snippet}
    </Form>
  {/snippet}
</FormModal>

<FormModal
  bind:open={editOpen}
  title="Ubah Portofolio"
  subtitle={editingPortfolio?.name ?? ''}
  size="lg"
>
  {#snippet children()}
    {#if editingPortfolio}
      <Form
        {...update.form(editingPortfolio.id)}
        class="d-flex flex-column gap-3"
        novalidate
        onSuccess={() => (editOpen = false)}
      >
        {#snippet children({ errors, processing })}
          <Field.Group>
            <Field.Label for="edit-name">Nama</Field.Label>
            <Field.Input
              id="edit-name"
              name="name"
              required
              value={editingPortfolio.name}
              invalid={!!errors.name}
            />
            <Field.Feedback message={errors.name} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-slug">Slug</Field.Label>
            <Field.Input
              id="edit-slug"
              name="slug"
              value={editingPortfolio.slug}
              invalid={!!errors.slug}
            />
            <Field.Feedback message={errors.slug} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-image">Gambar Sampul</Field.Label>
            <MediaField
              name="image"
              required
              value={editingPortfolio.image}
              invalid={!!errors.image}
            />
            <Field.Feedback message={errors.image} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-short_desc">Deskripsi Singkat</Field.Label>
            <Field.Input
              id="edit-short_desc"
              name="short_desc"
              value={editingPortfolio.short_desc}
              invalid={!!errors.short_desc}
            />
            <Field.Feedback message={errors.short_desc} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-description">Deskripsi</Field.Label>
            <textarea
              id="edit-description"
              name="description"
              class="form-control"
              class:is-invalid={!!errors.description}
              rows="5">{editingPortfolio.description ?? ''}</textarea
            >
            <Field.Feedback message={errors.description} />
          </Field.Group>

          <div class="row g-3">
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-demo_link">Tautan Demo</Field.Label>
                <Field.Input
                  id="edit-demo_link"
                  name="demo_link"
                  type="url"
                  value={editingPortfolio.demo_link}
                  invalid={!!errors.demo_link}
                />
                <Field.Feedback message={errors.demo_link} />
              </Field.Group>
            </div>
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-source_code">Kode Sumber</Field.Label>
                <Field.Input
                  id="edit-source_code"
                  name="source_code"
                  type="url"
                  value={editingPortfolio.source_code}
                  invalid={!!errors.source_code}
                />
                <Field.Feedback message={errors.source_code} />
              </Field.Group>
            </div>
          </div>

          <input type="hidden" name="is_private" value="0" />
          <Field.Input.Check
            id="edit-is_private"
            name="is_private"
            value="1"
            checked={editingPortfolio.is_private}
          >
            Portofolio privat
          </Field.Input.Check>

          <Field.Group>
            <Field.Label for="edit-technologies">Teknologi</Field.Label>
            <MultiCheck
              name="technologies"
              options={technologies.map((t) => ({
                value: t.id,
                label: t.name,
              }))}
              selected={editingSelectedTechnologies}
            />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-categories">Kategori</Field.Label>
            <MultiCheck
              name="categories"
              options={categories.map((c) => ({ value: c.id, label: c.name }))}
              selected={editingSelectedCategories}
            />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-careers">Karier terkait</Field.Label>
            <MultiCheck
              name="careers"
              options={careers.map((c) => ({
                value: c.id,
                label: `${c.position} — ${c.company}`,
              }))}
              selected={editingSelectedCareers}
            />
          </Field.Group>

          <div class="d-flex justify-content-end gap-2">
            <button
              type="button"
              class="btn btn-outline-secondary"
              onclick={() => (editOpen = false)}
            >
              Batal
            </button>
            <button type="submit" class="btn btn-primary" disabled={processing}
              >Simpan</button
            >
          </div>
        {/snippet}
      </Form>
    {/if}
  {/snippet}
</FormModal>
