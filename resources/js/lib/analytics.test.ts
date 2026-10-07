import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const handlers = vi.hoisted(() => ({}) as Record<string, (() => void)[]>);

vi.mock('@inertiajs/svelte', () => ({
  router: {
    on: (name: string, cb: () => void) => (handlers[name] ??= []).push(cb),
  },
}));

import {
  initializeAnalytics,
  track,
  trackEvent,
  trackPageView,
} from '@/lib/analytics';

const gtag = vi.fn();

beforeEach(() => {
  window.gtag = gtag;
  window.history.pushState({}, '', '/blog');
});

afterEach(() => {
  delete window.gtag;
});

describe('trackEvent', () => {
  it('sends events through gtag', () => {
    trackEvent('cta', { id: 'x' });

    expect(gtag).toHaveBeenCalledWith('event', 'cta', { id: 'x' });
  });

  it('is a no-op without gtag', () => {
    delete window.gtag;

    expect(() => trackEvent('cta')).not.toThrow();
    expect(gtag).not.toHaveBeenCalled();
  });

  it.each(['/admin/posts', '/login', '/settings/profile'])(
    'skips private path %s',
    (path) => {
      window.history.pushState({}, '', path);

      trackEvent('cta');

      expect(gtag).not.toHaveBeenCalled();
    },
  );
});

describe('trackPageView', () => {
  it('reports the current location', () => {
    window.history.pushState({}, '', '/blog?page=2');

    trackPageView();

    expect(gtag).toHaveBeenCalledWith(
      'event',
      'page_view',
      expect.objectContaining({ page_path: '/blog?page=2' }),
    );
  });
});

describe('track action', () => {
  it('tracks clicks and follows option updates', () => {
    const node = document.createElement('button');
    const action = track(node, 'first');

    node.click();
    action.update('second');
    node.click();
    action.destroy();
    node.click();

    expect(gtag).toHaveBeenNthCalledWith(1, 'event', 'first', {});
    expect(gtag).toHaveBeenNthCalledWith(2, 'event', 'second', {});
    expect(gtag).toHaveBeenCalledTimes(2);
  });

  it('supports object options with params', () => {
    const node = document.createElement('button');
    track(node, { name: 'cta', params: { id: 'hero' } });

    node.click();

    expect(gtag).toHaveBeenCalledWith('event', 'cta', { id: 'hero' });
  });
});

describe('initializeAnalytics', () => {
  initializeAnalytics();

  const click = (html: string) => {
    document.body.innerHTML = html;
    (document.body.firstElementChild as HTMLElement).click();
  };

  it('reports page views on navigation', () => {
    handlers.navigate.forEach((handler) => handler());

    expect(gtag).toHaveBeenCalledWith('event', 'page_view', expect.any(Object));
  });

  it('classifies mailto, tel, outbound and download links', () => {
    click('<a href="mailto:a@b.c">mail</a>');
    click('<a href="tel:123">call</a>');
    click('<a href="https://other.test/x">out</a>');
    click('<a href="/files/cv.pdf">cv</a>');

    const names = gtag.mock.calls.map((call) => call[1]);
    expect(names).toEqual([
      'contact_email',
      'contact_phone',
      'click',
      'file_download',
    ]);
  });

  it('honours data-track on any element', () => {
    click('<button data-track="theme_toggle" data-track-label="x">t</button>');

    expect(gtag).toHaveBeenCalledWith('event', 'theme_toggle', { label: 'x' });
  });

  it('ignores internal links without special meaning', () => {
    click('<a href="/blog">blog</a>');

    expect(gtag).not.toHaveBeenCalled();
  });

  it('reports form submissions', () => {
    document.body.innerHTML = '<form id="f" action="/kontak"></form>';

    document
      .getElementById('f')
      ?.dispatchEvent(new Event('submit', { bubbles: true }));

    expect(gtag).toHaveBeenCalledWith('event', 'form_submit', {
      form_id: 'f',
      form_action: '/kontak',
    });
  });

  it('reports scroll depth milestones once', () => {
    Object.defineProperty(document.documentElement, 'scrollHeight', {
      value: 2000,
      configurable: true,
    });
    Object.defineProperty(window, 'innerHeight', {
      value: 1000,
      configurable: true,
    });
    Object.defineProperty(window, 'scrollY', {
      value: 600,
      configurable: true,
    });

    window.dispatchEvent(new Event('scroll'));
    window.dispatchEvent(new Event('scroll'));

    const percents = gtag.mock.calls.map((call) => call[2].percent);
    expect(percents).toEqual([25, 50]);

    Object.defineProperty(window, 'scrollY', { value: 0, configurable: true });
  });
});
