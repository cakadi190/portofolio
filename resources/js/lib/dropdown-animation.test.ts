import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

type Module = typeof import('@/lib/dropdown-animation');

const registered: [string, EventListenerOrEventListenerObject][] = [];
const nativeAdd = document.addEventListener.bind(document);

async function load(reducedMotion = false): Promise<Module> {
  vi.resetModules();
  vi.spyOn(document, 'addEventListener').mockImplementation(
    (name: string, listener: EventListenerOrEventListenerObject) => {
      registered.push([name, listener]);
      nativeAdd(name, listener);
    },
  );
  vi.mocked(window.matchMedia).mockImplementation(
    (query: string) =>
      ({
        matches: reducedMotion,
        media: query,
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
      }) as unknown as MediaQueryList,
  );

  return import('@/lib/dropdown-animation');
}

function dropdown(wrapperClass = 'dropdown', menuClass = 'dropdown-menu') {
  const wrapper = document.createElement('div');
  wrapper.className = wrapperClass;
  const toggle = document.createElement('button');
  const menu = document.createElement('ul');
  menu.className = menuClass;
  menu.style.position = 'absolute';
  wrapper.append(toggle, menu);
  document.body.appendChild(wrapper);

  return { toggle, menu };
}

const fire = (target: Element, name: string) =>
  target.dispatchEvent(new Event(`${name}.bs.dropdown`, { bubbles: true }));

async function open(toggle: HTMLElement, menu: HTMLElement) {
  fire(toggle, 'show');
  menu.classList.add('show');
  fire(toggle, 'shown');
  await Promise.resolve();
}

beforeEach(() => {
  vi.useFakeTimers();
});

afterEach(() => {
  vi.useRealTimers();
  registered
    .splice(0)
    .forEach(([name, listener]) =>
      document.removeEventListener(name, listener),
    );
});

describe('initDropdownAnimation', () => {
  it('is idempotent', async () => {
    const { initDropdownAnimation } = await load();
    const spy = vi.spyOn(document, 'addEventListener');

    initDropdownAnimation();
    initDropdownAnimation();

    expect(
      spy.mock.calls.filter(([name]) => String(name).endsWith('.bs.dropdown')),
    ).toHaveLength(4);
  });

  it('plays the enter animation, resolving the placement', async () => {
    const { initDropdownAnimation } = await load();
    initDropdownAnimation();
    const { toggle, menu } = dropdown();

    await open(toggle, menu);

    expect(menu.classList).toContain('dropdown-anim-enter');
    expect(menu.getAttribute('data-popper-placement')).toBe('bottom-start');

    menu.dispatchEvent(new Event('animationend'));

    expect(menu.classList).not.toContain('dropdown-anim-enter');
    expect(menu.hasAttribute('data-popper-placement')).toBe(false);
  });

  it.each([
    ['dropup', 'top-start'],
    ['dropend', 'right-start'],
    ['dropstart', 'left-start'],
    ['dropdown-center', 'bottom'],
    ['dropup-center', 'top'],
  ])('derives the %s placement', async (wrapper, placement) => {
    const { initDropdownAnimation } = await load();
    initDropdownAnimation();
    const { toggle, menu } = dropdown(wrapper);

    await open(toggle, menu);

    expect(menu.getAttribute('data-popper-placement')).toBe(placement);
  });

  it("keeps Popper's own placement when present", async () => {
    const { initDropdownAnimation } = await load();
    initDropdownAnimation();
    const { toggle, menu } = dropdown();
    menu.setAttribute('data-popper-placement', 'top-end');

    await open(toggle, menu);
    menu.dispatchEvent(new Event('animationend'));

    expect(menu.getAttribute('data-popper-placement')).toBe('top-end');
  });

  it('holds the menu in place for the leave animation', async () => {
    const { initDropdownAnimation } = await load();
    initDropdownAnimation();
    const { toggle, menu } = dropdown();
    menu.setAttribute('data-bs-popper', 'static');
    menu.style.top = '10px';
    menu.setAttribute('data-popper-placement', 'bottom-end');

    fire(toggle, 'hide');
    menu.classList.remove('show');
    menu.removeAttribute('data-bs-popper');
    menu.style.removeProperty('top');
    fire(toggle, 'hidden');

    expect(menu.classList).toContain('dropdown-anim-leave');
    expect(menu.getAttribute('data-bs-popper')).toBe('static');
    expect(menu.style.top).toBe('10px');
    expect(menu.getAttribute('data-popper-placement')).toBe('bottom-end');

    menu.dispatchEvent(new Event('animationend'));

    expect(menu.classList).not.toContain('dropdown-anim-leave');
    expect(menu.hasAttribute('data-bs-popper')).toBe(false);
    expect(menu.style.top).toBe('');
  });

  it('skips leave animation for in-flow (collapsed navbar) menus', async () => {
    const { initDropdownAnimation } = await load();
    initDropdownAnimation();
    const { toggle, menu } = dropdown();
    menu.style.position = 'static';

    fire(toggle, 'hide');
    fire(toggle, 'hidden');

    expect(menu.classList).not.toContain('dropdown-anim-leave');
  });

  it('settles via a safety timeout if animationend never fires', async () => {
    const { initDropdownAnimation } = await load();
    initDropdownAnimation();
    const { toggle, menu } = dropdown();

    await open(toggle, menu);
    vi.advanceTimersByTime(1100);

    expect(menu.classList).not.toContain('dropdown-anim-enter');
  });

  it('does nothing when the user prefers reduced motion', async () => {
    const { initDropdownAnimation } = await load(true);
    initDropdownAnimation();
    const { toggle, menu } = dropdown();

    await open(toggle, menu);

    expect(menu.classList).not.toContain('dropdown-anim-enter');
  });

  it('ignores toggles without a menu', async () => {
    const { initDropdownAnimation } = await load();
    initDropdownAnimation();
    const lonely = document.createElement('button');
    document.body.appendChild(lonely);

    expect(() => {
      fire(lonely, 'show');
      fire(lonely, 'shown');
      fire(lonely, 'hide');
      fire(lonely, 'hidden');
    }).not.toThrow();
  });
});
