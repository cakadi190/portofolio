import Award from '@lucide/svelte/icons/award';
import Coffee from '@lucide/svelte/icons/coffee';
import FolderKanban from '@lucide/svelte/icons/folder-kanban';
import GraduationCap from '@lucide/svelte/icons/graduation-cap';
import LayoutDashboard from '@lucide/svelte/icons/layout-dashboard';
import Newspaper from '@lucide/svelte/icons/newspaper';
import User from '@lucide/svelte/icons/user';
import type { AdminSidebarEntry } from '@/types/admin-sidebar';

/**
 * Admin sidebar tree. Only entries wired to a real route are listed.
 */
export function adminMenu(currentUrl: string): AdminSidebarEntry[] {
  const isActive = (href: string): boolean =>
    currentUrl === href || currentUrl.startsWith(`${href}/`) || currentUrl.startsWith(`${href}?`);
  const link = (label: string, href: string): AdminSidebarEntry => ({
    label,
    href,
    active: isActive(href),
  });

  return [
    { type: 'header', label: 'Menu Utama' },
    {
      label: 'Dasbor',
      icon: LayoutDashboard,
      href: '/admin',
      active: currentUrl.split('?')[0] === '/admin',
    },

    { type: 'header', label: 'Portofolio & Blog' },
    {
      label: 'Profil',
      icon: GraduationCap,
      children: [
        link('Riwayat Pendidikan', '/admin/educations'),
        link('Pengalaman Organisasi', '/admin/organizations'),
        link('Pengalaman Karier', '/admin/careers'),
      ],
    },
    {
      label: 'Penghargaan',
      icon: Award,
      href: '/admin/awards',
      active: isActive('/admin/awards'),
    },
    {
      label: 'Portofolio',
      icon: FolderKanban,
      children: [
        link('Semua Portofolio', '/admin/portfolios'),
        link('Kategori Portofolio', '/admin/portfolio-categories'),
        link('Galeri Portofolio', '/admin/portfolio-galleries'),
        link('Ulasan Portofolio', '/admin/portfolio-ratings'),
        link('Teknologi', '/admin/technologies'),
      ],
    },
    {
      label: 'Blog',
      icon: Newspaper,
      children: [
        link('Semua Artikel', '/admin/posts'),
        link('Kategori Artikel Blog', '/admin/post-categories'),
        link('Tag Artikel', '/admin/tags'),
      ],
    },
    {
      label: 'Kedai Kopi',
      icon: Coffee,
      href: '/admin/coffee-places',
      active: isActive('/admin/coffee-places'),
    },

    { type: 'header', label: 'Pengguna & Akses' },
    {
      label: 'Pengguna',
      icon: User,
      href: '/admin/users',
      active: isActive('/admin/users'),
    },
  ];
}
