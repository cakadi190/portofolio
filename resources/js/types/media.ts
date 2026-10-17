/** One generated responsive size of an image. */
export type MediaVariant = {
  width: number;
  height: number;
  path: string;
  size: number;
  format: string;
};

/** Media library item as serialised by `App\Models\Media`. */
export type MediaItem = {
  id: number;
  path: string;
  name: string;
  alt: string | null;
  mime_type: string;
  size: number;
  width: number | null;
  height: number | null;
  /** Stored responsive variants; null/empty for images that predate them. */
  variants?: MediaVariant[] | null;
  /** Ready-made `srcset`, null when the image has no smaller variants. */
  srcset?: string | null;
  url: string | null;
  is_image: boolean;
  created_at: string;
};

/** Which kinds of files a picker or field accepts. */
export type MediaAccept = 'image' | 'all';

/** How many items a media picker lets the user choose. */
export type MediaSelectionMode = 'single' | 'multiple';
