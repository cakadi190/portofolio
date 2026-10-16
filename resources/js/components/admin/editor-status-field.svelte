<script lang="ts">
  import { t } from '@/lib/i18n';
  /** Draft / published radios for the editor's "Status & visibilitas" popover. */
  let {
    isPublished,
    scheduled = false,
    onStatus,
  }: {
    isPublished: boolean;
    scheduled?: boolean;
    onStatus: (published: boolean) => void;
  } = $props();

  const uid = $props.id();

  const options = $derived([
    {
      published: false,
      label: t('admin.draf'),
      hint: t('admin.belum_siap_untuk_dipublikasikan'),
    },
    {
      published: true,
      label: scheduled ? t('admin.terjadwal') : t('admin.telah_terbit'),
      hint: scheduled
        ? t('admin.tayang_otomatis_pada_tanggal_terbit_yang')
        : t('admin.dapat_dilihat_siapa_saja'),
    },
  ]);
</script>

<div class="d-flex flex-column gap-2" role="radiogroup" aria-label={t('admin.status')}>
  {#each options as option (option.label)}
    <label class="d-flex gap-2 align-items-start">
      <input
        type="radio"
        class="form-check-input mt-1"
        name="{uid}-status"
        checked={isPublished === option.published}
        onchange={() => onStatus(option.published)}
      />
      <span>
        <span class="d-block fw-semibold">{option.label}</span>
        <span class="d-block text-secondary">{option.hint}</span>
      </span>
    </label>
  {/each}
</div>
