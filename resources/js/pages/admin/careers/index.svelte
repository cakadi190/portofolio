<script lang="ts">
  import DatePicker from '@/components/ui/date-picker.svelte';
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import { Field } from '@/components/ui/field';
  import MultiCheck from '@/components/ui/multi-check.svelte';
  import { Form } from '@inertiajs/svelte';
  import { formatDate } from '@/lib/utils';
  import { destroy, store, update } from '@/wayfinder/routes/admin/careers';
  import type { Paginated } from '@/types/pagination';

  type PortfolioOption = { id: number; name: string };

  type Career = {
    id: number;
    position: string;
    company: string;
    location: string;
    start_date: string;
    end_date: string | null;
    portfolios: { id: number }[];
  };

  let {
    careers,
    portfolios,
  }: { careers: Paginated<Career>; portfolios: PortfolioOption[] } = $props();

  let createOpen = $state(false);
  let editOpen = $state(false);
  let editingCareer = $state<Career | null>(null);
  const editingSelectedPortfolios = $derived(
    editingCareer?.portfolios.map((p) => p.id) ?? [],
  );

  function openEdit(career: Career): void {
    editingCareer = career;
    editOpen = true;
  }
</script>

<AppHead title="Riwayat Karier" />

<AdminPageHeader
  title="Riwayat Karier"
  subtitle="Kelola riwayat pekerjaan Anda."
  onCreate={() => (createOpen = true)}
/>

{#if careers.data.length === 0}
  <EmptyState
    title="Belum ada karier"
    text="Tambahkan riwayat karier pertama Anda."
  />
{:else}
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Posisi</th>
          <th>Perusahaan</th>
          <th>Lokasi</th>
          <th>Periode</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        {#each careers.data as career (career.id)}
          <tr>
            <td>{career.position}</td>
            <td>{career.company}</td>
            <td>{career.location}</td>
            <td
              >{formatDate(career.start_date)} &ndash; {career.end_date
                ? formatDate(career.end_date)
                : 'Sekarang'}</td
            >
            <td class="text-end">
              <div class="d-inline-flex gap-2">
                <button
                  type="button"
                  class="btn btn-sm btn-outline-secondary"
                  onclick={() => openEdit(career)}
                >
                  Ubah
                </button>
                <AdminDeleteButton
                  href={destroy(career.id).url}
                  label={`Hapus karier "${career.position}"?`}
                />
              </div>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>

  <SimplePaginator
    currentPage={careers.current_page}
    lastPage={careers.last_page}
    prevPageUrl={careers.prev_page_url}
    nextPageUrl={careers.next_page_url}
  />
{/if}

<FormModal
  bind:open={createOpen}
  title="Tambah Karier"
  subtitle="Catat riwayat pekerjaan baru."
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
          <Field.Label for="create-position">Posisi</Field.Label>
          <Field.Input
            id="create-position"
            name="position"
            required
            invalid={!!errors.position}
          />
          <Field.Feedback message={errors.position} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-company">Perusahaan</Field.Label>
          <Field.Input
            id="create-company"
            name="company"
            required
            invalid={!!errors.company}
          />
          <Field.Feedback message={errors.company} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-location">Lokasi</Field.Label>
          <Field.Input
            id="create-location"
            name="location"
            required
            invalid={!!errors.location}
          />
          <Field.Feedback message={errors.location} />
        </Field.Group>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-start_date">Mulai</Field.Label>
              <DatePicker
                id="create-start_date"
                name="start_date"
                invalid={!!errors.start_date}
              />
              <Field.Feedback message={errors.start_date} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-end_date">Selesai</Field.Label>
              <DatePicker
                id="create-end_date"
                name="end_date"
                invalid={!!errors.end_date}
              />
              <Field.Feedback message={errors.end_date} />
            </Field.Group>
          </div>
        </div>

        <Field.Group>
          <Field.Label for="create-portfolios">Portofolio terkait</Field.Label>
          <MultiCheck
            name="portfolios"
            options={portfolios.map((p) => ({ value: p.id, label: p.name }))}
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
  title="Ubah Karier"
  subtitle={editingCareer?.position ?? ''}
  size="lg"
>
  {#snippet children()}
    {#if editingCareer}
      <Form
        {...update.form(editingCareer.id)}
        class="d-flex flex-column gap-3"
        novalidate
        onSuccess={() => (editOpen = false)}
      >
        {#snippet children({ errors, processing })}
          <Field.Group>
            <Field.Label for="edit-position">Posisi</Field.Label>
            <Field.Input
              id="edit-position"
              name="position"
              required
              value={editingCareer.position}
              invalid={!!errors.position}
            />
            <Field.Feedback message={errors.position} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-company">Perusahaan</Field.Label>
            <Field.Input
              id="edit-company"
              name="company"
              required
              value={editingCareer.company}
              invalid={!!errors.company}
            />
            <Field.Feedback message={errors.company} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-location">Lokasi</Field.Label>
            <Field.Input
              id="edit-location"
              name="location"
              required
              value={editingCareer.location}
              invalid={!!errors.location}
            />
            <Field.Feedback message={errors.location} />
          </Field.Group>

          <div class="row g-3">
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-start_date">Mulai</Field.Label>
                <DatePicker
                  id="edit-start_date"
                  name="start_date"
                  value={editingCareer.start_date}
                  invalid={!!errors.start_date}
                />
                <Field.Feedback message={errors.start_date} />
              </Field.Group>
            </div>
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-end_date">Selesai</Field.Label>
                <DatePicker
                  id="edit-end_date"
                  name="end_date"
                  value={editingCareer.end_date}
                  invalid={!!errors.end_date}
                />
                <Field.Feedback message={errors.end_date} />
              </Field.Group>
            </div>
          </div>

          <Field.Group>
            <Field.Label for="edit-portfolios">Portofolio terkait</Field.Label>
            <MultiCheck
              name="portfolios"
              options={portfolios.map((p) => ({ value: p.id, label: p.name }))}
              selected={editingSelectedPortfolios}
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
