import { fireEvent, render, screen } from '@testing-library/svelte';
import { page } from '@inertiajs/svelte';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import LanguageSuggestion from '@/components/language-suggestion.svelte';

const post = vi.hoisted(() => vi.fn());

vi.mock('@inertiajs/svelte', async (importOriginal) => {
  const original = await importOriginal<typeof import('@inertiajs/svelte')>();

  return {
    ...original,
    router: Object.assign(Object.create(original.router), { post }),
  };
});

const suggestion = {
  locale: 'en',
  url: '/en/blog',
  messages: {
    prompt: 'You can read this site in English.',
    accept: 'View in English',
    dismiss: 'Stay here',
  },
};

beforeEach(() => {
  post.mockReset();
  page.props = { ...page.props, languageSuggestion: suggestion, locale: 'id' };
});

describe('LanguageSuggestion', () => {
  it('offers the suggested language in that language', async () => {
    render(LanguageSuggestion);

    expect(
      await screen.findByText('You can read this site in English.'),
    ).toBeTruthy();
    expect(
      screen.getByRole('button', { name: 'View in English' }),
    ).toBeTruthy();
  });

  it('renders nothing without a suggestion', () => {
    page.props = { ...page.props, languageSuggestion: null };
    render(LanguageSuggestion);

    expect(screen.queryByRole('region')).toBeNull();
  });

  it('remembers the choice and goes to the translated page on accept', async () => {
    render(LanguageSuggestion);

    await fireEvent.click(
      await screen.findByRole('button', { name: 'View in English' }),
    );

    expect(post).toHaveBeenCalledWith(
      '/locale',
      { locale: 'en' },
      expect.objectContaining({ onSuccess: expect.any(Function) }),
    );
  });

  it('remembers the current language and hides the bar on dismiss', async () => {
    render(LanguageSuggestion);

    await fireEvent.click(
      await screen.findByRole('button', { name: 'Stay here' }),
    );

    expect(post).toHaveBeenCalledWith(
      '/locale',
      { locale: 'id' },
      expect.anything(),
    );
    expect(screen.queryByRole('region')).toBeNull();
  });
});
