import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createTranslatable } from '@/lib/translatable.svelte';

const mocks = vi.hoisted(() => ({
  post: vi.fn(),
  error: vi.fn(),
  success: vi.fn(),
}));

vi.mock('@/lib/post-json', () => ({ postJson: mocks.post }));
vi.mock('@/lib/toast', () => ({
  toast: { error: mocks.error, success: mocks.success },
}));

const initial = {
  title: { id: 'Judul', en: '' },
  content: { id: '<p>Isi</p>', en: '' },
};
const formats = { title: 'text', content: 'html' } as const;

beforeEach(() => {
  vi.clearAllMocks();
  vi.stubGlobal(
    'confirm',
    vi.fn(() => true),
  );
  window.confirm = vi.fn(() => true);
});

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

  it('drafts each field of the active language from Indonesian', async () => {
    mocks.post.mockImplementation(
      async (_url: string, body: { text: string }) => ({
        text: `EN:${body.text}`,
      }),
    );
    const form = createTranslatable(initial, formats);

    form.locale = 'en';
    const key = form.contentKey;
    await form.translateFromDefault();

    expect(mocks.post).toHaveBeenCalledTimes(2);
    expect(mocks.post).toHaveBeenCalledWith('/admin/translate', {
      text: '<p>Isi</p>',
      from: 'id',
      to: 'en',
      format: 'html',
    });
    expect(form.values.title.en).toBe('EN:Judul');
    expect(form.values.content.en).toBe('EN:<p>Isi</p>');
    expect(form.contentKey).not.toBe(key);
    expect(mocks.success).toHaveBeenCalled();
    expect(form.translating).toBe(false);
  });

  it('asks before replacing text that is already translated', async () => {
    window.confirm = vi.fn(() => false);
    const form = createTranslatable(
      { ...initial, title: { id: 'Judul', en: 'Title' } },
      formats,
    );

    form.locale = 'en';
    await form.translateFromDefault();

    expect(window.confirm).toHaveBeenCalled();
    expect(mocks.post).not.toHaveBeenCalled();
    expect(form.values.title.en).toBe('Title');
  });

  it('reports a failure and leaves the text alone', async () => {
    mocks.post.mockRejectedValue(
      new Error('Layanan terjemahan menolak permintaan ini.'),
    );
    const form = createTranslatable(initial, formats);

    form.locale = 'en';
    await form.translateFromDefault();

    expect(mocks.error).toHaveBeenCalledWith(
      'Layanan terjemahan menolak permintaan ini.',
    );
    expect(form.values.title.en).toBe('');
    expect(form.translating).toBe(false);
  });

  it('does nothing on the default language or without source text', async () => {
    const form = createTranslatable(initial, formats);

    await form.translateFromDefault();

    expect(mocks.post).not.toHaveBeenCalled();

    const empty = createTranslatable(
      { title: { id: '' }, content: { id: '' } },
      formats,
    );
    empty.locale = 'en';
    await empty.translateFromDefault();

    expect(mocks.post).not.toHaveBeenCalled();
    expect(mocks.error).toHaveBeenCalled();
  });
});
