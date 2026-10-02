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
  router.on('navigate', () => trackPageView());

  document.addEventListener('click', handleClick);
  document.addEventListener('submit', handleSubmit, true);
  initScrollDepth();
}
