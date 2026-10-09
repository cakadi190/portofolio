/** Event times are entered as WIB (UTC+7) wall-clock values: "YYYY-MM-DD HH:mm". */
const WIB_OFFSET_HOURS = 7;

export type ZoneRow = {
  key: string;
  label: string;
  timeZone: string;
  start: string;
  end: string | null;
  isDevice: boolean;
};

const ZONES = [
  { key: 'wib', label: 'WIB', timeZone: 'Asia/Jakarta' },
  { key: 'wita', label: 'WITA', timeZone: 'Asia/Makassar' },
  { key: 'wit', label: 'WIT', timeZone: 'Asia/Jayapura' },
  { key: 'sgt', label: 'Singapura / Malaysia', timeZone: 'Asia/Singapore' },
  { key: 'utc', label: 'UTC', timeZone: 'UTC' },
];

/** Convert a WIB wall-clock string into the absolute instant it denotes. */
export function wibToInstant(value: string): Date {
  const [date, time = '00:00'] = value.split(' ');
  const [year, month, day] = date.split('-').map(Number);
  const [hour, minute] = time.split(':').map(Number);

  return new Date(
    Date.UTC(year, month - 1, day, hour - WIB_OFFSET_HOURS, minute),
  );
}

function formatIn(instant: Date, timeZone: string): string {
  const parts = new Intl.DateTimeFormat('id-ID', {
    timeZone,
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
  }).formatToParts(instant);
  const get = (type: string): string =>
    parts.find((part) => part.type === type)?.value ?? '';

  return `${get('weekday')}, ${get('day')} ${get('month')} · ${get('hour')}.${get('minute')}`;
}

/** The viewer's own IANA timezone, or null when it cannot be determined. */
export function deviceTimeZone(): string | null {
  try {
    return Intl.DateTimeFormat().resolvedOptions().timeZone || null;
  } catch {
    return null;
  }
}

/** One row per zone showing the event start (and end) in that zone's local time. */
export function zoneRows(
  startsAt: string,
  endsAt: string | null,
  device: string | null = deviceTimeZone(),
): ZoneRow[] {
  const start = wibToInstant(startsAt);
  const end = endsAt ? wibToInstant(endsAt) : null;
  const zones = [...ZONES];

  if (device && !zones.some((zone) => zone.timeZone === device)) {
    zones.unshift({ key: 'device', label: 'Zona waktu perangkat Anda', timeZone: device });
  }

  return zones.map((zone) => ({
    ...zone,
    start: formatIn(start, zone.timeZone),
    end: end ? formatIn(end, zone.timeZone) : null,
    isDevice: zone.timeZone === device || zone.key === 'device',
  }));
}
