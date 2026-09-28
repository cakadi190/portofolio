<script lang="ts">
  /** Checkbox-list multi-select for pivot relations, posted as `name[]`. */
  let {
    name,
    options,
    selected = [],
  }: {
    name: string;
    options: { value: number; label: string }[];
    selected?: number[];
  } = $props();
</script>

<div class="multi-check">
  {#if options.length === 0}
    <p class="text-muted small mb-0">Belum ada data.</p>
  {:else}
    {#each options as option (option.value)}
      <div class="form-check">
        <input
          type="checkbox"
          class="form-check-input"
          id={`${name}-${option.value}`}
          name={`${name}[]`}
          value={option.value}
          checked={selected.includes(option.value)}
        />
        <label class="form-check-label" for={`${name}-${option.value}`}>
          {option.label}
        </label>
      </div>
    {/each}
  {/if}
</div>

<style>
  .multi-check {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
    max-height: 12rem;
    overflow-y: auto;
    padding: 0.75rem;
    border: 1px solid var(--neo-border-color);
    border-radius: 0.5rem;
  }
</style>
