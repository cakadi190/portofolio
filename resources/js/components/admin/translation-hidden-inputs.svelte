<script lang="ts">
  import { fieldName, type createTranslatable } from '@/lib/translatable.svelte';

  /**
   * Submits the languages that are not on screen: every translated field of the form is
   * posted as `field[locale]`, so switching tabs never loses the other language's text.
   */
  let {
    translatable,
    fields,
  }: {
    translatable: ReturnType<typeof createTranslatable>;
    fields: string[];
  } = $props();

  const others = $derived(
    Object.keys(translatable.values[fields[0]] ?? {}).filter((code) => code !== translatable.locale),
  );
</script>

{#each others as code (code)}
  {#each fields as field (field)}
    <input type="hidden" name={fieldName(field, code)} value={translatable.values[field][code]} />
  {/each}
{/each}
