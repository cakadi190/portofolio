export type LightboxMediaType = 'image' | 'pdf' | 'video' | 'embed';

const NATIVE_VIDEO_PATTERN =
  /\.(mp4|m4v|mkv|mov|webm|wmv|avi|3gp|ogv)($|[?#])/i;

const NATIVE_VIDEO_MIME: Record<string, string> = {
  mp4: 'video/mp4',
  m4v: 'video/mp4',
  mkv: 'video/x-matroska',
  mov: 'video/quicktime',
  webm: 'video/webm',
  wmv: 'video/x-ms-wmv',
  avi: 'video/x-msvideo',
  '3gp': 'video/3gpp',
  ogv: 'video/ogg',
};

/** Hosts that refuse to be embedded (DRM / X-Frame-Options), so the lightbox offers an external link instead. */
const NON_EMBEDDABLE_HOSTS =
  /(^|\.)(netflix\.com|disneyplus\.com|primevideo\.com|hbomax\.com|max\.com)$/i;

function parseUrl(url: string): URL | null {
  try {
    return new URL(url, window.location.origin);
  } catch {
    return null;
  }
}

export function isNativeVideoUrl(url: string): boolean {
  return NATIVE_VIDEO_PATTERN.test(url);
}

/** MIME type guessed from the file extension, so browsers get a hint for containers like mkv/mov. */
export function nativeVideoMime(url: string): string | undefined {
  const extension =
    parseUrl(url)?.pathname.split('.').pop()?.toLowerCase() ?? '';

  return NATIVE_VIDEO_MIME[extension];
}

export function isNonEmbeddableVideoUrl(url: string): boolean {
  const host = parseUrl(url)?.hostname ?? '';

  return NON_EMBEDDABLE_HOSTS.test(host);
}

/**
 * Convert a public watch URL (YouTube, Vimeo, Vidio, Dailymotion, ...) into its iframe embed URL.
 * Returns null when the URL is not a known video provider.
 */
export function toEmbedUrl(url: string): string | null {
  const parsed = parseUrl(url);

  if (!parsed || !/^https?:$/.test(parsed.protocol)) {
    return null;
  }

  const host = parsed.hostname.replace(/^(www|m)\./, '');
  const segments = parsed.pathname.split('/').filter(Boolean);

  if (host === 'youtu.be' && segments[0]) {
    return `https://www.youtube-nocookie.com/embed/${segments[0]}`;
  }

  if (host === 'youtube.com' || host === 'youtube-nocookie.com') {
    const id =
      parsed.searchParams.get('v') ??
      (['embed', 'shorts', 'live', 'v'].includes(segments[0])
        ? segments[1]
        : null);

    return id ? `https://www.youtube-nocookie.com/embed/${id}` : null;
  }

  if (host === 'vimeo.com' || host === 'player.vimeo.com') {
    const id = segments.find((segment) => /^\d+$/.test(segment));

    return id ? `https://player.vimeo.com/video/${id}` : null;
  }

  if (host === 'vidio.com') {
    if (segments[0] === 'embed' && segments[1]) {
      return `https://www.vidio.com/embed/${segments[1]}`;
    }

    if (segments[0] === 'watch' && segments[1]) {
      return `https://www.vidio.com/embed/${segments[1]}`;
    }

    return null;
  }

  if (host === 'dailymotion.com' || host === 'dai.ly') {
    const id =
      host === 'dai.ly' ? segments[0] : segments[segments.indexOf('video') + 1];

    return id ? `https://www.dailymotion.com/embed/video/${id}` : null;
  }

  return null;
}

/**
 * Decide how the lightbox renders an item. An explicit `type` wins; otherwise it is inferred from the URL:
 * .pdf → pdf, video file extension → video, known video provider → embed, anything else → image.
 */
export function detectMediaType(
  url: string,
  explicit?: LightboxMediaType,
): LightboxMediaType {
  if (explicit) {
    return explicit;
  }

  if (/\.pdf($|[?#])/i.test(url)) {
    return 'pdf';
  }

  if (isNativeVideoUrl(url)) {
    return 'video';
  }

  if (toEmbedUrl(url) || isNonEmbeddableVideoUrl(url)) {
    return 'embed';
  }

  return 'image';
}
