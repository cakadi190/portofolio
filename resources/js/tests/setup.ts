import { page } from '@inertiajs/svelte';
import { cleanup } from '@testing-library/svelte';
import { indonesianTranslations } from './translations';
import { afterEach, beforeEach, vi } from 'vitest';

// Components load Bootstrap lazily; transforming its ES sources on first use can
// outlast `vi.waitFor`'s default 1s, so pay that cost before any test starts.
await import('@/lib/bootstrap');

vi.mock(
  '../../../node_modules/@inertiajs/svelte/dist/components/InfiniteScroll.svelte',
  async () => ({
    default: (await import('./stubs/infinite-scroll-stub.svelte')).default,
  }),
);

/**
 * happy-dom lacks several browser APIs the app relies on. Minimal stand-ins keep
 * components mountable; individual tests override them when behavior matters.
 */
function installBrowserStubs(): void {
  // Under Bun the runtime's own `Event` stays global instead of happy-dom's, so
  // Bootstrap's `element.dispatchEvent(new Event(...))` is rejected by happy-dom.
  vi.stubGlobal('Event', window.Event);
  vi.stubGlobal('CustomEvent', window.CustomEvent);

  // No test may reach the network (icon API, CDN scripts); happy-dom would abort the
  // pending request at teardown and print an AbortError. Tests stub their own responses.
  vi.stubGlobal(
    'fetch',
    vi.fn(async () => new Response(null, { status: 404 })),
  );

  window.matchMedia = vi.fn().mockImplementation((query: string) => ({
    matches: false,
    media: query,
    onchange: null,
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    addListener: vi.fn(),
    removeListener: vi.fn(),
    dispatchEvent: vi.fn(),
  }));

  class NoopObserver {
    observe = vi.fn();
    unobserve = vi.fn();
    disconnect = vi.fn();
    takeRecords = vi.fn(() => []);
  }

  vi.stubGlobal('ResizeObserver', NoopObserver);
  vi.stubGlobal('IntersectionObserver', NoopObserver);
  window.scrollTo = vi.fn() as unknown as typeof window.scrollTo;
  Element.prototype.scrollIntoView = vi.fn();

  // ProseMirror measures ranges while scrolling the selection into view.
  Range.prototype.getClientRects = () =>
    ({
      length: 0,
      item: () => null,
      [Symbol.iterator]: [][Symbol.iterator],
    }) as unknown as DOMRectList;
  Range.prototype.getBoundingClientRect = () => new DOMRect();

  // Svelte transitions use the Web Animations API; finish them immediately.
  Element.prototype.animate = function animate() {
    const animation = {
      currentTime: 0,
      onfinish: null as (() => void) | null,
      cancel: vi.fn(),
      finish: vi.fn(),
      pause: vi.fn(),
      play: vi.fn(),
      reverse: vi.fn(),
      finished: Promise.resolve(),
    };
    queueMicrotask(() => animation.onfinish?.());

    return animation as unknown as Animation;
  };
}

// Some modules touch these APIs while being imported, so install them once up
// front as well as before every test (restoreMocks resets the spies).
installBrowserStubs();

beforeEach(() => {
  installBrowserStubs();
  page.props = { ...page.props, translations: indonesianTranslations };
});

afterEach(() => {
  cleanup();
  document.body.innerHTML = '';
});
