<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import SettingsLayout from '@/components/admin/settings-layout.svelte';
  import MediaField from '@/components/media/media-field.svelte';
  import { Field } from '@/components/ui/field';
  import AtSign from '@lucide/svelte/icons/at-sign';
  import Mail from '@lucide/svelte/icons/mail';
  import ShieldCheck from '@lucide/svelte/icons/shield-check';
  import Search from '@lucide/svelte/icons/search';
  import Share2 from '@lucide/svelte/icons/share-2';
  import ChartColumn from '@lucide/svelte/icons/chart-column';
  import Globe from '@lucide/svelte/icons/globe';
  import { router } from '@inertiajs/svelte';
  import { Form } from '@inertiajs/svelte';
  import { update, test } from '@/wayfinder/routes/admin/system-settings';

  type SettingField = {
    key: string;
    label: string;
    type: string;
    placeholder: string;
    note?: string;
    min?: number;
    max?: number;
    options?: { value: string; label: string }[];
  };

  type SettingGroup = {
    value: string;
    label: string;
    description: string;
    fields: SettingField[];
  };

  let {
    groups,
    values,
    secretsSet = [],
    status = {},
  }: {
    groups: SettingGroup[];
    values: Record<string, string | null>;
    secretsSet?: string[];
    status?: Record<string, { enabled: boolean; configured: boolean }>;
  } = $props();

  const icons = { information: AtSign, social_media: Share2, mail: Mail, seo: Search, security: ShieldCheck, visitor_analytics: ChartColumn, ipinfo: Globe } as const;

  function initialTab(): string {
    const tab =
      typeof window === 'undefined'
        ? null
        : new URLSearchParams(window.location.search).get('tab');

    return groups.some((group) => group.value === tab) ? tab! : groups[0].value;
  }

  let activeTab = $state(initialTab());

  function selectTab(value: string): void {
    activeTab = value;

    const url = new URL(window.location.href);
    url.searchParams.set('tab', value);
    window.history.replaceState(window.history.state, '', url);
  }
</script>

<AppHead title="Pengaturan Sistem" />

<div id="system-settings-page">
  <AdminPageHeader
    title="Pengaturan Sistem"
    subtitle="Kelola informasi kontak, sosial media, email penerima pesan, serta SEO dan analitik."
  />

  <Form {...update.form()} novalidate options={{ preserveScroll: true }}>
    {#snippet children({ errors, processing })}
      <SettingsLayout>
        {#snippet nav()}
          {#each groups as group (group.value)}
            {@const Icon = icons[group.value as keyof typeof icons]}
            <button
              type="button"
              class="nav-link"
              class:active={activeTab === group.value}
              id={`system-setting-${group.value}-tab`}
              role="tab"
              aria-controls={`system-setting-${group.value}-pane`}
              aria-selected={activeTab === group.value}
              onclick={() => selectTab(group.value)}
            >
              <Icon size={16} aria-hidden="true" />
              <span>{group.label}</span>
              {#if group.fields.some((field) => errors[field.key])}
                <span class="badge text-bg-danger rounded-pill">!</span>
              {/if}
            </button>
          {/each}
        {/snippet}

        <div class="card">
          <div class="card-body">
            <div class="tab-content">
              {#each groups as group (group.value)}
                <div
                  class="tab-pane fade"
                  class:show={activeTab === group.value}
                  class:active={activeTab === group.value}
                  id={`system-setting-${group.value}-pane`}
                  role="tabpanel"
                  aria-labelledby={`system-setting-${group.value}-tab`}
                  tabindex="0"
                >
                  <h2 class="h6">{group.label}</h2>
                  <p class="text-muted small">{group.description}</p>

                  <div class="d-flex flex-column gap-3">
                    {#each group.fields as field (field.key)}
                      <Field.Group>
                        <Field.Label for={`setting-${field.key}`}>{field.label}</Field.Label>
                        {#if field.type === 'image'}
                          <MediaField
                            name={field.key}
                            value={values[field.key]}
                            accept="image"
                            invalid={!!errors[field.key]}
                          />
                        {:else if field.type === 'toggle'}
                          <input type="hidden" name={field.key} value="0" />
                          <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id={`setting-${field.key}`} name={field.key} value="1" checked={values[field.key] === '1'} />
                          </div>
                        {:else if field.type === 'select'}
                          <select class="form-select" id={`setting-${field.key}`} name={field.key}>
                            <option value="">Bawaan</option>
                            {#each field.options ?? [] as option (option.value)}
                              <option value={option.value} selected={values[field.key] === option.value}>{option.label}</option>
                            {/each}
                          </select>
                        {:else if field.type === 'secret'}
                          <Field.Input
                            id={`setting-${field.key}`}
                            name={field.key}
                            type="password"
                            autocomplete="new-password"
                            placeholder={secretsSet.includes(field.key) ? '•••••••• (tersimpan, kosongkan untuk mempertahankan)' : field.placeholder}
                            invalid={!!errors[field.key]}
                          />
                          {#if secretsSet.includes(field.key)}
                            <div class="form-check mt-1">
                              <input class="form-check-input" type="checkbox" id={`clear-${field.key}`} name={`${field.key}__clear`} value="1" />
                              <label class="form-check-label small" for={`clear-${field.key}`}>Hapus nilai tersimpan</label>
                            </div>
                          {/if}
                        {:else}
                          <Field.Input
                            id={`setting-${field.key}`}
                            name={field.key}
                            type={field.type === 'gtag' || field.type === 'digits' ? 'text' : field.type === 'integer' ? 'number' : field.type}
                            placeholder={field.placeholder}
                            value={values[field.key]}
                            invalid={!!errors[field.key]}
                          />
                        {/if}
                        <Field.Feedback message={errors[field.key]} />
                        {#if field.note}
                          <p class="text-muted small mb-0">{field.note}</p>
                        {/if}
                      </Field.Group>
                    {/each}
                  </div>
                  {#if status[group.value]}
                    <div class="mt-3 d-flex align-items-center gap-2">
                      <span class="badge {status[group.value].enabled && status[group.value].configured ? 'text-bg-success' : 'text-bg-secondary'}">
                        {status[group.value].configured ? (status[group.value].enabled ? 'Aktif' : 'Nonaktif') : 'Belum dikonfigurasi'}
                      </span>
                      <button type="button" class="btn btn-sm btn-outline-secondary" disabled={!status[group.value].configured} onclick={() => router.post(test().url, {}, { preserveScroll: true })}>
                        Uji koneksi (simpan dulu)
                      </button>
                    </div>
                  {/if}
                </div>
              {/each}
            </div>

            <div class="mt-4">
              <button type="submit" class="btn btn-primary" disabled={processing}>
                Simpan Perubahan
              </button>
            </div>
          </div>
        </div>
      </SettingsLayout>
    {/snippet}
  </Form>
</div>
