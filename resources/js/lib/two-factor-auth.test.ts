import { describe, expect, it, vi } from 'vitest';

const get = vi.hoisted(() => vi.fn());

vi.mock('@inertiajs/svelte', () => ({ useHttp: () => ({ get }) }));

import { twoFactorAuthState } from '@/lib/two-factor-auth.svelte';

describe('twoFactorAuthState', () => {
  it('loads QR code and setup key', async () => {
    get.mockImplementation(async (url: string) =>
      url.includes('qr') ? { svg: '<svg/>', url: 'x' } : { secretKey: 'KEY' },
    );
    const api = twoFactorAuthState();

    await api.fetchSetupData();

    expect(api.state.qrCodeSvg).toBe('<svg/>');
    expect(api.state.manualSetupKey).toBe('KEY');
    expect(api.hasSetupData()).toBe(true);
  });

  it('records errors when fetching fails', async () => {
    get.mockRejectedValue(new Error('x'));
    const api = twoFactorAuthState();

    await api.fetchSetupData();

    expect(api.hasSetupData()).toBe(false);
    expect(api.state.errors).toEqual([
      'Gagal mengambil kode QR',
      'Gagal mengambil kunci setup',
    ]);
  });

  it('fetches and clears recovery codes', async () => {
    get.mockResolvedValue(['a', 'b']);
    const api = twoFactorAuthState();

    await api.fetchRecoveryCodes();
    expect(api.state.recoveryCodesList).toEqual(['a', 'b']);

    api.clearTwoFactorAuthData();
    expect(api.state.recoveryCodesList).toEqual([]);
    expect(api.state.errors).toEqual([]);
  });

  it('reports recovery code failures', async () => {
    get.mockRejectedValue(new Error('x'));
    const api = twoFactorAuthState();

    await api.fetchRecoveryCodes();

    expect(api.state.errors).toContain('Gagal mengambil kode pemulihan');
    expect(api.state.recoveryCodesList).toEqual([]);
  });

  it('clearSetupData resets setup state and errors', async () => {
    get.mockResolvedValue({ svg: 's', secretKey: 'k' });
    const api = twoFactorAuthState();
    await api.fetchSetupData();

    api.clearSetupData();

    expect(api.hasSetupData()).toBe(false);
    expect(api.state.errors).toEqual([]);
  });
});
