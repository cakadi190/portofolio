/**
 * Edge-caches image responses from /storage/* and /images/* so repeat
 * requests are served from Cloudflare's cache instead of hitting the
 * Laravel origin. Images saved via ImageService use random/slugged
 * filenames, so caching them as immutable is safe.
 */

const CACHE_TTL_SECONDS = 60 * 60 * 24 * 30; // 30 days
const CACHEABLE_EXTENSION = /\.(webp|avif|jpe?g|png|gif|svg)$/i;

export default {
  async fetch(request: Request, _env: unknown, ctx: ExecutionContext): Promise<Response> {
    const url = new URL(request.url);

    if (request.method !== 'GET' || !CACHEABLE_EXTENSION.test(url.pathname)) {
      return fetch(request);
    }

    const cache = (caches as unknown as { default: Cache }).default;
    const cacheKey = new Request(url.toString(), request);

    const cached = await cache.match(cacheKey);
    if (cached) {
      return cached;
    }

    const originResponse = await fetch(request);

    if (!originResponse.ok) {
      return originResponse;
    }

    const headers = new Headers(originResponse.headers);
    headers.set('Cache-Control', `public, max-age=${CACHE_TTL_SECONDS}, immutable`);

    const response = new Response(originResponse.body, {
      status: originResponse.status,
      statusText: originResponse.statusText,
      headers,
    });

    ctx.waitUntil(cache.put(cacheKey, response.clone()));

    return response;
  },
};
