import { fireEvent, render, screen } from '@testing-library/svelte';
import { afterEach, describe, expect, it, vi } from 'vitest';
import AppHead from '@/components/app-head.svelte';
import CardCoffee from '@/components/card-coffee.svelte';
import HeaderHome from '@/components/home/header-home.svelte';
import ManageTwoFactor from '@/components/manage-two-factor.svelte';
import Turnstile from '@/components/turnstile.svelte';
import { hrefOf } from '@/tests/helpers';

const state = vi.hoisted(() => ({
  page: { props: {} as Record<string, unknown> },
  finish: undefined as undefined | (() => void),
  stop: vi.fn(),
}));
const twoFactor = vi.hoisted(() => ({
  state: {
    qrCodeSvg: null as string | null,
    manualSetupKey: null as string | null,
    recoveryCodesList: [] as string[],
    errors: [] as string[],
  },
  hasSetupData: vi.fn(() => false),
  fetchSetupData: vi.fn(),
  fetchRecoveryCodes: vi.fn(),
  clearTwoFactorAuthData: vi.fn(),
}));

vi.mock('@inertiajs/svelte', async (importOriginal) => {
  const original = await importOriginal<typeof import('@inertiajs/svelte')>();

  return {
    ...original,
    page: state.page,
    router: Object.assign(Object.create(original.router), {
      on: (_: string, callback: () => void) => {
        state.finish = callback;

        return state.stop;
      },
    }),
  };
});
vi.mock('@/lib/two-factor-auth.svelte', () => ({
  twoFactorAuthState: () => twoFactor,
}));

afterEach(() => {
  state.page.props = {};
  twoFactor.state.qrCodeSvg = null;
  twoFactor.state.manualSetupKey = null;
  twoFactor.state.recoveryCodesList = [];
  twoFactor.state.errors = [];
  document.getElementById('cloudflare-turnstile')?.remove();
  delete (window as { turnstile?: unknown }).turnstile;
});

describe('AppHead', () => {
  it('prefixes the title with the app name', () => {
    render(AppHead, { title: 'Blog' });

    expect(document.title).toMatch(/^Blog • /);
  });

  it('uses only the app name without a title', () => {
    render(AppHead, {});

    expect(document.title).not.toContain('•');
    expect(document.title.length).toBeGreaterThan(0);
  });
});

describe('HeaderHome', () => {
  it('renders the hero, calls to action and tech stack', () => {
    render(HeaderHome);

    expect(screen.getByRole('heading', { level: 1 }).textContent).toContain(
      'Wibowo',
    );
    expect(hrefOf(screen.getByRole('link', { name: /Hubungi Saya/ }))).toBe(
      '/kontak',
    );
    const resume = screen.getByRole('link', { name: /Resume/ });
    expect(resume.getAttribute('target')).toBe('_blank');
    expect(resume.getAttribute('rel')).toContain('noopener');
    expect(document.querySelectorAll('.tech-stack')).toHaveLength(9);
    expect(screen.getByAltText('Cak Adi')).toBeTruthy();
  });

  it('initialises Bootstrap tooltips on the tech stack', async () => {
    render(HeaderHome);

    const { Tooltip } = await import('bootstrap');

    await vi.waitFor(() =>
      expect(
        Tooltip.getInstance(
          document.querySelector('.tech-stack') as HTMLElement,
        ),
      ).not.toBeNull(),
    );
  });
});

describe('CardCoffee', () => {
  const place = {
    id: 7,
    name: 'Kopi Senja',
    region: 'Ngawi',
    description: 'a'.repeat(120),
    image: null,
    address: 'Jl. Merdeka 1',
    mapUrl: 'https://maps.test/x',
    opensAt: '08:00',
    closesAt: '22:00',
    parkFee: 2000,
    isRecommended: true,
    latitude: null,
    longitude: null,
    wifiProvider: 'IndiHome',
    wifiSpeed: 'Cepat',
    wifiSpeedColor: 'success',
    priceTier: '$$',
    priceTierColor: 'warning',
    facilities: ['Colokan', 'Parkir'],
    galleries: [
      { url: '/g1.webp', title: 'Teras' },
      { url: '/g2.webp', title: null },
    ],
  };

  it('shows a summary card with a truncated description', () => {
    render(CardCoffee, place);

    const card = document.querySelector('.card-text');
    expect(card?.textContent).toBe(`${'a'.repeat(100)}…`);
    expect(
      document.querySelector('[data-bs-target="#coffee-place-7"]'),
    ).not.toBeNull();
    expect(screen.getAllByAltText('Kopi Senja')[0].getAttribute('src')).toBe(
      '/images/coffee-default.webp',
    );
  });

  it('shows details in the modal', () => {
    render(CardCoffee, place);

    expect(screen.getByText('Direkomendasikan')).toBeTruthy();
    expect(screen.getByText('Rp 2.000')).toBeTruthy();
    expect(screen.getByText('IndiHome')).toBeTruthy();
    expect(screen.getByText('Cepat')).toBeTruthy();
    expect(screen.getByText('$$')).toBeTruthy();
    expect(screen.getByText('Colokan')).toBeTruthy();
    expect(screen.getByText('Jl. Merdeka 1')).toBeTruthy();
    expect(
      screen
        .getByRole('link', { name: /Arahkan Saya/, hidden: true })
        .getAttribute('href'),
    ).toBe('https://maps.test/x');
  });

  it('falls back for missing optional data', () => {
    render(CardCoffee, {
      ...place,
      description: null,
      region: null,
      opensAt: null,
      closesAt: null,
      parkFee: 0,
      isRecommended: false,
      mapUrl: null,
      wifiProvider: null,
      wifiSpeed: null,
      priceTier: null,
      facilities: [],
      galleries: [],
    });

    expect(screen.getByText('Gratis')).toBeTruthy();
    expect(screen.queryByText('Direkomendasikan')).toBeNull();
    expect(screen.queryByText('Fasilitas')).toBeNull();
    expect(
      screen.queryByRole('link', { name: /Arahkan Saya/, hidden: true }),
    ).toBeNull();
  });

  it('renders a map only with coordinates', () => {
    const { unmount } = render(CardCoffee, place);
    expect(document.querySelector('[role="application"]')).toBeNull();
    unmount();

    render(CardCoffee, { ...place, latitude: -7.4, longitude: '111.4' });
    expect(screen.getByLabelText('Peta lokasi Kopi Senja')).toBeTruthy();
  });

  it('opens the gallery lightbox', async () => {
    render(CardCoffee, place);

    await fireEvent.click(screen.getByLabelText('Pratinjau Teras'));

    expect(await screen.findByRole('dialog', { name: 'Teras' })).toBeTruthy();
  });
});

describe('Turnstile', () => {
  it('renders nothing without a site key', () => {
    const { container } = render(Turnstile, {});

    expect(container.querySelector('div')).toBeNull();
  });

  it('loads the script, renders the widget and shows errors', async () => {
    state.page.props = { turnstileSiteKey: 'site-key' };
    const turnstile = {
      render: vi.fn(() => 'w1'),
      reset: vi.fn(),
      remove: vi.fn(),
    };
    render(Turnstile, { error: 'Gagal verifikasi' });

    expect(screen.getByText('Gagal verifikasi')).toBeTruthy();
    const script = document.getElementById(
      'cloudflare-turnstile',
    ) as HTMLScriptElement;
    expect(script.src).toContain('challenges.cloudflare.com/turnstile');

    (window as unknown as { turnstile: unknown }).turnstile = turnstile;
    script.dispatchEvent(new Event('load'));

    await vi.waitFor(() =>
      expect(turnstile.render).toHaveBeenCalledWith(expect.any(HTMLElement), {
        sitekey: 'site-key',
        theme: 'auto',
      }),
    );
  });

  it('resets the widget after each Inertia request and cleans up', async () => {
    state.page.props = { turnstileSiteKey: 'site-key' };
    const turnstile = {
      render: vi.fn(() => 'w1'),
      reset: vi.fn(),
      remove: vi.fn(),
    };
    (window as unknown as { turnstile: unknown }).turnstile = turnstile;
    const { unmount } = render(Turnstile, {});
    await vi.waitFor(() => expect(turnstile.render).toHaveBeenCalled());

    state.finish?.();
    expect(turnstile.reset).toHaveBeenCalledWith('w1');

    unmount();
    expect(turnstile.remove).toHaveBeenCalledWith('w1');
    expect(state.stop).toHaveBeenCalled();
  });
});

describe('ManageTwoFactor', () => {
  it('renders nothing when management is disabled', () => {
    render(ManageTwoFactor, {});

    expect(screen.queryByText('Autentikasi dua faktor')).toBeNull();
  });

  it('offers to enable 2FA', () => {
    render(ManageTwoFactor, { canManageTwoFactor: true });

    expect(screen.getByRole('button', { name: 'Aktifkan 2FA' })).toBeTruthy();
  });

  it('offers to disable 2FA when enabled', () => {
    render(ManageTwoFactor, {
      canManageTwoFactor: true,
      twoFactorEnabled: true,
    });

    expect(
      screen.getByRole('button', { name: 'Nonaktifkan 2FA' }),
    ).toBeTruthy();
    expect(screen.queryByRole('button', { name: 'Aktifkan 2FA' })).toBeNull();
  });

  it('loads recovery codes on first reveal and toggles visibility', async () => {
    twoFactor.fetchRecoveryCodes.mockImplementation(async () => {
      twoFactor.state.recoveryCodesList = ['code-1', 'code-2'];
    });
    render(ManageTwoFactor, {
      canManageTwoFactor: true,
      twoFactorEnabled: true,
    });

    await fireEvent.click(
      screen.getByRole('button', { name: /Lihat kode pemulihan/ }),
    );

    expect(twoFactor.fetchRecoveryCodes).toHaveBeenCalledOnce();
    expect(await screen.findByText('code-1')).toBeTruthy();
    expect(
      screen.getByRole('button', { name: /Buat ulang kode/ }),
    ).toBeTruthy();

    await fireEvent.click(
      screen.getByRole('button', { name: /Sembunyikan kode pemulihan/ }),
    );
    expect(screen.queryByText('code-1')).toBeNull();
  });

  it('clears sensitive state when destroyed', () => {
    const { unmount } = render(ManageTwoFactor, { canManageTwoFactor: true });

    unmount();

    expect(twoFactor.clearTwoFactorAuthData).toHaveBeenCalled();
  });
});
