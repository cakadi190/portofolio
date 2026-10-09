import { describe, expect, it } from 'vitest';
import { wibToInstant, zoneRows } from '@/lib/timezones';

describe('timezones', () => {
  it('converts a WIB wall-clock time to the matching UTC instant', () => {
    expect(wibToInstant('2026-10-11 19:30').toISOString()).toBe(
      '2026-10-11T12:30:00.000Z',
    );
  });

  it('lists the event in WIB, WITA, WIT and UTC', () => {
    const rows = zoneRows('2026-10-11 19:30', '2026-10-11 21:30', null);
    const byKey = Object.fromEntries(rows.map((row) => [row.key, row]));

    expect(byKey.wib.start).toContain('19.30');
    expect(byKey.wita.start).toContain('20.30');
    expect(byKey.wit.start).toContain('21.30');
    expect(byKey.utc.start).toContain('12.30');
    expect(byKey.wib.end).toContain('21.30');
  });

  it('rolls over to the next day where the local time passes midnight', () => {
    const rows = zoneRows('2026-10-11 23:00', null, null);

    expect(rows.find((row) => row.key === 'wit')?.start).toContain('Senin, 12');
    expect(rows.find((row) => row.key === 'wib')?.end).toBeNull();
  });

  it('adds and flags the device zone when it is not already listed', () => {
    const rows = zoneRows('2026-10-11 19:30', null, 'America/New_York');

    expect(rows[0]).toMatchObject({ key: 'device', isDevice: true });
    expect(rows[0].start).toContain('08.30');
  });

  it('flags a listed zone instead of duplicating it', () => {
    const rows = zoneRows('2026-10-11 19:30', null, 'Asia/Makassar');

    expect(rows.filter((row) => row.isDevice).map((row) => row.key)).toEqual(['wita']);
    expect(rows).toHaveLength(5);
  });
});
