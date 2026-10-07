import { fireEvent, render, screen } from '@testing-library/svelte';
import { createRawSnippet } from 'svelte';
import { describe, expect, it, vi } from 'vitest';
import MultiCheck from '@/components/ui/multi-check.svelte';
import Select from '@/components/ui/select.svelte';
import Separator from '@/components/ui/separator.svelte';
import { Field } from '@/components/ui/field';

const text = (value: string) =>
  createRawSnippet(() => ({ render: () => `<span>${value}</span>` }));

describe('Field', () => {
  it('composes group, label, input and feedback', () => {
    render(Field.Group, {
      class: 'extra',
      children: createRawSnippet(() => ({
        render: () => '<div data-testid="slot">slot</div>',
      })),
    });

    expect(screen.getByTestId('slot').parentElement?.className).toContain('extra');
  });

  it('Label forwards attributes', () => {
    render(Field.Label, { for: 'x', children: text('Nama') });

    expect(screen.getByText('Nama').closest('label')?.getAttribute('for')).toBe('x');
  });

  it('Row lays out children', () => {
    render(Field.Row, { children: text('baris') });

    expect(screen.getByText('baris').parentElement?.className).toContain('justify-content-between');
  });

  it('Feedback renders only with a message', () => {
    const { unmount } = render(Field.Feedback, { message: 'Wajib' });
    expect(screen.getByText('Wajib').className).toContain('invalid-feedback');
    unmount();

    const { container } = render(Field.Feedback, {});
    expect(container.querySelector('.invalid-feedback')).toBeNull();
  });
});

describe('Field.Input', () => {
  it('renders a text input and flags invalid state', () => {
    render(Field.Input, { id: 'a', name: 'a', invalid: true, class: 'x' });

    const input = document.getElementById('a') as HTMLInputElement;
    expect(input.type).toBe('text');
    expect(input.classList).toContain('is-invalid');
    expect(input.classList).toContain('x');
  });

  it('updates its value', async () => {
    render(Field.Input, { id: 'a', value: 'awal' });
    const input = document.getElementById('a') as HTMLInputElement;

    await fireEvent.input(input, { target: { value: 'baru' } });

    expect(input.value).toBe('baru');
  });

  it('Check renders a labelled checkbox with variants', () => {
    render(Field.Input.Check, {
      id: 'c',
      name: 'c',
      inline: true,
      switchStyle: true,
      children: text('Ingat saya'),
    });

    const input = document.getElementById('c') as HTMLInputElement;
    expect(input.type).toBe('checkbox');
    expect(input.parentElement?.className).toContain('form-check-inline');
    expect(input.parentElement?.className).toContain('form-switch');
    expect(screen.getByLabelText('Ingat saya')).toBe(input);
  });

  it('Radio renders a labelled radio', () => {
    render(Field.Input.Radio, { id: 'r', name: 'r', value: '1', label: text('Satu') });

    expect(screen.getByLabelText('Satu')).toBe(document.getElementById('r'));
  });
});

describe('Field.Input.Password', () => {
  it('toggles visibility', async () => {
    render(Field.Input.Password, { id: 'p', name: 'password' });
    const input = document.getElementById('p') as HTMLInputElement;

    expect(input.type).toBe('password');

    await fireEvent.click(screen.getByLabelText('Show password'));
    expect(input.type).toBe('text');

    await fireEvent.click(screen.getByLabelText('Hide password'));
    expect(input.type).toBe('password');
  });

  it('shows a strength meter and a confirmation field when confirmed', async () => {
    render(Field.Input.Password, {
      id: 'p',
      name: 'password',
      confirmed: 'password_confirmation',
    });
    const password = document.getElementById('p') as HTMLInputElement;
    const confirmation = document.getElementById('password_confirmation') as HTMLInputElement;

    expect(document.querySelector('.password-meter')).toBeNull();

    await fireEvent.input(password, { target: { value: 'abc' } });
    expect(screen.getByText('Sangat lemah')).toBeTruthy();

    await fireEvent.input(password, { target: { value: 'Abcdefgh12!x' } });
    expect(screen.getByText('Sangat kuat')).toBeTruthy();

    await fireEvent.input(confirmation, { target: { value: 'beda' } });
    expect(document.querySelector('.form-confirmed')?.getAttribute('data-state')).toBe('mismatch');

    await fireEvent.input(confirmation, { target: { value: 'Abcdefgh12!x' } });
    expect(document.querySelector('.form-confirmed')?.getAttribute('data-state')).toBe('match');
  });

  it('hides the meter when disabled', async () => {
    render(Field.Input.Password, { id: 'p', confirmed: 'pc', meter: false });

    await fireEvent.input(document.getElementById('p')!, { target: { value: 'abc' } });

    expect(document.querySelector('.password-meter')).toBeNull();
  });
});

describe('Separator', () => {
  it('draws a rule on both sides by default', () => {
    const { container } = render(Separator, { children: text('Atau') });

    expect(container.querySelectorAll('hr')).toHaveLength(2);
    expect(screen.getByText('Atau')).toBeTruthy();
  });

  it.each([
    ['start', 1],
    ['end', 1],
  ] as const)('draws a single rule for position %s', (position, count) => {
    const { container } = render(Separator, { position, children: text('x') });

    expect(container.querySelectorAll('hr')).toHaveLength(count);
  });
});

describe('MultiCheck', () => {
  const options = [
    { value: 1, label: 'PHP' },
    { value: 2, label: 'Svelte' },
  ];

  it('posts checked values as name[]', () => {
    render(MultiCheck, { name: 'tags', options, selected: [2] });

    const boxes = document.querySelectorAll<HTMLInputElement>('input[name="tags[]"]');
    expect(boxes).toHaveLength(2);
    expect(boxes[0].checked).toBe(false);
    expect(boxes[1].checked).toBe(true);
  });

  it('filters by hiding rows, keeping them mounted', async () => {
    render(MultiCheck, { name: 'tags', options, selected: [1] });

    await fireEvent.input(screen.getByLabelText('Cari opsi'), { target: { value: 'sve' } });

    expect(document.querySelectorAll('input[name="tags[]"]')).toHaveLength(2);
    expect(document.querySelector<HTMLElement>('.form-check:nth-child(1)')?.hidden).toBe(true);
    expect(document.querySelector<HTMLElement>('.form-check:nth-child(2)')?.hidden).toBe(false);
  });

  it('reports no results', async () => {
    render(MultiCheck, { name: 'tags', options });

    await fireEvent.input(screen.getByLabelText('Cari opsi'), { target: { value: 'zzz' } });

    expect(screen.getByText('Tidak ada hasil.')).toBeTruthy();
  });

  it('shows an empty message without options and hides search', () => {
    render(MultiCheck, { name: 'tags', options: [] });

    expect(screen.getByText('Belum ada data.')).toBeTruthy();
    expect(screen.queryByLabelText('Cari opsi')).toBeNull();
  });

  it('can disable search', () => {
    render(MultiCheck, { name: 'tags', options, searchable: false });

    expect(screen.queryByLabelText('Cari opsi')).toBeNull();
  });
});

describe('Select', () => {
  const items = [
    { value: 'a', label: 'Opsi A' },
    { value: 'b', label: 'Opsi B' },
  ];

  it('submits the initial value through a hidden input', async () => {
    render(Select, { name: 'kind', items, value: 'b' });

    await vi.waitFor(() =>
      expect(document.querySelector<HTMLInputElement>('input[name="kind"]')?.value).toBe('b'),
    );
  });

  it('preselects the first option when required', async () => {
    render(Select, { name: 'kind', items, required: true });

    await vi.waitFor(() =>
      expect(document.querySelector<HTMLInputElement>('input[name="kind"]')?.value).toBe('a'),
    );
  });

  it('leaves the value empty when optional', () => {
    render(Select, { name: 'kind', items });

    expect(document.querySelector<HTMLInputElement>('input[name="kind"]')?.value ?? '').toBe('');
  });

  it('marks invalid state', () => {
    const { container } = render(Select, { name: 'kind', items, invalid: true });

    expect(container.querySelector('.neo-select.is-invalid')).not.toBeNull();
  });
});

