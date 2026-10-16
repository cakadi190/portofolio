<script lang="ts">
  import { page } from '@inertiajs/svelte';
import Link from '@/components/link.svelte';
  import AppBrand from '@/components/app-brand.svelte';
  import LanguageSwitcher from '@/components/language-switcher.svelte';
  import ThemeToggler from '@/components/theme-toggler.svelte';
  import { localizeHref, t } from '@/lib/i18n';

  interface MenuItem {
    name: string;
    href?: string;
    children?: { name: string; href: string }[];
  }

  const navbarMenu = $derived<MenuItem[]>([
    { name: t('nav.home'), href: '/' },
    { name: t('nav.portfolio'), href: '/portfolio' },
    { name: t('nav.speaking'), href: '/speaking' },
    { name: t('nav.services'), href: '/services' },
    {
      name: t('nav.about'),
      children: [
        { name: t('nav.profile'), href: '/about' },
        { name: t('nav.education'), href: '/education' },
        { name: t('nav.awards'), href: '/awards' },
        { name: t('nav.career'), href: '/career' },
      ],
    },
    {
      name: t('nav.reading'),
      children: [
        { name: t('nav.articles'), href: '/blog' },
        { name: t('nav.notes'), href: '/notes' },
      ],
    },
    { name: t('nav.contact'), href: '/contact' },
  ]);

  function isGroupActive(item: MenuItem): boolean {
    return item.children?.some((child) => localizeHref(child.href) === page.url) ?? false;
  }

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
    (
      document.querySelector('#offcanvas .btn-close') as HTMLElement | null
    )?.click();
  }
</script>

<div class="navbar p-0 d-block">
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
        <button
          type="button"
          class="btn-close"
          data-bs-dismiss="offcanvas"
          aria-label={t('nav.close')}
        ></button>
      </div>
    </div>
    <div class="offcanvas-body">
      <ul class="navbar-nav gap-2 justify-content-end flex-grow-1">
        {#each navbarMenu as item (item.name)}
          {#if item.children}
            <li class="nav-item dropdown">
              <button
                type="button"
                class={`nav-link dropdown-toggle ${isGroupActive(item) ? 'active' : ''}`}
                data-bs-toggle="dropdown"
                aria-expanded="false"
              >
                {item.name}
              </button>
              <ul class="dropdown-menu">
                {#each item.children as child (child.href)}
                  <li>
                    <Link
                      class={`dropdown-item ${localizeHref(child.href) === page.url ? 'active' : ''}`}
                      href={child.href}
                      onclick={closeOffcanvas}
                    >
                      {child.name}
                    </Link>
                  </li>
                {/each}
              </ul>
            </li>
          {:else if item.href}
            <li class="nav-item">
              <Link
                class={`nav-link ${localizeHref(item.href) === page.url ? 'active' : ''}`}
                href={item.href}
                onclick={closeOffcanvas}
              >
                {item.name}
              </Link>
            </li>
          {/if}
        {/each}
      </ul>
    </div>
  </div>
</div>

<nav
  bind:this={navbar}
  class="navbar navbar-expand-lg fixed-top navbar-light py-3"
>
  <div class="container">
    <AppBrand class="navbar-brand" href="/" height={32} />

    <div class="d-flex align-items-center gap-2 order-lg-last">
      <LanguageSwitcher />
      <ThemeToggler />
      <button
        class="navbar-toggler p-0 border-0"
        type="button"
        data-bs-toggle="offcanvas"
        data-bs-target="#offcanvas"
        aria-controls="offcanvas"
        aria-expanded="false"
        aria-label={t('nav.open')}
      >
        <span class="navbar-toggler-icon"></span>
      </button>
    </div>

    <div class="collapse navbar-collapse" id="navbarMain">
      <ul class="navbar-nav gap-2 justify-content-end flex-grow-1 me-3">
        {#each navbarMenu as item (item.name)}
          {#if item.children}
            <li class="nav-item dropdown">
              <button
                type="button"
                class={`nav-link dropdown-toggle ${isGroupActive(item) ? 'active' : ''}`}
                data-bs-toggle="dropdown"
                aria-expanded="false"
              >
                {item.name}
              </button>
              <ul class="dropdown-menu">
                {#each item.children as child (child.href)}
                  <li>
                    <Link
                      class={`dropdown-item ${localizeHref(child.href) === page.url ? 'active' : ''}`}
                      href={child.href}
                    >
                      {child.name}
                    </Link>
                  </li>
                {/each}
              </ul>
            </li>
          {:else if item.href}
            <li class="nav-item">
              <Link
                class={`nav-link ${localizeHref(item.href) === page.url ? 'active' : ''}`}
                href={item.href}
              >
                {item.name}
              </Link>
            </li>
          {/if}
        {/each}
      </ul>
    </div>
  </div>
</nav>
