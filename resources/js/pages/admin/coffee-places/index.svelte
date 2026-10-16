<script lang="ts">
  import { t } from '@/lib/i18n';
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
  import type { InfiniteList, TableFilters } from '@/types/pagination';

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
    coffeePlaces: InfiniteList<CoffeePlace>;
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

<AppHead title={t('admin-coffee-places.kedai_kopi')} />

<datalist id="region-options">
  {#each regions as region (region)}
    <option value={region}></option>
  {/each}
</datalist>

<AdminPageHeader
  title={t('admin-coffee-places.kedai_kopi')}
  subtitle={t('admin-coffee-places.kelola_daftar_tempat_ngopi_rekomendasi')}
  onCreate={() => (createOpen = true)}
/>

<DataTable
  data={coffeePlaces}
  scrollKey="coffeePlaces"
  {filters}
  url={index().url}
  columns={[
    { label: t('admin.foto') },
    { label: t('admin-coffee-places.nama_alamat'), key: 'name', sortable: true },
    { label: t('admin-coffee-places.tingkat_harga'), key: 'price_tier', sortable: true },
    { label: t('admin-coffee-places.rekomendasi'), key: 'is_recommended', sortable: true },
    { label: t('admin-media.aksi'), align: 'end' },
  ]}
  emptyTitle={t('admin-coffee-places.belum_ada_kedai_kopi')}
  emptyText={t('admin-coffee-places.tambahkan_kedai_kopi_pertama_anda')}
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
      <td class="dt-cell-stack">
        <div class="fw-semibold">{place.name}</div>
        <div class="text-muted small">{place.address}</div>
      </td>
      <td>{priceTierLabel(place.price_tier)}</td>
      <td>{place.is_recommended ? t('admin.ya') : t('admin.tidak')}</td>
      <td class="text-end">
        <div class="d-inline-flex gap-2">
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            onclick={() => openEdit(place)}
          >
            {t('admin.ubah')}
          </button>
          <AdminDeleteButton
            href={destroy(place.id).url}
            label={t('admin-coffee-places.delete_confirm', { name: place.name })}
          />
        </div>
      </td>
    </tr>
  {/snippet}
</DataTable>

<FormModal
  bind:open={createOpen}
  title={t('admin-coffee-places.tambah_kedai_kopi')}
  subtitle={t('admin-coffee-places.catat_tempat_ngopi_baru')}
  size="lg"
>
  {#snippet children()}
    <Form
      {...store.form()}
      class="d-flex flex-column gap-3"
      novalidate
      options={{ preserveScroll: true, only: ['coffeePlaces', 'regions'], reset: ['coffeePlaces'] }}
      onSuccess={() => (createOpen = false)}
    >
      {#snippet children({ errors, processing })}
        <Field.Group>
          <Field.Label for="create-name">{t('admin.nama')}</Field.Label>
          <Field.Input placeholder={t('admin.masukkan_nama')}
            id="create-name"
            name="name"
            required
            invalid={!!errors.name}
          />
          <Field.Feedback message={errors.name} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-address">{t('admin-coffee-places.alamat')}</Field.Label>
          <Field.Input placeholder={t('admin-coffee-places.masukkan_alamat')}
            id="create-address"
            name="address"
            required
            invalid={!!errors.address}
          />
          <Field.Feedback message={errors.address} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-description">{t('admin.deskripsi')}</Field.Label>
          <textarea placeholder={t('admin.masukkan_deskripsi')}
            id="create-description"
            name="description"
            class="form-control"
            class:is-invalid={!!errors.description}
            rows="3"></textarea>
          <Field.Feedback message={errors.description} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-image">{t('admin.foto')}</Field.Label>
          <MediaField name="image" invalid={!!errors.image} />
          <Field.Feedback message={errors.image} />
        </Field.Group>

        <Field.Group>
          <Field.Label>{t('admin.galeri')}</Field.Label>
          <MediaGalleryField {errors} />
        </Field.Group>

        <MapPicker
          idPrefix="create"
          latitudeError={errors.latitude}
          longitudeError={errors.longitude}
        />

        <Field.Group>
          <Field.Label for="create-map_url">{t('admin-coffee-places.tautan_peta')}</Field.Label>
          <Field.Input placeholder="https://contoh.com"
            id="create-map_url"
            name="map_url"
            type="url"
            invalid={!!errors.map_url}
          />
          <Field.Feedback message={errors.map_url} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="create-wifi_provider">{t('admin-coffee-places.penyedia_wifi')}</Field.Label>
          <Field.Input placeholder={t('admin-coffee-places.masukkan_penyedia_wifi')}
            id="create-wifi_provider"
            name="wifi_provider"
            invalid={!!errors.wifi_provider}
          />
          <Field.Feedback message={errors.wifi_provider} />
        </Field.Group>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="create-wifi_speed">{t('admin-coffee-places.kecepatan_wifi')}</Field.Label>
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
              <Field.Label for="create-price_tier">{t('admin-coffee-places.tingkat_harga')}</Field.Label>
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
              <Field.Label for="create-park_fee">{t('admin-coffee-places.biaya_parkir')}</Field.Label>
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
              <Field.Label for="create-opens_at">{t('admin.buka')}</Field.Label>
              <TimePicker id="create-opens_at" name="opens_at" invalid={!!errors.opens_at} />
              <Field.Feedback message={errors.opens_at} />
            </Field.Group>
          </div>
          <div class="col-sm-4">
            <Field.Group>
              <Field.Label for="create-closes_at">{t('admin.tutup')}</Field.Label>
              <TimePicker id="create-closes_at" name="closes_at" invalid={!!errors.closes_at} />
              <Field.Feedback message={errors.closes_at} />
            </Field.Group>
          </div>
        </div>

        <Field.Group>
          <Field.Label for="create-region">{t('admin-coffee-places.wilayah')}</Field.Label>
          <Field.Input placeholder={t('admin-coffee-places.masukkan_wilayah')}
            id="create-region"
            list="region-options"
            autocomplete="off"
            name="region"
            invalid={!!errors.region}
          />
          <Field.Feedback message={errors.region} />
        </Field.Group>

        <Field.Group>
          <Field.Label>{t('admin-coffee-places.fasilitas')}</Field.Label>
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
          {t('admin-coffee-places.rekomendasikan_tempat_ini')}
        </Field.Input.Check>

        <div class="d-flex justify-content-end gap-2">
          <button
            type="button"
            class="btn btn-outline-secondary"
            onclick={() => (createOpen = false)}
          >
            {t('admin.batal')}
          </button>
          <button type="submit" class="btn btn-primary" disabled={processing}
            >{t('admin.simpan')}</button
          >
        </div>
      {/snippet}
    </Form>
  {/snippet}
</FormModal>

<FormModal
  bind:open={editOpen}
  title={t('admin-coffee-places.ubah_kedai_kopi')}
  subtitle={editingPlace?.name ?? ''}
  size="lg"
>
  {#snippet children()}
    {#if editingPlace}
      {@const current = editingPlace}
      <Form
        {...update.form(current.id)}
        class="d-flex flex-column gap-3"
        novalidate
        options={{ preserveScroll: true, only: ['coffeePlaces', 'regions'], reset: ['coffeePlaces'] }}
        onSuccess={() => (editOpen = false)}
      >
        {#snippet children({ errors, processing })}
          <Field.Group>
            <Field.Label for="edit-name">{t('admin.nama')}</Field.Label>
            <Field.Input placeholder={t('admin.masukkan_nama')}
              id="edit-name"
              name="name"
              required
              value={current.name}
              invalid={!!errors.name}
            />
            <Field.Feedback message={errors.name} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-address">{t('admin-coffee-places.alamat')}</Field.Label>
            <Field.Input placeholder={t('admin-coffee-places.masukkan_alamat')}
              id="edit-address"
              name="address"
              required
              value={current.address}
              invalid={!!errors.address}
            />
            <Field.Feedback message={errors.address} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-description">{t('admin.deskripsi')}</Field.Label>
            <textarea placeholder={t('admin.masukkan_deskripsi')}
              id="edit-description"
              name="description"
              class="form-control"
              class:is-invalid={!!errors.description}
              rows="3">{current.description ?? ''}</textarea
            >
            <Field.Feedback message={errors.description} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-image">{t('admin.foto')}</Field.Label>
            <MediaField
              name="image"
              value={current.image}
              invalid={!!errors.image}
            />
            <Field.Feedback message={errors.image} />
          </Field.Group>

          <Field.Group>
            <Field.Label>{t('admin.galeri')}</Field.Label>
            <MediaGalleryField value={current.galleries} {errors} />
          </Field.Group>

          <MapPicker
            idPrefix="edit"
            latitude={current.latitude}
            longitude={current.longitude}
            latitudeError={errors.latitude}
            longitudeError={errors.longitude}
          />

          <Field.Group>
            <Field.Label for="edit-map_url">{t('admin-coffee-places.tautan_peta')}</Field.Label>
            <Field.Input placeholder="https://contoh.com"
              id="edit-map_url"
              name="map_url"
              type="url"
              value={current.map_url}
              invalid={!!errors.map_url}
            />
            <Field.Feedback message={errors.map_url} />
          </Field.Group>

          <Field.Group>
            <Field.Label for="edit-wifi_provider">{t('admin-coffee-places.penyedia_wifi')}</Field.Label>
            <Field.Input placeholder={t('admin-coffee-places.masukkan_penyedia_wifi')}
              id="edit-wifi_provider"
              name="wifi_provider"
              value={current.wifi_provider}
              invalid={!!errors.wifi_provider}
            />
            <Field.Feedback message={errors.wifi_provider} />
          </Field.Group>

          <div class="row g-3">
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-wifi_speed">{t('admin-coffee-places.kecepatan_wifi')}</Field.Label>
                <Select
                  id="edit-wifi_speed"
                  name="wifi_speed"
                  items={wifiSpeeds}
                  value={current.wifi_speed}
                  required
                  invalid={!!errors.wifi_speed}
                />
                <Field.Feedback message={errors.wifi_speed} />
              </Field.Group>
            </div>
            <div class="col-sm-6">
              <Field.Group>
                <Field.Label for="edit-price_tier">{t('admin-coffee-places.tingkat_harga')}</Field.Label>
                <Select
                  id="edit-price_tier"
                  name="price_tier"
                  items={priceTiers}
                  value={current.price_tier}
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
                <Field.Label for="edit-park_fee">{t('admin-coffee-places.biaya_parkir')}</Field.Label>
                <Field.Input placeholder="0"
                  id="edit-park_fee"
                  name="park_fee"
                  type="number"
                  value={current.park_fee}
                  invalid={!!errors.park_fee}
                />
                <Field.Feedback message={errors.park_fee} />
              </Field.Group>
            </div>
            <div class="col-sm-4">
              <Field.Group>
                <Field.Label for="edit-opens_at">{t('admin.buka')}</Field.Label>
                <TimePicker id="edit-opens_at" name="opens_at" value={current.opens_at} invalid={!!errors.opens_at} />
                <Field.Feedback message={errors.opens_at} />
              </Field.Group>
            </div>
            <div class="col-sm-4">
              <Field.Group>
                <Field.Label for="edit-closes_at">{t('admin.tutup')}</Field.Label>
                <TimePicker id="edit-closes_at" name="closes_at" value={current.closes_at} invalid={!!errors.closes_at} />
                <Field.Feedback message={errors.closes_at} />
              </Field.Group>
            </div>
          </div>

          <Field.Group>
            <Field.Label for="edit-region">{t('admin-coffee-places.wilayah')}</Field.Label>
            <Field.Input placeholder={t('admin-coffee-places.masukkan_wilayah')}
              id="edit-region"
              list="region-options"
              autocomplete="off"
              name="region"
              value={current.region}
              invalid={!!errors.region}
            />
            <Field.Feedback message={errors.region} />
          </Field.Group>

          <Field.Group>
            <Field.Label>{t('admin-coffee-places.fasilitas')}</Field.Label>
            <div class="row g-2">
              {#each facilities as facility (facility.value)}
                <div class="col-6 col-sm-4">
                  <Field.Input.Check
                    id="edit-facility-{facility.value}"
                    name="facilities[]"
                    value={facility.value}
          checked={current.facilities?.includes(facility.value) ?? false}
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
            checked={current.is_recommended}
          >
            {t('admin-coffee-places.rekomendasikan_tempat_ini')}
          </Field.Input.Check>

          <div class="d-flex justify-content-end gap-2">
            <button
              type="button"
              class="btn btn-outline-secondary"
              onclick={() => (editOpen = false)}
            >
              {t('admin.batal')}
            </button>
            <button type="submit" class="btn btn-primary" disabled={processing}
              >{t('admin.simpan')}</button
            >
          </div>
        {/snippet}
      </Form>
    {/if}
  {/snippet}
</FormModal>
