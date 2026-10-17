import { fireEvent, render, screen } from '@testing-library/svelte';
import { describe, expect, it, vi } from 'vitest';
import ImageBlockToolbar from '@/components/ui/image-block-toolbar.svelte';

function setup(attrs: Record<string, unknown> = {}) {
  const host = document.createElement('div');
  const figure = document.createElement('figure');
  figure.innerHTML = '<img alt="">';
  host.append(figure);
  document.body.append(host);

  const handlers = {
    onpatch: vi.fn(),
    onreplace: vi.fn(),
    onmove: vi.fn(),
    onduplicate: vi.fn(),
    onremove: vi.fn(),
    onaddparagraph: vi.fn(),
  };

  render(ImageBlockToolbar, {
    editor: { view: { nodeDOM: () => figure } } as never,
    host,
    block: { pos: 0, attrs: { align: 'center', src: '/a.webp', ...attrs } },
    revision: 0,
    canMove: () => true,
    ...handlers,
  });

  return handlers;
}

describe('ImageBlockToolbar', () => {
  it('exposes the Gutenberg-style controls', () => {
    setup();

    expect(screen.getByRole('toolbar', { name: 'Alat gambar' })).toBeTruthy();
    for (const name of [
      'Pindah ke atas',
      'Pindah ke bawah',
      'Perataan',
      'Tautan',
      'Teks alternatif',
      'Opsi',
    ]) {
      expect(screen.getByLabelText(name)).toBeTruthy();
    }
    expect(screen.getByText('Gantikan')).toBeTruthy();
  });

  it('moves, replaces and aligns the image', async () => {
    const handlers = setup();

    await fireEvent.click(screen.getByLabelText('Pindah ke atas'));
    await fireEvent.click(screen.getByText('Gantikan'));
    await fireEvent.click(screen.getByLabelText('Perataan'));
    await fireEvent.click(
      screen.getByRole('menuitemradio', { name: 'Rata kiri' }),
    );

    expect(handlers.onmove).toHaveBeenCalledWith(-1);
    expect(handlers.onreplace).toHaveBeenCalled();
    expect(handlers.onpatch).toHaveBeenCalledWith({ align: 'left', width: 50 });
  });

  it('sets a safe link and can link to the image file', async () => {
    const handlers = setup();

    await fireEvent.click(screen.getByLabelText('Tautan'));
    await fireEvent.input(screen.getByLabelText('Tempel URL'), {
      target: { value: 'javascript:alert(1)' },
    });
    await fireEvent.click(screen.getByText('Terapkan'));
    expect(handlers.onpatch).toHaveBeenLastCalledWith({
      href: '',
      newTab: false,
    });

    await fireEvent.click(screen.getByLabelText('Tautan'));
    await fireEvent.click(screen.getByText('Tautan ke berkas gambar'));
    expect(handlers.onpatch).toHaveBeenLastCalledWith({
      href: '/a.webp',
      newTab: false,
    });
  });

  it('removes an existing link', async () => {
    const handlers = setup({ href: 'https://x.id' });

    await fireEvent.click(screen.getByLabelText('Tautan'));
    await fireEvent.click(screen.getByText('Hapus tautan'));

    expect(handlers.onpatch).toHaveBeenLastCalledWith({
      href: '',
      newTab: false,
    });
  });

  it('edits alt text and offers duplicate, paragraphs and delete', async () => {
    const handlers = setup();

    await fireEvent.click(screen.getByLabelText('Teks alternatif'));
    await fireEvent.input(screen.getByRole('textbox'), {
      target: { value: 'Kucing' },
    });
    expect(handlers.onpatch).toHaveBeenLastCalledWith({ alt: 'Kucing' });

    await fireEvent.click(screen.getByLabelText('Opsi'));
    await fireEvent.click(screen.getByRole('menuitem', { name: 'Gandakan' }));
    await fireEvent.click(screen.getByLabelText('Opsi'));
    await fireEvent.click(
      screen.getByRole('menuitem', { name: 'Tambah paragraf setelahnya' }),
    );
    await fireEvent.click(screen.getByLabelText('Opsi'));
    await fireEvent.click(screen.getByRole('menuitem', { name: 'Hapus' }));

    expect(handlers.onduplicate).toHaveBeenCalled();
    expect(handlers.onaddparagraph).toHaveBeenCalledWith('after');
    expect(handlers.onremove).toHaveBeenCalled();
  });

  it('resizes with the keyboard in 5% steps and never below 10%', async () => {
    const handlers = setup({ width: 10 });
    const slider = screen.getAllByRole('slider')[0];

    await fireEvent.keyDown(slider, { key: 'ArrowRight' });
    await fireEvent.keyDown(slider, { key: 'ArrowLeft' });

    expect(handlers.onpatch).toHaveBeenNthCalledWith(1, { width: 15 });
    expect(handlers.onpatch).toHaveBeenNthCalledWith(2, { width: 10 });
  });
});
