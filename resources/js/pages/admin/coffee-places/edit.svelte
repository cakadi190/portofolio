<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import { Field } from '@/components/ui/field';
  import FileDropzone from '@/components/ui/file-dropzone.svelte';
  import { Form } from '@inertiajs/svelte';
  import { index, update } from '@/wayfinder/routes/admin/coffee-places';

  type Option = { value: string; label: string };

  type CoffeePlaceForm = {
    id: number;
    name: string;
    address: string;
    description: string | null;
    latitude: string | null;
    longitude: string | null;
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
    coffeePlace,
    wifiSpeeds,
    priceTiers,
  }: { coffeePlace: CoffeePlaceForm; wifiSpeeds: Option[]; priceTiers: Option[] } = $props();
</script>

<AppHead title="Ubah Kedai Kopi" />

<AdminPageHeader title="Ubah Kedai Kopi" subtitle={coffeePlace.name} />

<div class="card" style="max-width: 640px;">
  <div class="card-body">
    <Form {...update.form(coffeePlace.id)} class="d-flex flex-column gap-3" novalidate>
      {#snippet children({ errors, processing })}
        <Field.Group>
          <Field.Label for="name">Nama</Field.Label>
          <Field.Input id="name" name="name" required value={coffeePlace.name} invalid={!!errors.name} />
          <Field.Feedback message={errors.name} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="address">Alamat</Field.Label>
          <Field.Input
            id="address"
            name="address"
            required
            value={coffeePlace.address}
            invalid={!!errors.address}
          />
          <Field.Feedback message={errors.address} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="description">Deskripsi</Field.Label>
          <textarea
            id="description"
            name="description"
            class="form-control"
            class:is-invalid={!!errors.description}
            rows="3">{coffeePlace.description ?? ''}</textarea
          >
          <Field.Feedback message={errors.description} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="image">Foto</Field.Label>
          <FileDropzone
            name="image"
            existingUrl={coffeePlace.image ? `/storage/${coffeePlace.image}` : null}
            invalid={!!errors.image}
          />
          <Field.Feedback message={errors.image} />
        </Field.Group>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="latitude">Latitude</Field.Label>
              <Field.Input
                id="latitude"
                name="latitude"
                type="number"
                step="any"
                value={coffeePlace.latitude}
                invalid={!!errors.latitude}
              />
              <Field.Feedback message={errors.latitude} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="longitude">Longitude</Field.Label>
              <Field.Input
                id="longitude"
                name="longitude"
                type="number"
                step="any"
                value={coffeePlace.longitude}
                invalid={!!errors.longitude}
              />
              <Field.Feedback message={errors.longitude} />
            </Field.Group>
          </div>
        </div>

        <Field.Group>
          <Field.Label for="map_url">Tautan Peta</Field.Label>
          <Field.Input
            id="map_url"
            name="map_url"
            type="url"
            value={coffeePlace.map_url}
            invalid={!!errors.map_url}
          />
          <Field.Feedback message={errors.map_url} />
        </Field.Group>

        <Field.Group>
          <Field.Label for="wifi_provider">Penyedia WiFi</Field.Label>
          <Field.Input
            id="wifi_provider"
            name="wifi_provider"
            value={coffeePlace.wifi_provider}
            invalid={!!errors.wifi_provider}
          />
          <Field.Feedback message={errors.wifi_provider} />
        </Field.Group>

        <div class="row g-3">
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="wifi_speed">Kecepatan WiFi</Field.Label>
              <select
                id="wifi_speed"
                name="wifi_speed"
                class="form-select"
                class:is-invalid={!!errors.wifi_speed}
                required
              >
                {#each wifiSpeeds as option (option.value)}
                  <option value={option.value} selected={option.value === coffeePlace.wifi_speed}>
                    {option.label}
                  </option>
                {/each}
              </select>
              <Field.Feedback message={errors.wifi_speed} />
            </Field.Group>
          </div>
          <div class="col-sm-6">
            <Field.Group>
              <Field.Label for="price_tier">Tingkat Harga</Field.Label>
              <select
                id="price_tier"
                name="price_tier"
                class="form-select"
                class:is-invalid={!!errors.price_tier}
                required
              >
                {#each priceTiers as option (option.value)}
                  <option value={option.value} selected={option.value === coffeePlace.price_tier}>
                    {option.label}
                  </option>
                {/each}
              </select>
              <Field.Feedback message={errors.price_tier} />
            </Field.Group>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-sm-4">
            <Field.Group>
              <Field.Label for="park_fee">Biaya Parkir</Field.Label>
              <Field.Input
                id="park_fee"
                name="park_fee"
                type="number"
                value={coffeePlace.park_fee}
                invalid={!!errors.park_fee}
              />
              <Field.Feedback message={errors.park_fee} />
            </Field.Group>
          </div>
          <div class="col-sm-4">
            <Field.Group>
              <Field.Label for="opens_at">Buka</Field.Label>
              <Field.Input
                id="opens_at"
                name="opens_at"
                type="time"
                value={coffeePlace.opens_at}
                invalid={!!errors.opens_at}
              />
              <Field.Feedback message={errors.opens_at} />
            </Field.Group>
          </div>
          <div class="col-sm-4">
            <Field.Group>
              <Field.Label for="closes_at">Tutup</Field.Label>
              <Field.Input
                id="closes_at"
                name="closes_at"
                type="time"
                value={coffeePlace.closes_at}
                invalid={!!errors.closes_at}
              />
              <Field.Feedback message={errors.closes_at} />
            </Field.Group>
          </div>
        </div>

        <Field.Group>
          <Field.Label for="region">Wilayah</Field.Label>
          <Field.Input id="region" name="region" value={coffeePlace.region} invalid={!!errors.region} />
          <Field.Feedback message={errors.region} />
        </Field.Group>

        <input type="hidden" name="is_recommended" value="0" />
        <Field.Input.Check
          id="is_recommended"
          name="is_recommended"
          value="1"
          checked={coffeePlace.is_recommended}
        >
          Rekomendasikan tempat ini
        </Field.Input.Check>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary" disabled={processing}>Simpan</button>
          <a href={index().url} class="btn btn-outline-secondary">Batal</a>
        </div>
      {/snippet}
    </Form>
  </div>
</div>
