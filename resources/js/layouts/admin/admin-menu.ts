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
  return [
    { type: 'header', label: 'Menu Utama' },
    {
      label: 'Dasbor',
      icon: LayoutDashboard,
      href: '/admin',
      active: currentUrl === '/admin',
    },

    { type: 'header', label: 'Portofolio & Blog' },
    {
      label: 'Profil',
      icon: GraduationCap,
      children: [
        { label: 'Riwayat Pendidikan', href: '/admin/educations' },
        { label: 'Pengalaman Organisasi', href: '/admin/organizations' },
        { label: 'Pengalaman Karier', href: '/admin/careers' },
      ],
    },
    {
      label: 'Penghargaan',
      icon: Award,
      href: '/admin/awards',
      active: currentUrl.startsWith('/admin/awards'),
    },
    {
      label: 'Portofolio',
      icon: FolderKanban,
      children: [
        { label: 'Semua Portofolio', href: '/admin/portfolios' },
        { label: 'Kategori Portofolio', href: '/admin/portfolio-categories' },
        { label: 'Galeri Portofolio', href: '/admin/portfolio-galleries' },
        { label: 'Ulasan Portofolio', href: '/admin/portfolio-ratings' },
        { label: 'Teknologi', href: '/admin/technologies' },
      ],
    },
    {
      label: 'Blog',
      icon: Newspaper,
      children: [
        { label: 'Semua Artikel', href: '/admin/posts' },
        { label: 'Kategori Artikel Blog', href: '/admin/post-categories' },
        { label: 'Tag Artikel', href: '/admin/tags' },
      ],
    },
    {
      label: 'Kedai Kopi',
      icon: Coffee,
      href: '/admin/coffee-places',
      active: currentUrl.startsWith('/admin/coffee-places'),
    },

    { type: 'header', label: 'Pengguna & Akses' },
    {
      label: 'Pengguna',
      icon: User,
      href: '/admin/users',
      active: currentUrl.startsWith('/admin/users'),
    },
  ];
}
