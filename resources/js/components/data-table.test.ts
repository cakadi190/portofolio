import { fireEvent, render, screen } from '@testing-library/svelte';
import { createRawSnippet } from 'svelte';
import { afterEach, describe, expect, it, vi } from 'vitest';
import DataTable from '@/components/admin/data-table.svelte';

const get = vi.hoisted(() => vi.fn());

vi.mock('@inertiajs/svelte', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@inertiajs/svelte')>()),
  router: { get },
}));

const row = createRawSnippet<[{ id: number; name: string }]>((item) => ({
  render: () => `<tr><td>${item().name}</td></tr>`,
}));

const columns = [
  { label: 'Nama', key: 'name', sortable: true },
  { label: 'Aksi' },
];

const paginated = (overrides = {}) => ({
  data: [
    { id: 1, name: 'Satu' },
    { id: 2, name: 'Dua' },
  ],
  current_page: 1,
  last_page: 1,
  from: 1,
  to: 2,
  total: 2,
  ...overrides,
});

const filters = (overrides = {}) => ({
  search: '',
  sort: null,
  direction: 'asc',
  per_page: 10,
  ...overrides,
});

const mount = (props: Record<string, unknown> = {}) =>
  render(DataTable as never, {
    data: paginated(),
    filters: filters(),
    url: '/admin/things',
    columns,
    row,
    ...props,
  });

afterEach(() => {
  vi.useRealTimers();
});

describe('DataTable', () => {
  it('renders rows and the summary', () => {
    mount();

    expect(screen.getByText('Satu')).toBeTruthy();
    expect(screen.getByText('Dua')).toBeTruthy();
    expect(screen.getByText('Menampilkan 1 sampai 2 dari 2 data')).toBeTruthy();
  });

  it('labels cells with their column header for the mobile layout', () => {
    mount();

    expect(screen.getByText('Satu').closest('td')?.dataset.label).toBe('Nama');
  });

  it('shows the empty text, or a no-match message when filtered', () => {
    const { unmount } = mount({ data: paginated({ data: [], total: 0 }) });
    expect(screen.getByText('Data akan muncul di sini.')).toBeTruthy();
    expect(screen.getByText('Tidak ada data')).toBeTruthy();
    unmount();

    mount({
      data: paginated({ data: [], total: 0 }),
      filters: filters({ search: 'xyz' }),
    });
    expect(
      screen.getByText('Tidak ada data yang cocok dengan "xyz".'),
    ).toBeTruthy();
  });

  it('debounces search and sends it to the server', async () => {
    vi.useFakeTimers();
    mount();

    await fireEvent.input(screen.getByLabelText('Cari'), {
      target: { value: '  halo ' },
    });
    expect(get).not.toHaveBeenCalled();

    vi.advanceTimersByTime(400);

    expect(get).toHaveBeenCalledWith(
      '/admin/things',
      { search: 'halo' },
      expect.objectContaining({ preserveState: true, replace: true }),
    );
  });

  it('clears the search immediately', async () => {
    mount({ filters: filters({ search: 'abc' }) });

    await fireEvent.click(screen.getByLabelText('Hapus pencarian'));

    expect(get).toHaveBeenCalledWith('/admin/things', {}, expect.any(Object));
  });

  it('cycles sorting asc → desc → off', async () => {
    const { unmount } = mount();
    await fireEvent.click(screen.getByRole('button', { name: /Nama/ }));
    expect(get).toHaveBeenLastCalledWith(
      '/admin/things',
      { sort: 'name', direction: 'asc' },
      expect.any(Object),
    );
    unmount();

    const second = mount({
      filters: filters({ sort: 'name', direction: 'asc' }),
    });
    await fireEvent.click(screen.getByRole('button', { name: /Nama/ }));
    expect(get).toHaveBeenLastCalledWith(
      '/admin/things',
      { sort: 'name', direction: 'desc' },
      expect.any(Object),
    );
    second.unmount();

    mount({ filters: filters({ sort: 'name', direction: 'desc' }) });
    await fireEvent.click(screen.getByRole('button', { name: /Nama/ }));
    expect(get).toHaveBeenLastCalledWith(
      '/admin/things',
      {},
      expect.any(Object),
    );
  });

  it('exposes aria-sort on the active column', () => {
    mount({ filters: filters({ sort: 'name', direction: 'desc' }) });

    expect(
      screen
        .getByRole('columnheader', { name: /Nama/ })
        .getAttribute('aria-sort'),
    ).toBe('descending');
  });

  it('changes page size', async () => {
    mount();

    await fireEvent.change(screen.getByLabelText('Tampilkan'), {
      target: { value: '25' },
    });

    expect(get).toHaveBeenCalledWith(
      '/admin/things',
      { per_page: 25 },
      expect.any(Object),
    );
  });

  it('paginates with a gap window and disabled edges', async () => {
    mount({ data: paginated({ current_page: 5, last_page: 10, total: 100 }) });

    expect(screen.getAllByText('…')).toHaveLength(2);
    await fireEvent.click(screen.getByRole('button', { name: 'Berikutnya' }));
    expect(get).toHaveBeenLastCalledWith(
      '/admin/things',
      { page: 6 },
      expect.any(Object),
    );

    await fireEvent.click(screen.getByRole('button', { name: '10' }));
    expect(get).toHaveBeenLastCalledWith(
      '/admin/things',
      { page: 10 },
      expect.any(Object),
    );
  });

  it('disables previous on the first page', () => {
    mount({ data: paginated({ last_page: 3 }) });

    expect(
      (screen.getByRole('button', { name: 'Sebelumnya' }) as HTMLButtonElement)
        .disabled,
    ).toBe(true);
  });

  it('renders the grid variant', () => {
    const grid = createRawSnippet<[{ id: number; name: string }]>((item) => ({
      render: () => `<div>${item().name}</div>`,
    }));
    mount({ variant: 'grid', row: grid });

    expect(document.querySelector('table')).toBeNull();
    expect(screen.getByText('Satu')).toBeTruthy();
  });
});
