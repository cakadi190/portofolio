import { render, screen } from '@testing-library/svelte';
import { describe, expect, it } from 'vitest';
import EmptyState from '@/components/empty-state.svelte';

describe('EmptyState', () => {
  it('renders the title and text', () => {
    render(EmptyState, { title: 'Kosong', text: 'Belum ada data.' });

    expect(screen.getByRole('heading', { name: 'Kosong' })).toBeTruthy();
    expect(screen.getByText('Belum ada data.')).toBeTruthy();
  });
});
