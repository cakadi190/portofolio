<script lang="ts">
  import { Field } from '@/components/ui/field';

  /** Large borderless title field shown at the top of the editor canvas. */
  let {
    name,
    value = $bindable(''),
    placeholder = 'Tambahkan judul',
    ariaLabel = 'Judul',
    required = true,
    error,
  }: {
    name: string;
    value?: string;
    placeholder?: string;
    ariaLabel?: string;
    required?: boolean;
    error?: string;
  } = $props();
</script>

<textarea
  id={`editor-${name}`}
  {name}
  class="gb-title"
  class:is-invalid={!!error}
  aria-label={ariaLabel}
  {placeholder}
  rows="1"
  {required}
  bind:value
  onkeydown={(event) => {
    if (event.key === 'Enter') event.preventDefault();
  }}></textarea>
<Field.Feedback message={error} />
