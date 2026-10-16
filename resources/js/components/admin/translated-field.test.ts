import { fireEvent, render, screen } from '@testing-library/svelte';
import { page } from '@inertiajs/svelte';
import { describe, expect, it } from 'vitest';
import TranslatedField from '@/components/admin/translated-field.svelte';

function renderField(props: Record<string, unknown> = {}) {
  page.props = {
    ...page.props,
    locales: [
      { code: 'id', label: 'Bahasa Indonesia', flag: 'fi-id' },
      { code: 'en', label: 'English', flag: 'fi-us' },
    ],
    canTranslate: false,
  };

  return render(TranslatedField, {
    field: 'title',
    label: 'Judul',
    id: 'item-title',
    required: true,
    ...props,
  });
}

const input = (name: string) =>
  document.querySelector<HTMLInputElement>(`[name="${name}"]`);

describe('TranslatedField', () => {
  it('renders one input per language, only the Indonesian one required', () => {
    renderField({ values: { id: 'Juara 1', en: '1st Place' } });

    expect(input('title')?.value).toBe('Juara 1');
    expect(input('title')?.required).toBe(true);
    expect(input('title_en')?.value).toBe('1st Place');
    expect(input('title_en')?.required).toBe(false);
    expect(input('title_en')?.closest('div[hidden]')).not.toBeNull();
  });

  it('shows the other language when its tab is chosen, keeping both submitted', async () => {
    renderField({ values: { id: 'Juara 1', en: '' } });

    await fireEvent.click(screen.getByRole('tab', { name: /EN/ }));

    expect(input('title_en')?.closest('div[hidden]')).toBeNull();
    expect(input('title')?.closest('div[hidden]')).not.toBeNull();
    expect(input('title')?.value).toBe('Juara 1');
  });

  it('supports multiline fields and starts empty without values', () => {
    renderField({ multiline: true, required: false });

    expect(document.querySelector('textarea[name="title"]')).not.toBeNull();
    expect(
      document.querySelector<HTMLTextAreaElement>('textarea[name="title_en"]')
        ?.value,
    ).toBe('');
  });

  it('flags a language with no text', () => {
    renderField({ values: { id: 'x', en: '' } });

    expect(screen.getByRole('tab', { name: /EN/ }).textContent).toContain(
      'Belum diterjemahkan',
    );
    expect(screen.getByRole('tab', { name: /ID/ }).textContent).not.toContain(
      'Belum diterjemahkan',
    );
  });
});
