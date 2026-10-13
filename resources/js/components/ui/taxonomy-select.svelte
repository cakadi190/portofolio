<script lang="ts">
  import MultiCheck from '@/components/ui/multi-check.svelte';
  import { createTaxonomy, type TaxonomyItem } from '@/lib/taxonomy';

  /**
   * Tag/category checklist where a missing name can be created on the spot
   * (saved to the database right away, then ticked for the current record).
   */
  let {
    name,
    items,
    selected = [],
    createUrl,
  }: {
    name: string;
    items: TaxonomyItem[];
    selected?: number[];
    createUrl: string;
  } = $props();

  // svelte-ignore state_referenced_locally
  let list = $state<TaxonomyItem[]>([...items]);
  // svelte-ignore state_referenced_locally
  let picked = $state<number[]>([...selected]);

  async function create(label: string): Promise<void> {
    const item = await createTaxonomy(createUrl, label);

    if (!list.some((existing) => existing.id === item.id)) {
      list = [...list, item].sort((a, b) => a.name.localeCompare(b.name));
    }

    picked = [...picked, item.id];
  }
</script>

<MultiCheck
  {name}
  options={list.map((item) => ({ value: item.id, label: item.name }))}
  selected={picked}
  onCreate={create}
/>
