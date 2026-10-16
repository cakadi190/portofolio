import { execFileSync } from 'node:child_process';
import { page } from '@inertiajs/svelte';
import { cleanup } from '@testing-library/svelte';
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
 * Components read UI strings from the shared `translations` Inertia prop. Load the
 * Indonesian `lang/id/common.php` once so tests see what the server would share.
 */
function loadIndonesianTranslations(): Record<string, string> {
  try {
    return JSON.parse(
      execFileSync(
        'php',
        ['-r', 'echo json_encode(require "lang/id/common.php");'],
        {
          encoding: 'utf8',
        },
      ),
    ) as Record<string, string>;
  } catch {
    return {};
  }
}

const indonesianTranslations = loadIndonesianTranslations();

/**
 * happy-dom lacks several browser APIs the app relies on. Minimal stand-ins keep
 * components mountable; individual tests override them when behavior matters.
 */
function installBrowserStubs(): void {
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
