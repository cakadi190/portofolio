import Award from '@lucide/svelte/icons/award';
import Coffee from '@lucide/svelte/icons/coffee';
import FolderKanban from '@lucide/svelte/icons/folder-kanban';
import GraduationCap from '@lucide/svelte/icons/graduation-cap';
import LayoutDashboard from '@lucide/svelte/icons/layout-dashboard';
import Contact from '@lucide/svelte/icons/contact';
import Images from '@lucide/svelte/icons/images';
import Mic from '@lucide/svelte/icons/mic';
import Newspaper from '@lucide/svelte/icons/newspaper';
import Settings from '@lucide/svelte/icons/settings';
import User from '@lucide/svelte/icons/user';
import type { AdminSidebarEntry } from '@/types/admin-sidebar';

/**
 * Admin sidebar tree. Only entries wired to a real route are listed.
 * Redaktur only get the blogging section; the server enforces this too.
 */
export function adminMenu(
  currentUrl: string,
  accountType: string = 'admin',
): AdminSidebarEntry[] {
  const isActive = (href: string): boolean =>
    currentUrl === href ||
    currentUrl.startsWith(`${href}/`) ||
    currentUrl.startsWith(`${href}?`);
  const link = (label: string, href: string): AdminSidebarEntry => ({
    label,
    href,
    active: isActive(href),
  });

  const dashboard: AdminSidebarEntry = {
    label: 'Dasbor',
    icon: LayoutDashboard,
    href: '/admin',
    active: currentUrl.split('?')[0] === '/admin',
  };
  const blog: AdminSidebarEntry = {
    label: 'Blog',
    icon: Newspaper,
    children: [
      link('Semua Artikel', '/admin/posts'),
      link('Kategori Artikel Blog', '/admin/post-categories'),
      link('Tag Artikel', '/admin/tags'),
      link('Komentar Blog', '/admin/blog-comments'),
    ],
  };

  if (accountType !== 'admin') {
    return [
      { type: 'header', label: 'Menu Utama' },
      dashboard,
      { type: 'header', label: 'Blog' },
      blog,
    ];
  }

  return [
    { type: 'header', label: 'Menu Utama' },
    dashboard,

    { type: 'header', label: 'Portofolio & Blog' },
    {
      label: 'Profil',
      icon: GraduationCap,
      children: [
        link('Riwayat Pendidikan', '/admin/educations'),
        link('Pengalaman Organisasi', '/admin/organizations'),
        link('Pengalaman Karier', '/admin/careers'),
        link('Sertifikasi', '/admin/certifications'),
      ],
    },
    {
      label: 'Penghargaan',
      icon: Award,
      href: '/admin/awards',
      active: isActive('/admin/awards'),
    },
    {
      label: 'Pembicara & Mentoring',
      icon: Mic,
      href: '/admin/speaking-engagements',
      active: isActive('/admin/speaking-engagements'),
    },
    {
      label: 'Portofolio',
      icon: FolderKanban,
      children: [
        link('Semua Portofolio', '/admin/portfolios'),
        link('Galeri Portofolio', '/admin/portfolio-galleries'),
        link('Ulasan Portofolio', '/admin/portfolio-ratings'),
        link('Layanan', '/admin/services'),
        link('Teknologi', '/admin/technologies'),
      ],
    },
    blog,
    {
      label: 'Kedai Kopi',
      icon: Coffee,
      href: '/admin/coffee-places',
      active: isActive('/admin/coffee-places'),
    },

    {
      label: 'Pesan Masuk',
      icon: Contact,
      href: '/admin/contact-messages',
      active: isActive('/admin/contact-messages'),
    },
    {
      label: 'Pustaka Media',
      icon: Images,
      href: '/admin/media',
      active: isActive('/admin/media'),
    },

    { type: 'header', label: 'Pengguna & Akses' },
    {
      label: 'Pengguna',
      icon: User,
      href: '/admin/users',
      active: isActive('/admin/users'),
    },

    { type: 'header', label: 'Sistem' },
    {
      label: 'Pengaturan Sistem',
      icon: Settings,
      href: '/admin/system-settings',
      active: isActive('/admin/system-settings'),
    },
  ];
}
