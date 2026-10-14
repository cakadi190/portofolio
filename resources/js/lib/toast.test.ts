import { describe, expect, it, vi } from 'vitest';

const lib = vi.hoisted(() =>
  Object.assign(vi.fn(), { success: vi.fn(), error: vi.fn() }),
);

vi.mock('svelte-french-toast', () => ({ default: lib }));

import { toast } from '@/lib/toast';

describe('toast', () => {
  it.each(['success', 'error'] as const)('fires a %s toast', (type) => {
    toast[type]('Pesan');

    expect(lib[type]).toHaveBeenCalledWith('Pesan', { duration: 4000 });
  });

  it.each(['warning', 'info'] as const)('fires a %s toast with an icon', (type) => {
    toast[type]('Pesan');

    expect(lib).toHaveBeenCalledWith(
      'Pesan',
      expect.objectContaining({ icon: expect.any(String), duration: 4000 }),
    );
  });

  it('cancelled() defaults to an info message', () => {
    toast.cancelled();

    expect(lib).toHaveBeenCalledWith(
      'Aksi dibatalkan.',
      expect.objectContaining({ duration: 4000 }),
    );
  });
});
