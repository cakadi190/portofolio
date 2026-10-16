<script lang="ts">
  import RichTextEditor from '@/components/ui/rich-text-editor.svelte';
  import { createTranslatable } from '@/lib/translatable.svelte';

  const translatable = createTranslatable(
    { content: { id: '<p>Halo dunia</p>', en: '<p>Hello world</p>' } },
    { content: 'html' },
  );
</script>

<button type="button" onclick={() => (translatable.locale = 'en')}>to-en</button>
<button type="button" onclick={() => (translatable.locale = 'id')}>to-id</button>
<output data-testid="stored-id">{translatable.values.content.id}</output>
<output data-testid="stored-en">{translatable.values.content.en}</output>

<RichTextEditor
  name="content"
  value={translatable.values.content[translatable.locale]}
  contentKey={translatable.contentKey}
  onchange={(html) => (translatable.values.content[translatable.locale] = html)}
/>
