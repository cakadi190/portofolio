import { fireEvent, render, screen } from '@testing-library/svelte';
import { router } from '@inertiajs/svelte';
import { describe, expect, it, vi } from 'vitest';
import ConfirmPassword from '@/pages/auth/confirm-password/confirm-password.svelte';
import ForgotPassword from '@/pages/auth/forgot-password/forgot-password.svelte';
import LoginForm from '@/pages/auth/login/form.svelte';
import Login from '@/pages/auth/login/login.svelte';
import Register from '@/pages/auth/register/register.svelte';
import ResetPassword from '@/pages/auth/reset-password/reset-password.svelte';
import TwoFactorChallenge from '@/pages/auth/two-factor-challenge/two-factor-challenge.svelte';
import VerifyEmail from '@/pages/auth/verify-email/verify-email.svelte';
import { hrefOf } from '@/tests/helpers';

vi.mock('@laravel/passkeys/svelte', () => ({
  usePasskeyVerify: () => ({ isLoading: false, error: null, verify: vi.fn() }),
  usePasskeyRegister: () => ({
    isSupported: true,
    isLoading: false,
    error: null,
    register: vi.fn(),
  }),
}));

describe('LoginForm', () => {
  it('renders credentials fields and posts to the login route', () => {
    render(LoginForm, { canResetPassword: true });

    expect(screen.getByLabelText('Alamat email')).toBeTruthy();
    expect(screen.getByLabelText('Kata sandi')).toBeTruthy();
    expect(screen.getByLabelText('Ingatkan saya')).toBeTruthy();
    expect(screen.getByRole('button', { name: 'Masuk' })).toBeTruthy();
    expect(document.querySelector('form')?.getAttribute('action')).toContain(
      'login',
    );
    expect(
      hrefOf(screen.getByRole('link', { name: /Lupa kata sandi/ })),
    ).toContain('forgot-password');
  });

  it('hides the reset link when disabled', () => {
    render(LoginForm, { canResetPassword: false });

    expect(screen.queryByRole('link', { name: /Lupa kata sandi/ })).toBeNull();
  });
});

describe('Login', () => {
  it('shows only the email form without passkeys', () => {
    render(Login, { canResetPassword: true, status: 'Sandi direset.' });

    expect(screen.getByText('Sandi direset.')).toBeTruthy();
    expect(screen.queryByRole('tab')).toBeNull();
    expect(hrefOf(screen.getByRole('link', { name: 'Daftar' }))).toContain(
      'register',
    );
    expect(document.title).toContain('Masuk');
  });

  it('switches between passkey and email tabs', async () => {
    render(Login, { canResetPassword: true, canUsePasskeys: true });

    expect(
      screen
        .getByRole('tab', { name: 'Passkey' })
        .getAttribute('aria-selected'),
    ).toBe('true');
    expect(
      screen.getByText(/Masuk dengan passkey/, { selector: 'button' }),
    ).toBeTruthy();

    await fireEvent.click(screen.getByRole('tab', { name: 'Akun Email' }));

    expect(screen.getByLabelText('Alamat email')).toBeTruthy();
  });
});

describe('TwoFactorChallenge', () => {
  it('toggles between authenticator and recovery codes', async () => {
    vi.spyOn(router, 'visit').mockImplementation(() => undefined);
    render(TwoFactorChallenge);

    expect(screen.getByLabelText('Kode autentikasi')).toBeTruthy();

    await fireEvent.click(screen.getByText('masuk menggunakan kode pemulihan'));

    expect(screen.getByLabelText('Kode pemulihan')).toBeTruthy();
    expect(screen.queryByLabelText('Kode autentikasi')).toBeNull();
  });
});

describe('simple auth pages', () => {
  it('ForgotPassword renders an email form and status', () => {
    render(ForgotPassword, { status: 'Tautan dikirim' });

    expect(screen.getByText('Tautan dikirim')).toBeTruthy();
    expect(document.querySelector('input[name="email"]')).not.toBeNull();
  });

  it('ConfirmPassword renders a password field', () => {
    render(ConfirmPassword);

    expect(document.querySelector('input[name="password"]')).not.toBeNull();
  });

  it('Register renders the sign-up fields', () => {
    render(Register, { passwordRules: 'min:8' });

    for (const name of ['name', 'email', 'password', 'password_confirmation']) {
      expect(document.querySelector(`input[name="${name}"]`)).not.toBeNull();
    }
  });

  it('ResetPassword prefills the email and offers a password pair', () => {
    render(ResetPassword, {
      token: 'tok123',
      user: { email: 'a@b.c' } as never,
      passwordRules: 'min:8',
    });

    expect(
      document.querySelector<HTMLInputElement>('input[name="email"]')?.value,
    ).toBe('a@b.c');
    expect(document.querySelector('input[name="password"]')).not.toBeNull();
    expect(
      document.querySelector('input[name="password_confirmation"]'),
    ).not.toBeNull();
  });

  it('VerifyEmail shows the verification status and a resend action', () => {
    render(VerifyEmail, { status: 'verification-link-sent' });

    expect(document.querySelector('form')).not.toBeNull();
    expect(document.title).toBeTruthy();
  });
});
