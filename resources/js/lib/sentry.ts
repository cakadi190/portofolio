import * as Sentry from '@sentry/svelte';
import { router } from '@inertiajs/svelte';

type SentryConfig = {
  dsn: string;
  environment: string;
  release: string | null;
  tracesSampleRate: number;
  profilesSampleRate: number;
  requestId?: string;
};

type Monitoring = { actor: string; userRef: string | null };

const EMAIL = /[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/gi;
const SENSITIVE_KEY =
  /pass(word|wd)?|secret|token|api[_-]?key|authorization|cookie|credential|session|otp|email|phone|whatsapp|name|message/i;

/** Mask emails and drop sensitive query values from a URL or free text. */
export function scrubText(value: string): string {
  const [path, query] = value.split('?', 2);

  if (query === undefined) {
    return value.replace(EMAIL, '[email]');
  }

  const params = new URLSearchParams(query.split('#')[0]);
  for (const key of [...params.keys()]) {
    params.set(
      key,
      SENSITIVE_KEY.test(key)
        ? '[Filtered]'
        : params.get(key)!.replace(EMAIL, '[email]'),
    );
  }

  return `${path.replace(EMAIL, '[email]')}?${params.toString()}`;
}

function scrubDeep(value: unknown, depth = 0): unknown {
  if (typeof value === 'string') {
    return scrubText(value);
  }
  if (depth > 6 || value === null || typeof value !== 'object') {
    return value;
  }
  if (Array.isArray(value)) {
    return value.map((item) => scrubDeep(item, depth + 1));
  }

  return Object.fromEntries(
    Object.entries(value).map(([key, item]) => [
      key,
      SENSITIVE_KEY.test(key) ? '[Filtered]' : scrubDeep(item, depth + 1),
    ]),
  );
}

/** `beforeSend` / `beforeSendTransaction`: remove personal data from every event. */
export function scrubEvent<T extends Sentry.Event>(event: T): T {
  if (event.request) {
    delete event.request.cookies;
    delete event.request.data;
    if (event.request.url) {
      event.request.url = scrubText(event.request.url);
    }
    delete event.request.query_string;
    event.request.headers = Object.fromEntries(
      Object.entries(event.request.headers ?? {}).filter(([key]) =>
        ['user-agent', 'accept-language', 'referer'].includes(
          key.toLowerCase(),
        ),
      ),
    );
    if (event.request.headers.Referer) {
      event.request.headers.Referer = scrubText(event.request.headers.Referer);
    }
  }

  if (event.user) {
    event.user = { id: event.user.id, segment: event.user.segment };
  }

  event.exception?.values?.forEach((exception) => {
    if (exception.value) {
      exception.value = scrubText(exception.value);
    }
  });
  if (event.message) {
    event.message = scrubText(event.message);
  }
  if (event.transaction) {
    event.transaction = scrubText(event.transaction);
  }
  event.extra = scrubDeep(event.extra) as typeof event.extra;
  event.contexts = scrubDeep(event.contexts) as typeof event.contexts;
  event.breadcrumbs = event.breadcrumbs?.map(
    (crumb) => scrubDeep(crumb) as Sentry.Breadcrumb,
  );

  return event;
}

function readConfig(): SentryConfig | null {
  try {
    const raw = document
      .querySelector('meta[name="sentry-config"]')
      ?.getAttribute('content');
    return raw ? (JSON.parse(raw) as SentryConfig) : null;
  } catch {
    return null;
  }
}

/** Respect browser privacy signals: no tracing or profiling when asked not to be tracked. */
function privacySignalActive(): boolean {
  const nav = navigator as Navigator & { globalPrivacyControl?: boolean };

  return nav.globalPrivacyControl === true || navigator.doNotTrack === '1';
}

/**
 * Start Sentry in the browser. Configuration (DSN, sampling) comes from the
 * system settings through the `sentry-config` meta tag; nothing happens while
 * monitoring is disabled. Personal data is never collected.
 */
export function initializeSentry(): void {
  const config = readConfig();

  if (!config) {
    return;
  }

  const limited = privacySignalActive();
  const profiling = !limited && config.profilesSampleRate > 0;

  Sentry.init({
    dsn: config.dsn,
    environment: config.environment,
    release: config.release ?? undefined,
    // Collect no personal data: no IP/user info, cookies, request bodies or query strings.
    dataCollection: {
      userInfo: false,
      cookies: false,
      httpBodies: [],
      urlQueryParams: false,
      httpHeaders: { allow: ['user-agent', 'accept-language'] },
    },
    integrations: [
      Sentry.browserTracingIntegration(),
      ...(profiling ? [Sentry.browserProfilingIntegration()] : []),
    ],
    // Keep transaction-level scrubbing (beforeSendTransaction) working.
    traceLifecycle: 'static',
    tracesSampleRate: limited ? 0 : config.tracesSampleRate,
    profileSessionSampleRate: profiling ? config.profilesSampleRate : 0,
    // Only same-origin requests carry trace headers; third parties never see them.
    tracePropagationTargets: [window.location.origin, /^\/(?!\/)/],
    ignoreErrors: [
      'ResizeObserver loop limit exceeded',
      'ResizeObserver loop completed with undelivered notifications.',
      /^Non-Error promise rejection captured/,
      'Network request failed',
      /^HttpNetworkError: Network error/,
      /^Network error/,
      /\[Cloudflare Turnstile\]/,
      /Unable to preload (CSS|dependency)/,
      /reading 'getPage'/,
    ],
    denyUrls: [
      /^chrome-extension:\/\//i,
      /^moz-extension:\/\//i,
      /^safari-extension:\/\//i,
    ],
    beforeSend: (event) => scrubEvent(event),
    beforeSendTransaction: (event) => scrubEvent(event),
    beforeBreadcrumb: (crumb) => scrubDeep(crumb) as Sentry.Breadcrumb,
  });

  if (config.requestId) {
    Sentry.setTag('server_request_id', config.requestId);
  }

  router.on('navigate', (event) => {
    const page = event.detail.page;
    const monitoring = (page.props as { monitoring?: Monitoring }).monitoring;

    Sentry.setTag('inertia_component', page.component);
    Sentry.setTag('page_path', scrubText(page.url).split('?')[0]);
    Sentry.setTag('actor', monitoring?.actor ?? 'guest');
    Sentry.setUser(
      monitoring?.userRef
        ? { id: monitoring.userRef, segment: monitoring.actor }
        : null,
    );
    Sentry.addBreadcrumb({
      category: 'navigation',
      message: page.component,
      level: 'info',
    });
  });
}
