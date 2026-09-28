<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import EmptyState from '@/components/empty-state.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import SimplePaginator from '@/components/simple-paginator.svelte';
  import { Field } from '@/components/ui/field';
  import FileDropzone from '@/components/ui/file-dropzone.svelte';
  import { Form } from '@inertiajs/svelte';
  import { select2 } from '@/lib/select2';
  import { storageUrl } from '@/lib/utils';
  import { destroy, store, update } from '@/wayfinder/routes/admin/coffee-places';
  import type { Paginated } from '@/types/pagination';

  type Option = { value: string; label: string };

  type CoffeePlace = {
    id: number;
    name: string;
    address: string;
    description: string | null;
    latitude: number | null;
    longitude: number | null;
    map_url: string | null;
    image: string | null;
    wifi_provider: string | null;
    wifi_speed: string;
    price_tier: string;
    park_fee: number | null;
    opens_at: string | null;
    closes_at: string | null;
    region: string | null;
    is_recommended: boolean;
  };

  let {
    coffeePlaces,
    wifiSpeeds,
    priceTiers,
  }: { coffeePlaces: Paginated<CoffeePlace>; wifiSpeeds: Option[]; priceTiers: Option[] } =
    $props();

  let createOpen = $state(false);
  let editOpen = $state(false);
  let editingPlace = $state<CoffeePlace | null>(null);

  function openEdit(place: CoffeePlace): void {
    editingPlace = place;
    editOpen = true;
  }
</script>

<AppHead title="Kedai Kopi" />

<AdminPageHeader
  title="Kedai Kopi"
  subtitle="Kelola daftar tempat ngopi rekomendasi."
  onCreate={() => (createOpen = true)}
/>

{#if coffeePlaces.data.length === 0}
  <EmptyState title="Belum ada kedai kopi" text="Tambahkan kedai kopi pertama Anda." />
{:else}
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Foto</th>
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
            <td>
              {#if place.image}
                <img
                  src={storageUrl(place.image) ?? ''}
                  alt={place.name}
                  loading="lazy"
                  style="width:3.5rem;height:2.5rem;object-fit:cover;border-radius:0.5rem;"
                />
              {:else}
                <span class="text-muted">&mdash;</span>
              {/if}
            </td>
            <td>{place.name}</td>
            <td>{place.address}</td>
            <td>{place.price_tier}</td>
            <td>{place.is_recommended ? 'Ya' : 'Tidak'}</td>
            <td class="text-end">
              <div class="d-inline-flex gap-2">
                <button
                  type="button"
                  class="btn btn-sm btn-outline-secondary"
                  onclick={() => openEdit(place)}
                >
                  Ubah
                </button>
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

<FormModal bind:open={createOpen} title="Tambah Kedai Kopi" subtitle="Catat tempat ngopi baru." size="lg">
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
          <Field.Input id="create-name" name="name" required invalid={!!errors.name} />
          <Field.Feedback message={errors.name} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-address">Alamat</Field.Label>
          <Field.Input id="create-address" name="address" required invalid={!!errors.address} />
          <Field.Feedback message={errors.address} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-description">Deskripsi</Field.Label>
          <textarea
            id="create-description"
            name="description"
            class="form-control"
            class:is-invalid={!!errors.description}
            rows="3"
          ></textarea>
          <Field.Feedback message={errors.description} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-image">Foto</Field.Label>
          <FileDropzone name="image" invalid={!!errors.image} />
          <Field.Feedback message={errors.image} />
        </Field.Group>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-latitude">Latitude</Field.Label>
              <Field.Input
                id="create-latitude"
                name="latitude"
                type="number"
                step="any"
                invalid={!!errors.latitude}
              />
              <Field.Feedback message={errors.latitude} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-longitude">Longitude</Field.Label>
              <Field.Input
                id="create-longitude"
                name="longitude"
                type="number"
                step="any"
                invalid={!!errors.longitude}
              />
              <Field.Feedback message={errors.longitude} />
            </Field.Group>
          </div>
        </div>

        <Field.Group>
          <Field.Label for="create-map_url">Tautan Peta</Field.Label>
          <Field.Input id="create-map_url" name="map_url" type="url" invalid={!!errors.map_url} />
          <Field.Feedback message={errors.map_url} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-wifi_provider">Penyedia WiFi</Field.Label>
          <Field.Input
            id="create-wifi_provider"
            name="wifi_provider"
            invalid={!!errors.wifi_provider}
          />
          <Field.Feedback message={errors.wifi_provider} />
        </Field.Group>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-wifi_speed">Kecepatan WiFi</Field.Label>
              <select
                id="create-wifi_speed"
                name="wifi_speed"
                class="form-select"
                class:is-invalid={!!errors.wifi_speed}
                required
                use:select2
              >
                {#each wifiSpeeds as option (option.value)}
                  <option value={option.value}>{option.label}</option>
                {/each}
              </select>
              <Field.Feedback message={errors.wifi_speed} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-price_tier">Tingkat Harga</Field.Label>
              <select
                id="create-price_tier"
                name="price_tier"
                class="form-select"
                class:is-invalid={!!errors.price_tier}
                required
                use:select2
              >
                {#each priceTiers as option (option.value)}
                  <option value={option.value}>{option.label}</option>
                {/each}
              </select>
              <Field.Feedback message={errors.price_tier} />
            </Field.Group>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-sm-4">
            <Field.Group>
              <Field.Label for="create-park_fee">Biaya Parkir</Field.Label>
              <Field.Input
                id="create-park_fee"
                name="park_fee"
                type="number"
                invalid={!!errors.park_fee}
              />
              <Field.Feedback message={errors.park_fee} />
            </Field.Group>
          </div>
          <div class="col-sm-4">
            <Field.Group>
              <Field.Label for="create-opens_at">Buka</Field.Label>
              <Field.Input
                id="create-opens_at"
                name="opens_at"
                type="time"
                invalid={!!errors.opens_at}
              />
              <Field.Feedback message={errors.opens_at} />
            </Field.Group>
          </div>
          <div class="col-sm-4">
            <Field.Group>
              <Field.Label for="create-closes_at">Tutup</Field.Label>
              <Field.Input
                id="create-closes_at"
                name="closes_at"
                type="time"
                invalid={!!errors.closes_at}
              />
              <Field.Feedback message={errors.closes_at} />
            </Field.Group>
          </div>
        </div>

        <Field.Group>
          <Field.Label for="create-region">Wilayah</Field.Label>
          <Field.Input id="create-region" name="region" invalid={!!errors.region} />
          <Field.Feedback message={errors.region} />
        </Field.Group>

        <input type="hidden" name="is_recommended" value="0" />
        <Field.Input.Check id="create-is_recommended" name="is_recommended" value="1">
          Rekomendasikan tempat ini
        </Field.Input.Check>

        <div class="d-flex justify-content-end gap-2">
          <button
            type="button"
            class="btn btn-outline-secondary"
            onclick={() => (createOpen = false)}
          >
            Batal
          </button>
          <button type="submit" class="btn btn-primary" disabled={processing}>Simpan</button>
        </div>
      {/snippet}
    </Form>
  {/snippet}
</FormModal>

<FormModal bind:open={editOpen} title="Ubah Kedai Kopi" subtitle={editingPlace?.name ?? ''} size="lg">
  {#snippet children()}
    {#if editingPlace}
      <Form
        {...update.form(editingPlace.id)}
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
              value={editingPlace.name}
              invalid={!!errors.name}
            />
            <Field.Feedback message={errors.name} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-address">Alamat</Field.Label>
            <Field.Input
              id="edit-address"
              name="address"
              required
              value={editingPlace.address}
              invalid={!!errors.address}
            />
            <Field.Feedback message={errors.address} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-description">Deskripsi</Field.Label>
            <textarea
              id="edit-description"
              name="description"
              class="form-control"
              class:is-invalid={!!errors.description}
              rows="3">{editingPlace.description ?? ''}</textarea
            >
            <Field.Feedback message={errors.description} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-image">Foto</Field.Label>
            <FileDropzone
              name="image"
              existingUrl={editingPlace.image}
              invalid={!!errors.image}
            />
            <Field.Feedback message={errors.image} />
          </Field.Group>

          <div class="row g-3">
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-latitude">Latitude</Field.Label>
                <Field.Input
                  id="edit-latitude"
                  name="latitude"
                  type="number"
                  step="any"
                  value={editingPlace.latitude}
                  invalid={!!errors.latitude}
                />
                <Field.Feedback message={errors.latitude} />
              </Field.Group>
            </div>
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-longitude">Longitude</Field.Label>
                <Field.Input
                  id="edit-longitude"
                  name="longitude"
                  type="number"
                  step="any"
                  value={editingPlace.longitude}
                  invalid={!!errors.longitude}
                />
                <Field.Feedback message={errors.longitude} />
              </Field.Group>
            </div>
          </div>

          <Field.Group>
            <Field.Label for="edit-map_url">Tautan Peta</Field.Label>
            <Field.Input
              id="edit-map_url"
              name="map_url"
              type="url"
              value={editingPlace.map_url}
              invalid={!!errors.map_url}
            />
            <Field.Feedback message={errors.map_url} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-wifi_provider">Penyedia WiFi</Field.Label>
            <Field.Input
              id="edit-wifi_provider"
              name="wifi_provider"
              value={editingPlace.wifi_provider}
              invalid={!!errors.wifi_provider}
            />
            <Field.Feedback message={errors.wifi_provider} />
          </Field.Group>

          <div class="row g-3">
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-wifi_speed">Kecepatan WiFi</Field.Label>
                <select
                  id="edit-wifi_speed"
                  name="wifi_speed"
                  class="form-select"
                  class:is-invalid={!!errors.wifi_speed}
                  required
                  value={editingPlace.wifi_speed}
                  use:select2
                >
                  {#each wifiSpeeds as option (option.value)}
                    <option value={option.value}>{option.label}</option>
                  {/each}
                </select>
                <Field.Feedback message={errors.wifi_speed} />
              </Field.Group>
            </div>
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-price_tier">Tingkat Harga</Field.Label>
                <select
                  id="edit-price_tier"
                  name="price_tier"
                  class="form-select"
                  class:is-invalid={!!errors.price_tier}
                  required
                  value={editingPlace.price_tier}
                  use:select2
                >
                  {#each priceTiers as option (option.value)}
                    <option value={option.value}>{option.label}</option>
                  {/each}
                </select>
                <Field.Feedback message={errors.price_tier} />
              </Field.Group>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-sm-4">
              <Field.Group>
                <Field.Label for="edit-park_fee">Biaya Parkir</Field.Label>
                <Field.Input
                  id="edit-park_fee"
                  name="park_fee"
                  type="number"
                  value={editingPlace.park_fee}
                  invalid={!!errors.park_fee}
                />
                <Field.Feedback message={errors.park_fee} />
              </Field.Group>
            </div>
            <div class="col-sm-4">
              <Field.Group>
                <Field.Label for="edit-opens_at">Buka</Field.Label>
                <Field.Input
                  id="edit-opens_at"
                  name="opens_at"
                  type="time"
                  value={editingPlace.opens_at}
                  invalid={!!errors.opens_at}
                />
                <Field.Feedback message={errors.opens_at} />
              </Field.Group>
            </div>
            <div class="col-sm-4">
              <Field.Group>
                <Field.Label for="edit-closes_at">Tutup</Field.Label>
                <Field.Input
                  id="edit-closes_at"
                  name="closes_at"
                  type="time"
                  value={editingPlace.closes_at}
                  invalid={!!errors.closes_at}
                />
                <Field.Feedback message={errors.closes_at} />
              </Field.Group>
            </div>
          </div>

          <Field.Group>
            <Field.Label for="edit-region">Wilayah</Field.Label>
            <Field.Input
              id="edit-region"
              name="region"
              value={editingPlace.region}
              invalid={!!errors.region}
            />
            <Field.Feedback message={errors.region} />
          </Field.Group>

          <input type="hidden" name="is_recommended" value="0" />
          <Field.Input.Check
            id="edit-is_recommended"
            name="is_recommended"
            value="1"
            checked={editingPlace.is_recommended}
          >
            Rekomendasikan tempat ini
          </Field.Input.Check>

          <div class="d-flex justify-content-end gap-2">
            <button
              type="button"
              class="btn btn-outline-secondary"
              onclick={() => (editOpen = false)}
            >
              Batal
            </button>
            <button type="submit" class="btn btn-primary" disabled={processing}>Simpan</button>
          </div>
        {/snippet}
      </Form>
    {/if}
  {/snippet}
</FormModal>
