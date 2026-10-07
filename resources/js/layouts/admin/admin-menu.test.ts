import { describe, expect, it } from 'vitest';
import { adminMenu } from '@/layouts/admin/admin-menu';
import type { AdminSidebarMenuItem } from '@/types/admin-sidebar';

const items = (url: string) =>
  adminMenu(url).filter(
    (entry): entry is AdminSidebarMenuItem => entry.type !== 'header',
  );

describe('adminMenu', () => {
  it('starts with a section header and includes section headers', () => {
    const menu = adminMenu('/admin');

    expect(menu[0]).toEqual({ type: 'header', label: 'Menu Utama' });
    expect(menu.filter((entry) => entry.type === 'header')).toHaveLength(4);
  });

  it('marks only the dashboard active on /admin', () => {
    const active = items('/admin?tab=1').filter((item) => item.active);

    expect(active.map((item) => item.label)).toEqual(['Dasbor']);
  });

  it('marks nested links active by prefix and query', () => {
    const blog = items('/admin/posts/12/edit').find(
      (item) => item.label === 'Blog',
    );

    expect(
      blog?.children?.map((child) => (child as AdminSidebarMenuItem).active),
    ).toEqual([true, false, false]);
  });

  it('does not match sibling routes sharing a prefix', () => {
    const portfolios = items('/admin/portfolio-galleries').find(
      (item) => item.label === 'Portofolio',
    );
    const [all, galleries] = (portfolios?.children ??
      []) as AdminSidebarMenuItem[];

    expect(all.active).toBe(false);
    expect(galleries.active).toBe(true);
  });

  it('gives every leaf an href', () => {
    const leaves = items('/').flatMap((item) => item.children ?? [item]);

    for (const leaf of leaves as AdminSidebarMenuItem[]) {
      expect(leaf.href).toMatch(/^\/admin/);
    }
  });
});
