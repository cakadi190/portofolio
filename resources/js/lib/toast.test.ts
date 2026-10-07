import { describe, expect, it, vi } from 'vitest';

const fire = vi.hoisted(() => vi.fn());

vi.mock('sweetalert2', () => ({
  default: {
    mixin: () => ({ fire }),
    stopTimer: vi.fn(),
    resumeTimer: vi.fn(),
  },
}));

import { toast } from '@/lib/toast';

describe('toast', () => {
  it.each(['success', 'error', 'warning', 'info'] as const)(
    'fires a %s toast',
    (type) => {
      toast[type]('Pesan');

      expect(fire).toHaveBeenCalledWith({ icon: type, title: 'Pesan' });
    },
  );

  it('cancelled() defaults to an info message', () => {
    toast.cancelled();

    expect(fire).toHaveBeenCalledWith({
      icon: 'info',
      title: 'Aksi dibatalkan.',
    });
  });
});
