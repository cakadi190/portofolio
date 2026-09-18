import Building from '@lucide/svelte/icons/building';
import Car from '@lucide/svelte/icons/car';
import ChartPie from '@lucide/svelte/icons/chart-pie';
import Clapperboard from '@lucide/svelte/icons/clapperboard';
import ImagePlay from '@lucide/svelte/icons/image-play';
import LayoutDashboard from '@lucide/svelte/icons/layout-dashboard';
import Landmark from '@lucide/svelte/icons/landmark';
import Newspaper from '@lucide/svelte/icons/newspaper';
import Receipt from '@lucide/svelte/icons/receipt';
import ScrollText from '@lucide/svelte/icons/scroll-text';
import Settings from '@lucide/svelte/icons/settings';
import ShieldCheck from '@lucide/svelte/icons/shield-check';
import ShoppingBag from '@lucide/svelte/icons/shopping-bag';
import Store from '@lucide/svelte/icons/store';
import Ticket from '@lucide/svelte/icons/ticket';
import User from '@lucide/svelte/icons/user';
import UserRoundCheck from '@lucide/svelte/icons/user-round-check';
import Users from '@lucide/svelte/icons/users';
import Wallet from '@lucide/svelte/icons/wallet';
import BriefcaseBusiness from '@lucide/svelte/icons/briefcase-business';
import type { AdminSidebarEntry } from '@/types/admin-sidebar';

/**
 * Admin sidebar tree, mirroring batamtix's `config/sidebar.php` (`admin` key)
 * and its `lang/id/sidebar.php` labels. `#` marks pages that don't exist in
 * gettix yet; only the dashboard is a real route.
 */
export function adminMenu(currentUrl: string): AdminSidebarEntry[] {
  return [
    { type: 'header', label: 'Menu Utama' },
    {
      label: 'Dasbor',
      icon: LayoutDashboard,
      href: '/dashboard',
      active: currentUrl.startsWith('/dashboard'),
    },
    { label: 'Statistik', icon: ChartPie, href: '#' },
    {
      label: 'Operasional',
      icon: Settings,
      children: [
        { label: 'Chat', href: '#' },
        { label: 'Laporan & Bantuan', href: '#' },
        { label: 'Check-in & Redeem', href: '#' },
      ],
    },

    { type: 'header', label: 'Katalog & Layanan' },
    {
      label: 'Rental Mobil',
      icon: Car,
      children: [
        { label: 'Kendaraan', href: '#' },
        { label: 'Pesanan Rental', href: '#' },
      ],
    },
    {
      label: 'Tur & Perjalanan',
      icon: BriefcaseBusiness,
      children: [
        { label: 'Paket Tur', href: '#' },
        { label: 'Pemesanan Tur', href: '#' },
      ],
    },
    {
      label: 'Hotel & Penginapan',
      icon: Building,
      children: [
        { label: 'Properti', href: '#' },
        { label: 'Tipe Kamar', href: '#' },
        { label: 'Paket Tarif', href: '#' },
        { label: 'Pemesanan Hotel', href: '#' },
      ],
    },

    { type: 'header', label: 'Penjualan & Keuangan' },
    { label: 'Pesanan', icon: ShoppingBag, href: '#' },
    { label: 'Transaksi', icon: Receipt, href: '#' },
    { label: 'Promo & Kupon', icon: Ticket, href: '#' },
    {
      label: 'Rekening',
      icon: Landmark,
      children: [
        { label: 'Semua Rekening', href: '#' },
        { label: 'Validasi Rekening', href: '#' },
      ],
    },
    {
      label: 'Penarikan Dana',
      icon: Wallet,
      children: [
        { label: 'Semua Penarikan Dana', href: '#' },
        { label: 'Validasi Penarikan Dana', href: '#' },
      ],
    },

    { type: 'header', label: 'Konten' },
    { label: 'Kategori Artikel', icon: Newspaper, href: '#' },
    { label: 'Banner', icon: ImagePlay, href: '#' },
    { label: 'Galeri Video', icon: Clapperboard, href: '#' },

    { type: 'header', label: 'Pengguna & Akses' },
    { label: 'Pengguna', icon: User, href: '#' },
    { label: 'Mitra', icon: Store, href: '#' },
    { label: 'Anggota Mitra', icon: Users, href: '#' },
    { label: 'Peran', icon: UserRoundCheck, href: '#' },
    { label: 'Izin', icon: ShieldCheck, href: '#' },

    { type: 'header', label: 'Sistem' },
    {
      label: 'Pengaturan',
      icon: Settings,
      children: [
        { label: 'Sistem', href: '#' },
        { label: 'Mata Uang', href: '#' },
        { label: 'Metode Pembayaran', href: '#' },
        { label: 'Dokumen Legal', href: '#' },
      ],
    },
    {
      label: 'Log Sistem',
      icon: ScrollText,
      children: [
        {
          label: 'Sistem',
          children: [
            { label: 'Browser', href: '#' },
            { label: 'Laravel', href: '#' },
          ],
        },
        { label: 'Payment Gateway', href: '#' },
        { label: 'WhatsApp', href: '#' },
        { label: 'Log Aktivitas Pengguna', href: '#' },
      ],
    },
  ];
}
