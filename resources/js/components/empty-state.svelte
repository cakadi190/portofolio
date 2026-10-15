<script lang="ts">
  import '../../css/content.scss';
  let {
    title,
    text,
    type = 'info',
    wrapped = true,
  }: {
    title: string;
    text: string;
    /** Callout tone, same set as the editor's callout block. */
    type?: 'info' | 'success' | 'warning' | 'danger' | 'dark';
    /** Wrap in a centered column; pass false when the parent already sizes it. */
    wrapped?: boolean;
  } = $props();
</script>

<!-- Reuses the WYSIWYG callout styles (scoped to .wysiwyg-content-wrapper). -->
{#snippet callout()}
  <div class="wysiwyg-content-wrapper">
    <div class={`wp-block-callout is-${type}`} role="status">
      <p><strong>{title}</strong></p>
      <p>{text}</p>
    </div>
  </div>
{/snippet}

{#if wrapped}
  <div class="col-md-8 col-lg-6 mx-auto py-5">
    {@render callout()}
  </div>
{:else}
  {@render callout()}
{/if}
