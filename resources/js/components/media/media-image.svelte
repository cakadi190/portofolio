<script lang="ts">
  import type { MediaItem } from '@/types/media';

  /**
   * Renders a library image with its responsive `srcset`, a layout-specific
   * `sizes`, and intrinsic dimensions. Media without variants render a plain
   * `src`, so older uploads keep working.
   */
  let {
    media,
    sizes,
    alt,
    loading = 'lazy',
    class: className = '',
  }: {
    media: Pick<MediaItem, 'url' | 'srcset' | 'width' | 'height' | 'name' | 'alt'>;
    /** Describe the real layout width, e.g. `(min-width: 768px) 14vw, 50vw`. */
    sizes: string;
    alt?: string;
    loading?: 'lazy' | 'eager';
    class?: string;
  } = $props();
</script>

<img
  src={media.url ?? undefined}
  srcset={media.srcset ?? undefined}
  sizes={media.srcset ? sizes : undefined}
  width={media.width ?? undefined}
  height={media.height ?? undefined}
  alt={alt ?? media.alt ?? media.name}
  {loading}
  decoding="async"
  class={className}
/>
