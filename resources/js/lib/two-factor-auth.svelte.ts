import { useHttp } from '@inertiajs/svelte';
import {
  qrCode,
  recoveryCodes,
  secretKey,
} from '@/wayfinder/routes/two-factor';

type TwoFactorAuthState = {
  qrCodeSvg: string | null;
  manualSetupKey: string | null;
  recoveryCodesList: string[];
  errors: string[];
};

export type TwoFactorAuthStateApi = {
  state: TwoFactorAuthState;
  hasSetupData: () => boolean;
  clearSetupData: () => void;
  clearErrors: () => void;
  clearTwoFactorAuthData: () => void;
  fetchSetupData: () => Promise<void>;
  fetchRecoveryCodes: () => Promise<void>;
};

const state = $state<TwoFactorAuthState>({
  qrCodeSvg: null,
  manualSetupKey: null,
  recoveryCodesList: [],
  errors: [],
});

const hasSetupData = (): boolean =>
  state.qrCodeSvg !== null && state.manualSetupKey !== null;

export function twoFactorAuthState(): TwoFactorAuthStateApi {
  const http = useHttp();

  const fetchQrCode = async (): Promise<void> => {
    try {
      const { svg } = await http.get<{ svg: string; url: string }>(
        qrCode.url(),
      );

      state.qrCodeSvg = svg;
    } catch {
      state.errors = [...state.errors, 'Gagal mengambil kode QR'];
      state.qrCodeSvg = null;
    }
  };

  const fetchSetupKey = async (): Promise<void> => {
    try {
      const { secretKey: key } = await http.get<{ secretKey: string }>(
        secretKey.url(),
      );

      state.manualSetupKey = key;
    } catch {
      state.errors = [...state.errors, 'Gagal mengambil kunci setup'];
      state.manualSetupKey = null;
    }
  };

  const clearErrors = (): void => {
    state.errors = [];
  };

  const clearSetupData = (): void => {
    state.manualSetupKey = null;
    state.qrCodeSvg = null;
    clearErrors();
  };

  const clearTwoFactorAuthData = (): void => {
    clearSetupData();
    state.recoveryCodesList = [];
    clearErrors();
  };

  const fetchRecoveryCodes = async (): Promise<void> => {
    try {
      clearErrors();
      state.recoveryCodesList = await http.get<string[]>(
        recoveryCodes.url(),
      );
    } catch {
      state.errors = [...state.errors, 'Gagal mengambil kode pemulihan'];
      state.recoveryCodesList = [];
    }
  };

  const fetchSetupData = async (): Promise<void> => {
    try {
      clearErrors();
      await Promise.all([fetchQrCode(), fetchSetupKey()]);
    } catch {
      state.qrCodeSvg = null;
      state.manualSetupKey = null;
    }
  };

  return {
    state,
    hasSetupData,
    clearSetupData,
    clearErrors,
    clearTwoFactorAuthData,
    fetchSetupData,
    fetchRecoveryCodes,
  };
}
