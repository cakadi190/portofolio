import { describe, expect, it } from 'vitest';
import { createTranslatable } from '@/lib/translatable.svelte';

const initial = {
  title: { id: 'Judul', en: '' },
  content: { id: '<p>Isi</p>', en: '' },
};
const formats = { title: 'text', content: 'html' } as const;

describe('createTranslatable', () => {
  it('starts on Indonesian with every language present', () => {
    const form = createTranslatable(initial, formats);

    expect(form.locale).toBe('id');
    expect(form.isDefault).toBe(true);
    expect(form.values.title).toEqual({ id: 'Judul', en: '' });
    expect(form.isTranslated('id')).toBe(true);
    expect(form.isTranslated('en')).toBe(false);
  });

  it('counts a language as translated only when it has visible text', () => {
    const form = createTranslatable(
      { title: { id: 'a', en: '<p> </p>' }, content: {} },
      formats,
    );

    expect(form.isTranslated('en')).toBe(false);

    form.values.title.en = 'Title';

    expect(form.isTranslated('en')).toBe(true);
  });
});
