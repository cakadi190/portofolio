<script lang="ts">
  import { Link, page } from '@inertiajs/svelte';
  import AppBrand from '@/components/app-brand.svelte';
  import ThemeToggler from '@/components/theme-toggler.svelte';

  const navbarMenu = [
    { name: 'Beranda', href: '/' },
    { name: 'Portofolio', href: '/portofolio' },
    { name: 'Tentang Saya', href: '/tentang/saya' },
    { name: 'Pendidikan & Organisasi', href: '/pendidikan' },
    { name: 'Penghargaan', href: '/penghargaan' },
    { name: 'Karir', href: '/karir' },
    { name: 'Blog', href: '/blog' },
    { name: 'Kontak Saya', href: '/kontak' },
  ];

  let navbar = $state<HTMLElement>();

  $effect(() => {
    function onScroll(): void {
      const scrolled = window.scrollY >= 50;

      navbar?.classList.toggle('bg-body-rgb', scrolled);
      navbar?.classList.toggle('border-bottom', scrolled);

      if (scrolled) {
        navbar?.style.setProperty('--neo-bg-opacity', '.75');
        navbar?.style.setProperty('backdrop-filter', 'blur(1rem)');
      } else {
        navbar?.style.removeProperty('--neo-bg-opacity');
        navbar?.style.removeProperty('backdrop-filter');
      }
    }

    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });

    return () => window.removeEventListener('scroll', onScroll);
  });

  function closeOffcanvas(): void {
    (document.querySelector('#offcanvas .btn-close') as HTMLElement | null)?.click();
  }
</script>

<div
  class="offcanvas offcanvas-start"
  tabindex="-1"
  id="offcanvas"
  role="dialog"
  aria-labelledby="offcanvasLabel"
>
  <div class="offcanvas-header align-items-center">
    <AppBrand class="offcanvas-title" href="/" height={28} />
    <div class="d-flex align-items-center gap-2 ms-auto">
      <ThemeToggler />
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"
      ></button>
    </div>
  </div>
  <div class="offcanvas-body">
    <ul class="navbar-nav gap-2 justify-content-end flex-grow-1">
      {#each navbarMenu as item (item.name)}
        <li class="nav-item">
          <Link
            class={`nav-link ${item.href === page.url ? 'active' : ''}`}
            href={item.href}
            onclick={closeOffcanvas}
          >
            {item.name}
          </Link>
        </li>
      {/each}
    </ul>
  </div>
</div>

<nav bind:this={navbar} class="navbar navbar-expand-lg fixed-top navbar-light py-3">
  <div class="container">
    <AppBrand class="navbar-brand" href="/" height={32} />

    <div class="d-flex align-items-center gap-2 order-lg-last">
      <ThemeToggler />
      <button
        class="navbar-toggler p-0 border-0"
        type="button"
        data-bs-toggle="offcanvas"
        data-bs-target="#offcanvas"
        aria-controls="offcanvas"
        aria-expanded="false"
        aria-label="Buka navigasi"
      >
        <span class="navbar-toggler-icon"></span>
      </button>
    </div>

    <div class="collapse navbar-collapse" id="navbarMain">
      <ul class="navbar-nav gap-2 justify-content-end flex-grow-1 me-3">
        {#each navbarMenu as item (item.name)}
          <li class="nav-item">
            <Link
              class={`nav-link ${item.href === page.url ? 'active' : ''}`}
              href={item.href}
            >
              {item.name}
            </Link>
          </li>
        {/each}
      </ul>
    </div>
  </div>
</nav>
