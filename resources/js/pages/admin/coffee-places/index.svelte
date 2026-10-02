<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminDeleteButton from '@/components/admin/admin-delete-button.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import FormModal from '@/components/form-modal.svelte';
  import DataTable from '@/components/admin/data-table.svelte';
  import { Field } from '@/components/ui/field';
  import Select from '@/components/ui/select.svelte';
  import MediaField from '@/components/media/media-field.svelte';
  import TimePicker from '@/components/ui/time-picker.svelte';
  import MediaGalleryField from '@/components/media/media-gallery-field.svelte';
  import MapPicker from '@/components/ui/map-picker.svelte';
  import { Form } from '@inertiajs/svelte';
  import { storageUrl } from '@/lib/utils';
  import {
    destroy,
    store,
    update,
    index,
  } from '@/wayfinder/routes/admin/coffee-places';
  import type { Paginated, TableFilters } from '@/types/pagination';

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
    facilities: string[] | null;
    galleries: { image_url: string; description: string | null }[];
    is_recommended: boolean;
  };

  let {
    coffeePlaces,
    wifiSpeeds,
    priceTiers,
    facilities,
    regions,
    filters,
  }: {
    filters: TableFilters;
    coffeePlaces: Paginated<CoffeePlace>;
    wifiSpeeds: Option[];
    priceTiers: Option[];
    facilities: Option[];
    regions: string[];
  } = $props();

  let createOpen = $state(false);
  let editOpen = $state(false);
  let editingPlace = $state<CoffeePlace | null>(null);

  function priceTierLabel(value: string): string {
    return priceTiers.find((tier) => tier.value === value)?.label ?? value;
  }

  function openEdit(place: CoffeePlace): void {
    editingPlace = place;
    editOpen = true;
  }
</script>

<AppHead title="Kedai Kopi" />

<datalist id="region-options">
  {#each regions as region (region)}
    <option value={region}></option>
  {/each}
</datalist>

<AdminPageHeader
  title="Kedai Kopi"
  subtitle="Kelola daftar tempat ngopi rekomendasi."
  onCreate={() => (createOpen = true)}
/>

<DataTable
  data={coffeePlaces}
  {filters}
  url={index().url}
  columns={[
    { label: 'Foto' },
    { label: 'Nama & Alamat', key: 'name', sortable: true },
    { label: 'Tingkat Harga', key: 'price_tier', sortable: true },
    { label: 'Rekomendasi', key: 'is_recommended', sortable: true },
    { label: 'Aksi', align: 'end' },
  ]}
  emptyTitle="Belum ada kedai kopi"
  emptyText="Tambahkan kedai kopi pertama Anda."
>
  {#snippet row(place)}
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
      <td>
        <div class="fw-semibold">{place.name}</div>
        <div class="text-muted small">{place.address}</div>
      </td>
      <td>{priceTierLabel(place.price_tier)}</td>
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
  {/snippet}
</DataTable>

<FormModal
  bind:open={createOpen}
  title="Tambah Kedai Kopi"
  subtitle="Catat tempat ngopi baru."
  size="lg"
>
  {#snippet children()}
    <Form
      {...store.form()}
      class="d-flex flex-column gap-3"
      novalidate
      options={{ preserveScroll: true, only: ['coffeePlaces', 'regions'] }}
      onSuccess={() => (createOpen = false)}
    >
      {#snippet children({ errors, processing })}
        <Field.Group>
          <Field.Label for="create-name">Nama</Field.Label>
          <Field.Input placeholder="Masukkan nama"
            id="create-name"
            name="name"
            required
            invalid={!!errors.name}
          />
          <Field.Feedback message={errors.name} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-address">Alamat</Field.Label>
          <Field.Input placeholder="Masukkan alamat"
            id="create-address"
            name="address"
            required
            invalid={!!errors.address}
          />
          <Field.Feedback message={errors.address} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-description">Deskripsi</Field.Label>
          <textarea placeholder="Masukkan deskripsi"
            id="create-description"
            name="description"
            class="form-control"
            class:is-invalid={!!errors.description}
            rows="3"></textarea>
          <Field.Feedback message={errors.description} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-image">Foto</Field.Label>
          <MediaField name="image" invalid={!!errors.image} />
          <Field.Feedback message={errors.image} />
        </Field.Group>

        <Field.Group>
          <Field.Label>Galeri</Field.Label>
          <MediaGalleryField {errors} />
        </Field.Group>

        <MapPicker
          idPrefix="create"
          latitudeError={errors.latitude}
          longitudeError={errors.longitude}
        />

        <Field.Group>
          <Field.Label for="create-map_url">Tautan Peta</Field.Label>
          <Field.Input placeholder="https://contoh.com"
            id="create-map_url"
            name="map_url"
            type="url"
            invalid={!!errors.map_url}
          />
          <Field.Feedback message={errors.map_url} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-wifi_provider">Penyedia WiFi</Field.Label>
          <Field.Input placeholder="Masukkan penyedia WiFi"
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
              <Select
                id="create-wifi_speed"
                name="wifi_speed"
                items={wifiSpeeds}
                required
                invalid={!!errors.wifi_speed}
              />
              <Field.Feedback message={errors.wifi_speed} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-price_tier">Tingkat Harga</Field.Label>
              <Select
                id="create-price_tier"
                name="price_tier"
                items={priceTiers}
                required
                invalid={!!errors.price_tier}
              />
              <Field.Feedback message={errors.price_tier} />
            </Field.Group>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-sm-4">
            <Field.Group>
              <Field.Label for="create-park_fee">Biaya Parkir</Field.Label>
              <Field.Input placeholder="0"
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
              <TimePicker id="create-opens_at" name="opens_at" invalid={!!errors.opens_at} />
              <Field.Feedback message={errors.opens_at} />
            </Field.Group>
          </div>
          <div class="col-sm-4">
            <Field.Group>
              <Field.Label for="create-closes_at">Tutup</Field.Label>
              <TimePicker id="create-closes_at" name="closes_at" invalid={!!errors.closes_at} />
              <Field.Feedback message={errors.closes_at} />
            </Field.Group>
          </div>
        </div>

        <Field.Group>
          <Field.Label for="create-region">Wilayah</Field.Label>
          <Field.Input placeholder="Masukkan wilayah"
            id="create-region"
            list="region-options"
            autocomplete="off"
            name="region"
            invalid={!!errors.region}
          />
          <Field.Feedback message={errors.region} />
        </Field.Group>

        <Field.Group>
          <Field.Label>Fasilitas</Field.Label>
          <div class="row g-2">
            {#each facilities as facility (facility.value)}
              <div class="col-6 col-sm-4">
                <Field.Input.Check
                  id="create-facility-{facility.value}"
                  name="facilities[]"
                  value={facility.value}
                >
                  {facility.label}
                </Field.Input.Check>
              </div>
            {/each}
          </div>
          <Field.Feedback message={errors.facilities} />
        </Field.Group>

        <input type="hidden" name="is_recommended" value="0" />
        <Field.Input.Check
          id="create-is_recommended"
          name="is_recommended"
          value="1"
        >
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
  title="Ubah Kedai Kopi"
  subtitle={editingPlace?.name ?? ''}
  size="lg"
>
  {#snippet children()}
    {#if editingPlace}
      <Form
        {...update.form(editingPlace.id)}
        class="d-flex flex-column gap-3"
        novalidate
        options={{ preserveScroll: true, only: ['coffeePlaces', 'regions'] }}
        onSuccess={() => (editOpen = false)}
      >
        {#snippet children({ errors, processing })}
          <Field.Group>
            <Field.Label for="edit-name">Nama</Field.Label>
            <Field.Input placeholder="Masukkan nama"
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
            <Field.Input placeholder="Masukkan alamat"
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
            <textarea placeholder="Masukkan deskripsi"
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
            <MediaField
              name="image"
              value={editingPlace.image}
              invalid={!!errors.image}
            />
            <Field.Feedback message={errors.image} />
          </Field.Group>

          <Field.Group>
            <Field.Label>Galeri</Field.Label>
            <MediaGalleryField value={editingPlace.galleries} {errors} />
          </Field.Group>

          <MapPicker
            idPrefix="edit"
            latitude={editingPlace.latitude}
            longitude={editingPlace.longitude}
            latitudeError={errors.latitude}
            longitudeError={errors.longitude}
          />

          <Field.Group>
            <Field.Label for="edit-map_url">Tautan Peta</Field.Label>
            <Field.Input placeholder="https://contoh.com"
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
            <Field.Input placeholder="Masukkan penyedia WiFi"
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
                <Select
                  id="edit-wifi_speed"
                  name="wifi_speed"
                  items={wifiSpeeds}
                  value={editingPlace.wifi_speed}
                  required
                  invalid={!!errors.wifi_speed}
                />
                <Field.Feedback message={errors.wifi_speed} />
              </Field.Group>
            </div>
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-price_tier">Tingkat Harga</Field.Label>
                <Select
                  id="edit-price_tier"
                  name="price_tier"
                  items={priceTiers}
                  value={editingPlace.price_tier}
                  required
                  invalid={!!errors.price_tier}
                />
                <Field.Feedback message={errors.price_tier} />
              </Field.Group>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-sm-4">
              <Field.Group>
                <Field.Label for="edit-park_fee">Biaya Parkir</Field.Label>
                <Field.Input placeholder="0"
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
                <TimePicker id="edit-opens_at" name="opens_at" value={editingPlace.opens_at} invalid={!!errors.opens_at} />
                <Field.Feedback message={errors.opens_at} />
              </Field.Group>
            </div>
            <div class="col-sm-4">
              <Field.Group>
                <Field.Label for="edit-closes_at">Tutup</Field.Label>
                <TimePicker id="edit-closes_at" name="closes_at" value={editingPlace.closes_at} invalid={!!errors.closes_at} />
                <Field.Feedback message={errors.closes_at} />
              </Field.Group>
            </div>
          </div>

          <Field.Group>
            <Field.Label for="edit-region">Wilayah</Field.Label>
            <Field.Input placeholder="Masukkan wilayah"
              id="edit-region"
              list="region-options"
              autocomplete="off"
              name="region"
              value={editingPlace.region}
              invalid={!!errors.region}
            />
            <Field.Feedback message={errors.region} />
          </Field.Group>

          <Field.Group>
            <Field.Label>Fasilitas</Field.Label>
            <div class="row g-2">
              {#each facilities as facility (facility.value)}
                <div class="col-6 col-sm-4">
                  <Field.Input.Check
                    id="edit-facility-{facility.value}"
                    name="facilities[]"
                    value={facility.value}
          checked={editingPlace.facilities?.includes(facility.value) ?? false}
                  >
                    {facility.label}
                  </Field.Input.Check>
                </div>
              {/each}
            </div>
            <Field.Feedback message={errors.facilities} />
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
            <button type="submit" class="btn btn-primary" disabled={processing}
              >Simpan</button
            >
          </div>
        {/snippet}
      </Form>
    {/if}
  {/snippet}
</FormModal>
