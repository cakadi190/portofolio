import { fireEvent, render, screen } from '@testing-library/svelte';
import { describe, expect, it, vi } from 'vitest';
import DatePicker from '@/components/ui/date-picker.svelte';
import MapPicker from '@/components/ui/map-picker.svelte';
import TimePicker from '@/components/ui/time-picker.svelte';

const hidden = (name: string) =>
  document.querySelector<HTMLInputElement>(`input[type="hidden"][name="${name}"]`);

describe('DatePicker', () => {
  it('submits an empty value and shows the placeholder without a date', () => {
    render(DatePicker, { name: 'date', placeholder: 'Pilih' });

    expect(hidden('date')?.value).toBe('');
    expect(screen.getByPlaceholderText('Pilih')).toBeTruthy();
    expect(screen.queryByLabelText('Hapus tanggal')).toBeNull();
  });

  it('formats the date for display and submits ISO', async () => {
    render(DatePicker, { id: 'd', name: 'date', value: '2024-03-05' });

    await vi.waitFor(() => expect(hidden('date')?.value).toBe('2024-03-05'));
    expect((document.getElementById('d') as HTMLInputElement).value).toBe('5 Maret 2024');
  });

  it('keeps the time when withTime is set', async () => {
    render(DatePicker, { id: 'd', name: 'at', value: '2024-03-05 14:30', withTime: true });

    await vi.waitFor(() => expect(hidden('at')?.value).toBe('2024-03-05T14:30'));
    expect((document.getElementById('d') as HTMLInputElement).value).toBe('5 Maret 2024, 14:30');
  });

  it('clears the date', async () => {
    render(DatePicker, { id: 'd', name: 'date', value: '2024-03-05' });
    await vi.waitFor(() => expect(hidden('date')?.value).toBe('2024-03-05'));

    await fireEvent.click(screen.getByLabelText('Hapus tanggal'));

    expect(hidden('date')?.value).toBe('');
  });

  it('ignores malformed values', () => {
    render(DatePicker, { name: 'date', value: 'bukan-tanggal' });

    expect(hidden('date')?.value).toBe('');
  });

  it('marks invalid state', () => {
    const { container } = render(DatePicker, { id: 'd', name: 'date', invalid: true });

    expect(container.querySelector('.neo-datepicker.is-invalid')).not.toBeNull();
    expect(document.getElementById('d')?.classList).toContain('is-invalid');
  });
});

describe('TimePicker', () => {
  it('starts empty', () => {
    render(TimePicker, { id: 't', name: 'time' });

    expect(hidden('time')?.value).toBe('');
  });

  it('reads an initial HH:mm value', async () => {
    render(TimePicker, { id: 't', name: 'time', value: '08:05:00' });

    await vi.waitFor(() => expect(hidden('time')?.value).toBe('08:05'));
  });

  it('picks hour then minute and closes', async () => {
    render(TimePicker, { id: 't', name: 'time', minuteStep: 15 });

    await fireEvent.click(document.getElementById('t')!);
    await fireEvent.click(screen.getByRole('button', { name: '09' }));
    expect(hidden('time')?.value).toBe('09:00');

    await fireEvent.click(screen.getByRole('button', { name: '45' }));

    expect(hidden('time')?.value).toBe('09:45');
    expect(document.querySelector('.neo-timepicker-panel')).toBeNull();
  });

  it('offers minutes by the configured step', async () => {
    render(TimePicker, { id: 't', name: 'time', minuteStep: 30 });

    await fireEvent.click(document.getElementById('t')!);

    const minuteColumn = document.querySelectorAll('.neo-timepicker-col')[1];
    expect(
      [...minuteColumn.querySelectorAll('button')].map((b) => b.textContent?.trim()),
    ).toEqual(['00', '30']);
  });

  it('closes when clicking outside and can be cleared', async () => {
    render(TimePicker, { id: 't', name: 'time', value: '10:10' });
    await vi.waitFor(() => expect(hidden('time')?.value).toBe('10:10'));

    await fireEvent.click(document.getElementById('t')!);
    expect(document.querySelector('.neo-timepicker-panel')).not.toBeNull();

    await fireEvent.click(document.body);
    expect(document.querySelector('.neo-timepicker-panel')).toBeNull();

    await fireEvent.click(screen.getByLabelText('Hapus jam'));
    expect(hidden('time')?.value).toBe('');
  });
});

describe('MapPicker', () => {
  it('renders the map and coordinate fields with initial values', () => {
    render(MapPicker, { idPrefix: 'cp', latitude: -7.4, longitude: '111.4' });

    expect(screen.getByLabelText('Peta pemilih lokasi')).toBeTruthy();
    expect((screen.getByLabelText('Latitude') as HTMLInputElement).value).toBe('-7.4');
    expect((screen.getByLabelText('Longitude') as HTMLInputElement).value).toBe('111.4');
    expect(document.querySelector('.leaflet-container')).not.toBeNull();
    expect(document.querySelector('.leaflet-marker-icon')).not.toBeNull();
  });

  it('places no marker without coordinates', () => {
    render(MapPicker, { idPrefix: 'cp' });

    expect(document.querySelector('.leaflet-marker-icon')).toBeNull();
  });

  it('follows typed coordinates and drops the marker for invalid input', async () => {
    render(MapPicker, { idPrefix: 'cp' });

    await fireEvent.input(screen.getByLabelText('Latitude'), { target: { value: '-6.2' } });
    await fireEvent.input(screen.getByLabelText('Longitude'), { target: { value: '106.8' } });
    expect(document.querySelector('.leaflet-marker-icon')).not.toBeNull();

    await fireEvent.input(screen.getByLabelText('Latitude'), { target: { value: '999' } });
    expect(document.querySelector('.leaflet-marker-icon')).toBeNull();
  });

  it('shows field errors', () => {
    render(MapPicker, { idPrefix: 'cp', latitudeError: 'Lat salah', longitudeError: 'Lng salah' });

    expect(screen.getByText('Lat salah')).toBeTruthy();
    expect(screen.getByText('Lng salah')).toBeTruthy();
  });

  it('fills coordinates from the browser location', async () => {
    const getCurrentPosition = vi.fn((success: PositionCallback) =>
      success({ coords: { latitude: -7.5, longitude: 111.5 } } as GeolocationPosition),
    );
    vi.stubGlobal('navigator', { ...navigator, geolocation: { getCurrentPosition } });
    render(MapPicker, { idPrefix: 'cp' });

    await fireEvent.click(screen.getByRole('button', { name: 'Lokasi saya' }));

    expect(Number((screen.getByLabelText('Latitude') as HTMLInputElement).value)).toBeCloseTo(-7.5);
    expect(Number((screen.getByLabelText('Longitude') as HTMLInputElement).value)).toBeCloseTo(111.5);
  });
});
