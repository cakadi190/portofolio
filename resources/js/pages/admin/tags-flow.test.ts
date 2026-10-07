import { cleanup, fireEvent, render, screen } from '@testing-library/svelte';
import { afterEach, describe, expect, it, vi } from 'vitest';
import Tags from '@/pages/admin/tags/index.svelte';

const router = vi.hoisted(() => ({ delete: vi.fn(), get: vi.fn() }));

vi.mock('@inertiajs/svelte', async (importOriginal) => {
  const original = await importOriginal<typeof import('@inertiajs/svelte')>();

  return {
    ...original,
    router: Object.assign(Object.create(original.router), router),
  };
});

const tags = {
  data: [
    { id: 1, name: 'php' },
    { id: 2, name: 'svelte' },
  ],
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

describe('admin tags page (full flow)', () => {
  // Bootstrap's modal fade leaves a fallback timer running. If it fires after
  // the jsdom environment is torn down it dispatches a Node `Event` and fails
  // the whole run, so let pending transitions finish while jsdom is alive.
  afterEach(async () => {
    cleanup();
    await new Promise((resolve) => setTimeout(resolve, 400));
  });

  it('lists tags with edit and delete actions', () => {
    render(Tags, { tags, filters });

    expect(screen.getByText('php')).toBeTruthy();
    expect(screen.getByText('svelte')).toBeTruthy();
    expect(screen.getAllByRole('button', { name: 'Ubah' })).toHaveLength(2);
  });

  it('opens the create modal from the header button', async () => {
    render(Tags, { tags, filters });

    await fireEvent.click(screen.getByRole('button', { name: /Tambah/ }));

    await vi.waitFor(() =>
      expect(document.querySelector('.modal.show')).not.toBeNull(),
    );
    expect(screen.getAllByText('Tambah Tag').length).toBeGreaterThan(0);
    expect(document.querySelector('input#create-name')).not.toBeNull();
  });

  it('opens the edit modal prefilled', async () => {
    render(Tags, { tags, filters });

    await fireEvent.click(screen.getAllByRole('button', { name: 'Ubah' })[1]);

    await vi.waitFor(() =>
      expect(
        document.querySelector<HTMLInputElement>('input#edit-name')?.value,
      ).toBe('svelte'),
    );
  });

  it('deletes a tag after confirmation', async () => {
    render(Tags, { tags, filters });

    await fireEvent.click(
      screen.getAllByRole('button', { name: /^Hapus$/ })[0],
    );
    await fireEvent.click(
      screen.getAllByText('Hapus', { selector: 'button.btn-danger' })[0],
    );

    expect(router.delete).toHaveBeenCalledWith(
      expect.stringMatching(/\/admin\/tags\/1$/),
      expect.any(Object),
    );
  });
});
