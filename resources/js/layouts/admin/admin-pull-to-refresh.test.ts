import { render } from '@testing-library/svelte';
import { tick } from 'svelte';
import { afterEach, describe, expect, it, vi } from 'vitest';
import AdminPullToRefresh from '@/layouts/admin/admin-pull-to-refresh.svelte';

function mount() {
  const region = document.createElement('main');
  region.setAttribute('data-main-scroll', '');
  document.body.appendChild(region);
  const { container } = render(AdminPullToRefresh, { scroller: region });

  return { region, container };
}

function touch(region: HTMLElement, type: string, y: number, x = 0) {
  const event = new Event(type, { bubbles: true, cancelable: true });
  Object.assign(event, {
    touches: type === 'touchend' ? [] : [{ clientX: x, clientY: y }],
  });
  region.dispatchEvent(event);

  return event;
}

afterEach(() => {
  vi.useRealTimers();
  document.body.classList.remove('modal-open');
});

describe('AdminPullToRefresh', () => {
  it('renders an indicator', () => {
    const { container } = mount();

    expect(
      document.body.querySelector('.main-refresh') ??
        container.querySelector('.main-refresh'),
    ).not.toBeNull();
  });

  it('shows a pulling state while dragging down from the top', async () => {
    mount();
    const region = document.querySelector<HTMLElement>('[data-main-scroll]')!;

    touch(region, 'touchstart', 0);
    const move = touch(region, 'touchmove', 60);

    await tick();
    expect(move.defaultPrevented).toBe(true);
    expect(document.querySelector('.main-refresh--pulling')).not.toBeNull();
  });

  it('ignores horizontal swipes', () => {
    mount();
    const region = document.querySelector<HTMLElement>('[data-main-scroll]')!;

    touch(region, 'touchstart', 0, 0);
    const move = touch(region, 'touchmove', 20, 100);

    expect(move.defaultPrevented).toBe(false);
    expect(document.querySelector('.main-refresh--pulling')).toBeNull();
  });

  it('does not engage while a modal is open', () => {
    document.body.classList.add('modal-open');
    mount();
    const region = document.querySelector<HTMLElement>('[data-main-scroll]')!;

    touch(region, 'touchstart', 0);
    const move = touch(region, 'touchmove', 200);

    expect(move.defaultPrevented).toBe(false);
  });

  it('resets when released before reaching the threshold', () => {
    mount();
    const region = document.querySelector<HTMLElement>('[data-main-scroll]')!;

    touch(region, 'touchstart', 0);
    touch(region, 'touchmove', 30);
    touch(region, 'touchend', 30);

    expect(document.querySelector('.main-refresh--pulling')).toBeNull();
    expect(document.querySelector('.main-refresh--refreshing')).toBeNull();
  });

  it('reloads the page after a full pull', async () => {
    vi.useFakeTimers();
    const reload = vi.fn();
    vi.stubGlobal('location', { href: window.location.href, reload });
    mount();
    const region = document.querySelector<HTMLElement>('[data-main-scroll]')!;

    touch(region, 'touchstart', 0);
    touch(region, 'touchmove', 600);
    touch(region, 'touchend', 600);
    await tick();

    expect(document.querySelector('.main-refresh--refreshing')).not.toBeNull();
    vi.advanceTimersByTime(200);
    expect(reload).toHaveBeenCalledOnce();
  });
});
