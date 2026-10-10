import { router } from '@inertiajs/svelte';

type EventParams = Record<string, string | number | boolean | undefined>;

declare global {
  interface Window {
    gtag?: (...args: unknown[]) => void;
  }
}

/** Admin and auth screens are private; keep them out of visitor analytics. */
const PRIVATE_PATH =
  /^\/(admin|settings|login|register|forgot-password|reset-password|two-factor|user|confirm-password|email|\.well-known)(\/|$)/;

const DOWNLOAD_EXTENSION = /\.(pdf|docx?|xlsx?|pptx?|zip|rar|7z|csv|txt)$/i;

const UTM_KEYS = [
  ['utm_source', 'campaign_source'],
  ['utm_medium', 'campaign_medium'],
  ['utm_campaign', 'campaign_name'],
  ['utm_term', 'campaign_term'],
  ['utm_content', 'campaign_content'],
  ['utm_id', 'campaign_id'],
] as const;

const UTM_STORAGE_KEY = 'analytics:utm';

const SHARE_HOSTS: Record<string, string> = {
  'wa.me': 'whatsapp',
  'api.whatsapp.com': 'whatsapp',
  'www.facebook.com': 'facebook',
  'facebook.com': 'facebook',
  'twitter.com': 'x',
  'x.com': 'x',
  't.me': 'telegram',
  'www.linkedin.com': 'linkedin',
};

const SHARE_PATH = /\/(sharer|share|intent|send|shareArticle)/i;

function isTrackable(): boolean {
  return typeof window !== 'undefined' && typeof window.gtag === 'function';
}

/** Sends a custom GA4 event. No-op when the tag is not installed. */
export function trackEvent(name: string, params: EventParams = {}): void {
  if (!isTrackable() || PRIVATE_PATH.test(window.location.pathname)) {
    return;
  }

  window.gtag?.('event', name, params);
}

/**
 * Captures UTM parameters from the landing URL and keeps them for the rest of
 * the session (SPA navigation drops the query string), then hands them to GA4
 * as the official `campaign_*` parameters so attribution survives navigation.
 */
export function captureUtm(): Record<string, string> {
  const params = new URLSearchParams(window.location.search);
  const fresh: Record<string, string> = {};

  for (const [utmKey, campaignKey] of UTM_KEYS) {
    const value = params.get(utmKey);

    if (value) {
      fresh[campaignKey] = value;
    }
  }

  try {
    if (Object.keys(fresh).length > 0) {
      window.sessionStorage.setItem(UTM_STORAGE_KEY, JSON.stringify(fresh));

      return fresh;
    }

    return JSON.parse(window.sessionStorage.getItem(UTM_STORAGE_KEY) ?? '{}');
  } catch {
    return fresh;
  }
}

function applyUtm(): void {
  const campaign = captureUtm();

  if (isTrackable() && Object.keys(campaign).length > 0) {
    window.gtag?.('set', campaign);
  }
}

/** Reports a virtual page view for the current URL (SPA navigation). */
export function trackPageView(): void {
  trackEvent('page_view', {
    page_title: document.title,
    page_location: window.location.href,
    page_path: window.location.pathname + window.location.search,
  });
}

/**
 * Svelte action: `<button use:track={{ name: 'cta_click', params: { id: 'hero' } }}>`.
 * Pass a plain event name for events without parameters.
 */
export function track(
  node: HTMLElement,
  options: string | { name: string; params?: EventParams },
) {
  let current = options;

  const handler = () => {
    if (typeof current === 'string') {
      trackEvent(current);
    } else {
      trackEvent(current.name, current.params);
    }
  };

  node.addEventListener('click', handler);

  return {
    update(next: typeof options) {
      current = next;
    },
    destroy() {
      node.removeEventListener('click', handler);
    },
  };
}

function handleClick(event: MouseEvent): void {
  const target = event.target as Element | null;
  const element = target?.closest<HTMLElement>('a[href], [data-track]');

  if (!element) {
    return;
  }

  const customName = element.dataset.track;

  if (customName) {
    trackEvent(customName, { label: element.dataset.trackLabel });
  }

  if (!(element instanceof HTMLAnchorElement)) {
    return;
  }

  const href = element.getAttribute('href') ?? '';
  const label = element.textContent?.trim().slice(0, 100);

  if (href.startsWith('mailto:')) {
    trackEvent('contact_email', { link_url: href, link_text: label });
  } else if (href.startsWith('tel:')) {
    trackEvent('contact_phone', { link_url: href, link_text: label });
  } else if (
    element.host &&
    SHARE_HOSTS[element.host] &&
    (SHARE_PATH.test(element.pathname) || element.host === 'wa.me')
  ) {
    trackEvent('share', {
      method: SHARE_HOSTS[element.host],
      content_type: 'page',
      item_id: window.location.pathname,
    });
  } else if (element.host && element.host !== window.location.host) {
    trackEvent('click', {
      link_url: element.href,
      link_domain: element.host,
      link_text: label,
      outbound: true,
    });
  } else if (DOWNLOAD_EXTENSION.test(element.pathname)) {
    trackEvent('file_download', {
      file_name: element.pathname.split('/').pop(),
      link_url: element.href,
    });
  }
}

function handleSubmit(event: SubmitEvent): void {
  const form = event.target as HTMLFormElement | null;

  if (!form?.tagName || form.tagName !== 'FORM') {
    return;
  }

  trackEvent('form_submit', {
    form_id: form.id || undefined,
    form_action: new URL(
      form.action || window.location.href,
      window.location.href,
    ).pathname,
  });
}

type VisitDetail = {
  visit?: { method?: string; url?: URL };
  page?: { component?: string; url?: string; props?: Record<string, unknown> };
};

const SEARCH_PARAMS = ['search', 'q', 'query'];

/** GA4 `search` event when a visit carries a search query. */
function trackSearch(): void {
  const params = new URLSearchParams(window.location.search);

  for (const key of SEARCH_PARAMS) {
    const term = params.get(key);

    if (term) {
      trackEvent('search', { search_term: term });

      return;
    }
  }
}

/** Page-level events that depend on which Inertia page was rendered. */
function trackPageContext(page: VisitDetail['page']): void {
  const component = page?.component;

  if (!component) {
    return;
  }

  if (component === 'error') {
    trackEvent('page_error', {
      status: Number(page?.props?.status) || undefined,
      page_path: window.location.pathname,
    });
  } else if (component.endsWith('/show')) {
    trackEvent('view_item', {
      content_type: component.split('/')[0],
      item_id: window.location.pathname,
    });
  }
}

/** Successful POSTs become GA4 conversions: contact form, comments and ratings. */
function trackSuccessfulPost(detail: VisitDetail | undefined, method: string | undefined): void {
  if (method?.toLowerCase() !== 'post') {
    return;
  }

  const path = detail?.page?.url ?? window.location.pathname;

  if (path.startsWith('/contact') || path.startsWith('/hubungi')) {
    trackEvent('generate_lead', { form: 'contact' });
  } else if (path.startsWith('/blog') || path.startsWith('/artikel')) {
    trackEvent('comment_submit', { page_path: path });
  } else if (path.startsWith('/portfolio') || path.startsWith('/portofolio')) {
    trackEvent('rating_submit', { page_path: path });
  }
}

/** Reports uncaught errors using GA4's recommended `exception` event. */
function initExceptionTracking(): void {
  window.addEventListener('error', (event) => {
    trackEvent('exception', { description: event.message?.slice(0, 150), fatal: false });
  });

  window.addEventListener('unhandledrejection', (event) => {
    trackEvent('exception', {
      description: String(event.reason?.message ?? event.reason).slice(0, 150),
      fatal: false,
    });
  });
}

/** Scroll-depth milestones, reported once per page view. */
function initScrollDepth(): () => void {
  const milestones = [25, 50, 75, 100];
  let reported = new Set<number>();

  const onScroll = () => {
    const scrollable =
      document.documentElement.scrollHeight - window.innerHeight;

    if (scrollable <= 0) {
      return;
    }

    const percent = (window.scrollY / scrollable) * 100;

    for (const milestone of milestones) {
      if (percent >= milestone && !reported.has(milestone)) {
        reported.add(milestone);
        trackEvent('scroll_depth', { percent: milestone });
      }
    }
  };

  window.addEventListener('scroll', onScroll, { passive: true });
  router.on('navigate', () => {
    reported = new Set();
  });

  return () => window.removeEventListener('scroll', onScroll);
}

/**
 * Wires Google Analytics into the Inertia lifecycle: page views on every
 * visit, plus delegated click, form and scroll-depth interaction events.
 */
export function initializeAnalytics(): void {
  applyUtm();

  let lastMethod: string | undefined;

  router.on('start', (event?: CustomEvent<VisitDetail>) => {
    lastMethod = event?.detail?.visit?.method;
  });

  router.on('navigate', (event?: CustomEvent<VisitDetail>) => {
    applyUtm();
    trackPageView();
    trackSearch();
    trackPageContext(event?.detail?.page);
    trackSuccessfulPost(event?.detail, lastMethod);
    lastMethod = undefined;
  });

  initExceptionTracking();

  document.addEventListener('click', handleClick);
  document.addEventListener('submit', handleSubmit, true);
  initScrollDepth();
}
