<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import AdminPageHeader from '@/components/admin/admin-page-header.svelte';
  import SettingsLayout from '@/components/admin/settings-layout.svelte';
  import MediaField from '@/components/media/media-field.svelte';
  import { Field } from '@/components/ui/field';
  import AtSign from '@lucide/svelte/icons/at-sign';
  import Mail from '@lucide/svelte/icons/mail';
  import Search from '@lucide/svelte/icons/search';
  import Share2 from '@lucide/svelte/icons/share-2';
  import { Form } from '@inertiajs/svelte';
  import { update } from '@/wayfinder/routes/admin/system-settings';

  type SettingField = {
    key: string;
    label: string;
    type: string;
    placeholder: string;
    note?: string;
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
  }: {
    groups: SettingGroup[];
    values: Record<string, string | null>;
  } = $props();

  const icons = { information: AtSign, social_media: Share2, mail: Mail, seo: Search } as const;

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
                        {:else}
                          <Field.Input
                            id={`setting-${field.key}`}
                            name={field.key}
                            type={field.type === 'gtag' || field.type === 'digits' ? 'text' : field.type}
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
