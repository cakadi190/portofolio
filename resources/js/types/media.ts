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
  url: string | null;
  is_image: boolean;
  created_at: string;
};

/** Which kinds of files a picker or field accepts. */
export type MediaAccept = 'image' | 'all';
