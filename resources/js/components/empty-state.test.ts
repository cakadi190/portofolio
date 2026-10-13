import { render, screen } from '@testing-library/svelte';
import { describe, expect, it } from 'vitest';
import EmptyState from '@/components/empty-state.svelte';

describe('EmptyState', () => {
  it('renders the title and text', () => {
    render(EmptyState, { title: 'Kosong', text: 'Belum ada data.' });

    const callout = screen.getByRole('status');

    expect(callout.classList.contains('wp-block-callout')).toBe(true);
    expect(callout.classList.contains('is-info')).toBe(true);
    expect(screen.getByText('Kosong')).toBeTruthy();
    expect(screen.getByText('Belum ada data.')).toBeTruthy();
  });

  it('applies the requested callout tone', () => {
    render(EmptyState, { title: 'Hasil', text: 'Tidak cocok.', type: 'warning' });

    expect(screen.getByRole('status').classList.contains('is-warning')).toBe(true);
  });
});
