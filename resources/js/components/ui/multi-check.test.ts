import { fireEvent, render, screen } from '@testing-library/svelte';
import { describe, expect, it, vi } from 'vitest';
import MultiCheck from '@/components/ui/multi-check.svelte';
import TaxonomySelect from '@/components/ui/taxonomy-select.svelte';

vi.mock('@/lib/taxonomy', () => ({
  createTaxonomy: vi.fn(async (_url: string, name: string) => ({
    id: 99,
    name,
  })),
}));

describe('MultiCheck', () => {
  it('offers to create a missing option and ignores an exact match', async () => {
    const onCreate = vi.fn(async () => {});

    render(MultiCheck, {
      name: 'tags',
      options: [{ value: 1, label: 'Laravel' }],
      onCreate,
    });

    await fireEvent.input(screen.getByLabelText('Cari opsi'), {
      target: { value: 'laravel' },
    });
    expect(screen.queryByText(/Tambah/)).toBeNull();

    await fireEvent.input(screen.getByLabelText('Cari opsi'), {
      target: { value: 'Svelte' },
    });
    await fireEvent.click(screen.getByText(/Tambah “Svelte”/));

    expect(onCreate).toHaveBeenCalledWith('Svelte');
  });
});

describe('TaxonomySelect', () => {
  it('adds a created item to the list, ticked and submitted with the form', async () => {
    render(TaxonomySelect, {
      name: 'tags',
      items: [{ id: 1, name: 'Laravel' }],
      selected: [],
      createUrl: '/admin/tags/quick',
    });

    await fireEvent.input(screen.getByLabelText('Cari opsi'), {
      target: { value: 'Baru' },
    });
    await fireEvent.keyDown(screen.getByLabelText('Cari opsi'), {
      key: 'Enter',
    });

    const created = await screen.findByLabelText('Baru');

    expect((created as HTMLInputElement).checked).toBe(true);
    expect((created as HTMLInputElement).value).toBe('99');
  });
});
