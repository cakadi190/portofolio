import { fireEvent, render, screen } from '@testing-library/svelte';
import { describe, expect, it, vi } from 'vitest';
import ManagePasskeys from '@/components/manage-passkeys.svelte';
import PasskeyLogin from '@/components/passkey-login.svelte';

const router = vi.hoisted(() => ({
  patch: vi.fn(),
  delete: vi.fn(),
  reload: vi.fn(),
  visit: vi.fn(),
}));
const register = vi.hoisted(() => ({
  isSupported: true,
  isLoading: false,
  error: null as string | null,
  register: vi.fn(),
}));
const verify = vi.hoisted(() => ({
  isLoading: false,
  error: null as string | null,
  verify: vi.fn(),
}));
const callbacks = vi.hoisted(
  () => ({}) as Record<string, (arg?: unknown) => void>,
);

vi.mock('@inertiajs/svelte', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@inertiajs/svelte')>()),
  router,
}));
vi.mock('@laravel/passkeys/svelte', () => ({
  usePasskeyRegister: (options: { onSuccess: () => void }) => {
    callbacks.register = options.onSuccess;
    return register;
  },
  usePasskeyVerify: (options: { onSuccess: (arg: unknown) => void }) => {
    callbacks.verify = options.onSuccess as never;
    return verify;
  },
}));

const passkeys = [
  {
    id: 1,
    name: 'MacBook',
    authenticator: null,
    created_at_diff: '1 hari lalu',
    last_used_at_diff: '2 jam lalu',
  },
  {
    id: 2,
    name: 'iPhone',
    authenticator: null,
    created_at_diff: '3 hari lalu',
    last_used_at_diff: null,
  },
];

describe('ManagePasskeys', () => {
  it('renders nothing when management is disabled', () => {
    render(ManagePasskeys, { canManagePasskeys: false, passkeys });

    expect(screen.queryByText('Passkey')).toBeNull();
  });

  it('lists passkeys with usage info', () => {
    render(ManagePasskeys, { canManagePasskeys: true, passkeys });

    expect(screen.getByText('MacBook')).toBeTruthy();
    expect(screen.getByText(/Terakhir digunakan 2 jam lalu/)).toBeTruthy();
    expect(screen.getAllByText(/Ditambahkan/)).toHaveLength(2);
  });

  it('shows an empty state', () => {
    render(ManagePasskeys, { canManagePasskeys: true, passkeys: [] });

    expect(screen.getByText('Belum ada passkey')).toBeTruthy();
  });

  it('warns when passkeys are unsupported', () => {
    register.isSupported = false;
    render(ManagePasskeys, { canManagePasskeys: true });
    register.isSupported = true;

    expect(
      screen.getByText('Passkey tidak didukung oleh browser ini.'),
    ).toBeTruthy();
  });

  it('registers a new passkey by name', async () => {
    render(ManagePasskeys, { canManagePasskeys: true, passkeys });

    await fireEvent.click(
      screen.getByRole('button', { name: 'Tambah passkey' }),
    );
    const submit = screen.getByRole('button', {
      name: 'Daftarkan passkey',
    }) as HTMLButtonElement;
    expect(submit.disabled).toBe(true);

    await fireEvent.input(screen.getByLabelText('Nama passkey'), {
      target: { value: ' Laptop ' },
    });
    await fireEvent.click(submit);

    expect(register.register).toHaveBeenCalledWith('Laptop');
  });

  it('cancels registration', async () => {
    render(ManagePasskeys, { canManagePasskeys: true, passkeys });

    await fireEvent.click(
      screen.getByRole('button', { name: 'Tambah passkey' }),
    );
    await fireEvent.click(screen.getByRole('button', { name: 'Batal' }));

    expect(screen.getByRole('button', { name: 'Tambah passkey' })).toBeTruthy();
  });

  it('reloads the list after a successful registration', () => {
    render(ManagePasskeys, { canManagePasskeys: true, passkeys });

    callbacks.register();

    expect(router.reload).toHaveBeenCalledWith({ only: ['passkeys'] });
  });

  it('renames a passkey through the router', async () => {
    render(ManagePasskeys, { canManagePasskeys: true, passkeys });

    await fireEvent.click(screen.getAllByLabelText('Ubah nama passkey')[0]);
    const input = document.getElementById(
      'passkey-rename-1',
    ) as HTMLInputElement;
    await fireEvent.input(input, { target: { value: 'Kantor' } });
    await fireEvent.submit(document.getElementById('passkey-rename-form-1')!);

    expect(router.patch).toHaveBeenCalledWith(
      expect.stringContaining('/1'),
      { name: 'Kantor' },
      expect.any(Object),
    );
  });

  it('cancels renaming', async () => {
    render(ManagePasskeys, { canManagePasskeys: true, passkeys });

    await fireEvent.click(screen.getAllByLabelText('Ubah nama passkey')[0]);
    await fireEvent.click(screen.getByLabelText('Batal ubah nama'));

    expect(screen.getByText('MacBook')).toBeTruthy();
  });

  it('deletes a passkey after confirmation', async () => {
    render(ManagePasskeys, { canManagePasskeys: true, passkeys });

    await fireEvent.click(
      screen.getAllByRole('button', { name: 'Hapus passkey', hidden: true })[1],
    );
    expect(screen.getByText(/passkey "iPhone"/)).toBeTruthy();
    await fireEvent.click(
      screen.getByText('Hapus', { selector: 'button.btn-danger' }),
    );

    expect(router.delete).toHaveBeenCalledWith(
      expect.stringContaining('/2'),
      expect.objectContaining({ preserveScroll: true }),
    );
  });
});

describe('PasskeyLogin', () => {
  it('starts verification on click', async () => {
    render(PasskeyLogin);

    await fireEvent.click(
      screen.getByRole('button', { name: /Masuk dengan passkey/ }),
    );

    expect(verify.verify).toHaveBeenCalledOnce();
  });

  it('shows errors and disables while loading', () => {
    verify.error = 'Gagal';
    verify.isLoading = true;
    render(PasskeyLogin);
    verify.error = null;
    verify.isLoading = false;

    expect(screen.getByText('Gagal')).toBeTruthy();
    expect((screen.getByRole('button') as HTMLButtonElement).disabled).toBe(
      true,
    );
  });

  it('redirects after success, defaulting to /admin', () => {
    render(PasskeyLogin);

    callbacks.verify({ redirect: null });
    callbacks.verify({ redirect: '/dash' });

    expect(router.visit).toHaveBeenNthCalledWith(1, '/admin');
    expect(router.visit).toHaveBeenNthCalledWith(2, '/dash');
  });
});
