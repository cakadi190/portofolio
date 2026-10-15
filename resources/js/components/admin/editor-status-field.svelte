<script lang="ts">
  /** Draft / published radios for the editor's "Status & visibilitas" popover. */
  let {
    isPublished,
    onStatus,
  }: { isPublished: boolean; onStatus: (published: boolean) => void } =
    $props();

  const uid = $props.id();

  const options = [
    {
      published: false,
      label: 'Draf',
      hint: 'Belum siap untuk dipublikasikan.',
    },
    {
      published: true,
      label: 'Telah terbit',
      hint: 'Dapat dilihat siapa saja.',
    },
  ];
</script>

<div class="d-flex flex-column gap-2" role="radiogroup" aria-label="Status">
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
