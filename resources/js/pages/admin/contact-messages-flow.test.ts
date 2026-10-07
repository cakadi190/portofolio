import { fireEvent, render, screen } from '@testing-library/svelte';
import { describe, expect, it, vi } from 'vitest';
import ContactMessages from '@/pages/admin/contact-messages/index.svelte';

const router = vi.hoisted(() => ({
  put: vi.fn(),
  get: vi.fn(),
  delete: vi.fn(),
}));

vi.mock('@inertiajs/svelte', async (importOriginal) => {
  const original = await importOriginal<typeof import('@inertiajs/svelte')>();

  return {
    ...original,
    router: Object.assign(Object.create(original.router), router),
  };
});

const message = (id: number, readAt: string | null) => ({
  id,
  name: `Pengirim ${id}`,
  email: `p${id}@mail.test`,
  reason: 'kerja',
  reason_label: 'Kerja sama',
  message: '<p>Halo, <strong>saya tertarik</strong></p>',
  read_at: readAt,
  created_at: '2024-05-17T00:00:00Z',
});

const messages = {
  data: [message(1, null), message(2, '2024-05-18T00:00:00Z')],
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 2,
  from: 1,
  to: 2,
  prev_page_url: null,
  next_page_url: null,
};

const filters = {
  search: '',
  sort: null,
  direction: 'asc' as const,
  per_page: 10,
};

describe('admin contact messages page (full flow)', () => {
  it('lists messages with read/unread badges and formatted dates', () => {
    render(ContactMessages, { messages, filters });

    expect(screen.getByText('Baru', { selector: '.badge' })).toBeTruthy();
    expect(screen.getByText('Dibaca', { selector: '.badge' })).toBeTruthy();
    expect(screen.getAllByText('17 Mei 2024')).toHaveLength(2);
    expect(screen.getByText('p1@mail.test')).toBeTruthy();
  });

  it('opens an unread message and marks it as read', async () => {
    render(ContactMessages, { messages, filters });

    await fireEvent.click(screen.getAllByRole('button', { name: 'Lihat' })[0]);

    await vi.waitFor(() =>
      expect(document.querySelector('.modal.show')).not.toBeNull(),
    );
    expect(screen.getByText('saya tertarik')).toBeTruthy();
    expect(
      screen
        .getByRole('link', { name: 'Balas via Email', hidden: true })
        .getAttribute('href'),
    ).toBe('mailto:p1@mail.test');
    expect(router.put).toHaveBeenCalledWith(
      expect.stringMatching(/\/admin\/contact-messages\/1$/),
      {},
      expect.objectContaining({ preserveScroll: true }),
    );
  });

  it('does not re-mark an already read message', async () => {
    render(ContactMessages, { messages, filters });

    await fireEvent.click(screen.getAllByRole('button', { name: 'Lihat' })[1]);

    expect(router.put).not.toHaveBeenCalled();
  });

  it('deletes a message after confirmation', async () => {
    render(ContactMessages, { messages, filters });

    await fireEvent.click(
      screen.getAllByRole('button', { name: /^Hapus$/ })[0],
    );
    await fireEvent.click(
      screen.getAllByText('Hapus', { selector: 'button.btn-danger' })[0],
    );

    expect(router.delete).toHaveBeenCalledWith(
      expect.stringMatching(/\/admin\/contact-messages\/1$/),
      expect.any(Object),
    );
  });
});
