import { describe, expect, it, vi } from 'vitest';

const instance = vi.hoisted(() => ({ update: vi.fn(), destroy: vi.fn() }));
const ctor = vi.hoisted(() => vi.fn());

vi.mock('perfect-scrollbar', () => ({
  default: class {
    constructor(...args: unknown[]) {
      ctor(...args);
      return instance;
    }
  },
}));
vi.mock('perfect-scrollbar/css/perfect-scrollbar.css', () => ({}));

import { perfectScrollbar } from '@/lib/perfect-scrollbar';

describe('perfectScrollbar action', () => {
  it('creates an instance with defaults and cleans up on destroy', () => {
    const observe = vi.fn();
    const disconnect = vi.fn();
    vi.stubGlobal(
      'ResizeObserver',
      class {
        observe = observe;
        disconnect = disconnect;
      },
    );
    const node = document.createElement('div');
    node.appendChild(document.createElement('p'));

    const action = perfectScrollbar(node, { suppressScrollX: true });

    expect(ctor).toHaveBeenCalledWith(node, {
      wheelPropagation: true,
      suppressScrollX: true,
    });
    expect(observe).toHaveBeenCalledTimes(2);

    action.destroy();

    expect(disconnect).toHaveBeenCalled();
    expect(instance.destroy).toHaveBeenCalled();
  });
});
