import { fireEvent, render, screen } from '@testing-library/svelte';
import { afterEach, describe, expect, it, vi } from 'vitest';
import AdminCalendar from '@/layouts/admin/admin-calendar.svelte';

afterEach(() => {
  vi.useRealTimers();
  vi.unstubAllGlobals();
});

describe('AdminCalendar', () => {
  it('shows today and lists the events of the selected day', async () => {
    vi.useFakeTimers({ toFake: ['Date'] });
    vi.setSystemTime(new Date(2026, 9, 10, 6, 0, 0));
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      json: async () => ({
        events: [
          {
            id: 'speaking-1',
            type: 'speaking',
            title: 'Talk Oktober',
            subtitle: 'Pembicara · Komunitas',
            starts_at: '2026-10-10 19:30',
            ends_at: null,
          },
        ],
      }),
    });
    vi.stubGlobal('fetch', fetchMock);

    render(AdminCalendar, { active: true });

    expect(screen.getAllByText('Sabtu, 10 Oktober 2026').length).toBeGreaterThan(0);
    expect(await screen.findByText('Talk Oktober')).toBeTruthy();
    expect(fetchMock.mock.calls[0][0]).toContain('month=2026-10');

    await fireEvent.click(screen.getByLabelText('Bulan berikutnya'));
    await vi.waitFor(() =>
      expect(fetchMock.mock.calls.at(-1)?.[0]).toContain('month=2026-11'),
    );
  });

  it('does not fetch while the modal is closed', () => {
    const fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);

    render(AdminCalendar, { active: false });

    expect(fetchMock).not.toHaveBeenCalled();
  });
});
