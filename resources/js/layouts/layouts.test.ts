import { render, screen } from '@testing-library/svelte';
import { createRawSnippet } from 'svelte';
import { describe, expect, it } from 'vitest';
import AppLayout from '@/layouts/app-layout.svelte';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout.svelte';
import AuthCardLayout from '@/layouts/auth/auth-card-layout.svelte';
import AuthSimpleLayout from '@/layouts/auth/auth-simple-layout.svelte';
import AuthSplitLayout from '@/layouts/auth/auth-split-layout.svelte';
import AuthLayout from '@/layouts/auth-layout.svelte';

const content = createRawSnippet(() => ({ render: () => '<p>Isi</p>' }));

describe.each([
  ['AuthSplitLayout', AuthSplitLayout],
  ['AuthSimpleLayout', AuthSimpleLayout],
  ['AuthCardLayout', AuthCardLayout],
  ['AuthLayout (default split)', AuthLayout],
])('%s', (_name, Layout) => {
  it('renders the heading, description, body and theme toggle', () => {
    render(Layout as never, {
      title: 'Masuk',
      description: 'Silakan masuk',
      children: content,
    });

    expect(
      screen.getByRole('heading', { level: 1, name: 'Masuk' }),
    ).toBeTruthy();
    expect(screen.getByText('Silakan masuk')).toBeTruthy();
    expect(screen.getByText('Isi')).toBeTruthy();
    expect(screen.getAllByLabelText(/Ubah ke mode/).length).toBeGreaterThan(0);
    expect(screen.getByText(/Hak Cipta 2026/)).toBeTruthy();
  });
});

describe('AppLayout / AppSidebarLayout (public site shell)', () => {
  it('wraps content with navbar, footer and back-to-top', () => {
    render(AppSidebarLayout, { children: content });

    expect(document.getElementById('site-shell')).not.toBeNull();
    expect(screen.getByText('Isi')).toBeTruthy();
    expect(document.querySelector('footer.site-footer')).not.toBeNull();
    expect(screen.getByLabelText('Kembali ke atas')).toBeTruthy();
    expect(document.querySelector('nav.navbar')).not.toBeNull();
  });

  it('AppLayout renders the shell around its children', () => {
    render(AppLayout, { children: content });

    expect(screen.getByText('Isi')).toBeTruthy();
    expect(document.getElementById('site-shell')).not.toBeNull();
  });
});
