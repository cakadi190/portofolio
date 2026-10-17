import { t } from '@/lib/i18n';
import Activity from '@lucide/svelte/icons/activity';
import ChartColumn from '@lucide/svelte/icons/chart-column';
import Award from '@lucide/svelte/icons/award';
import BadgeCheck from '@lucide/svelte/icons/badge-check';
import Tv from '@lucide/svelte/icons/tv';
import Users from '@lucide/svelte/icons/users';
import Coffee from '@lucide/svelte/icons/coffee';
import FolderKanban from '@lucide/svelte/icons/folder-kanban';
import GraduationCap from '@lucide/svelte/icons/graduation-cap';
import LayoutDashboard from '@lucide/svelte/icons/layout-dashboard';
import Contact from '@lucide/svelte/icons/contact';
import Images from '@lucide/svelte/icons/images';
import Mic from '@lucide/svelte/icons/mic';
import NotebookPen from '@lucide/svelte/icons/notebook-pen';
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
    label: t('admin.menu_dasbor'),
    icon: LayoutDashboard,
    href: '/admin',
    active: currentUrl.split('?')[0] === '/admin',
  };
  const blog: AdminSidebarEntry = {
    label: t('admin.menu_blog'),
    icon: Newspaper,
    children: [
      link(t('admin.menu_semua_artikel'), '/admin/posts'),
      link(t('admin.menu_kategori_artikel_blog'), '/admin/post-categories'),
      link(t('admin.menu_tag_artikel'), '/admin/tags'),
      link(t('admin.menu_komentar_blog'), '/admin/blog-comments'),
    ],
  };
  const notes: AdminSidebarEntry = {
    label: t('admin.menu_catatan_buku_tamu'),
    icon: NotebookPen,
    children: [
      link(t('admin.menu_catatan_singkat'), '/admin/notes'),
      link(t('admin.menu_buku_tamu'), '/admin/guestbook-entries'),
    ],
  };

  if (accountType !== 'admin') {
    return [
      { type: 'header', label: t('admin.menu_menu_utama') },
      dashboard,
      { type: 'header', label: t('admin.menu_blog') },
      blog,
      notes,
    ];
  }

  return [
    { type: 'header', label: t('admin.menu_menu_utama') },
    dashboard,

    { type: 'header', label: t('admin.menu_profil_karier') },
    {
      label: t('admin.profil'),
      icon: GraduationCap,
      children: [
        link(t('admin.menu_riwayat_pendidikan'), '/admin/educations'),
        link(t('admin.menu_pengalaman_organisasi'), '/admin/organizations'),
        link(t('admin.menu_pengalaman_karier'), '/admin/careers'),
        link(t('admin.menu_sertifikasi'), '/admin/certifications'),
      ],
    },
    {
      label: t('admin.menu_penghargaan'),
      icon: Award,
      href: '/admin/awards',
      active: isActive('/admin/awards'),
    },
    {
      label: t('admin.menu_pembicara_mentoring'),
      icon: Mic,
      href: '/admin/speaking-engagements',
      active: isActive('/admin/speaking-engagements'),
    },
    { type: 'header', label: t('admin.portofolio') },
    {
      label: t('admin.portofolio'),
      icon: FolderKanban,
      children: [
        link(t('admin.menu_semua_portofolio'), '/admin/portfolios'),
        link(t('admin.menu_galeri_portofolio'), '/admin/portfolio-galleries'),
        link(t('admin.menu_ulasan_portofolio'), '/admin/portfolio-ratings'),
        link(t('admin.layanan'), '/admin/services'),
      ],
    },
    { type: 'header', label: t('admin.menu_blog_tulisan') },
    blog,
    notes,
    {
      label: t('admin.menu_klarifikasi'),
      icon: BadgeCheck,
      href: '/admin/clarifications',
      active: isActive('/admin/clarifications'),
    },
    { type: 'header', label: t('admin.menu_serba_serbi') },
    {
      label: t('admin.menu_komunitas_dev_id'),
      icon: Users,
      href: '/admin/dev-communities',
      active: isActive('/admin/dev-communities'),
    },
    {
      label: t('admin.menu_channel_programmer'),
      icon: Tv,
      href: '/admin/programmer-channels',
      active: isActive('/admin/programmer-channels'),
    },
    {
      label: t('admin.menu_kedai_kopi'),
      icon: Coffee,
      href: '/admin/coffee-places',
      active: isActive('/admin/coffee-places'),
    },

    { type: 'header', label: t('admin.menu_pesan_media') },
    {
      label: t('admin.menu_pesan_masuk'),
      icon: Contact,
      href: '/admin/contact-messages',
      active: isActive('/admin/contact-messages'),
    },
    {
      label: t('admin.menu_pustaka_media'),
      icon: Images,
      href: '/admin/media',
      active: isActive('/admin/media'),
    },

    { type: 'header', label: t('admin.menu_pengguna_akses') },
    {
      label: t('admin.pengguna'),
      icon: User,
      href: '/admin/users',
      active: isActive('/admin/users'),
    },

    { type: 'header', label: t('admin.menu_sistem') },
    {
      label: t('admin.menu_analitik_pengunjung'),
      icon: ChartColumn,
      href: '/admin/visitor-analytics',
      active: isActive('/admin/visitor-analytics'),
    },
    {
      label: t('admin.menu_log_aktivitas'),
      icon: Activity,
      href: '/admin/activity-logs',
      active: isActive('/admin/activity-logs'),
    },
    {
      label: t('admin.menu_pengaturan_sistem'),
      icon: Settings,
      href: '/admin/system-settings',
      active: isActive('/admin/system-settings'),
    },
  ];
}
