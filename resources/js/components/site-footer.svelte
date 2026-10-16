<script lang="ts">
  import Icon from '@iconify/svelte';
  import Link from '@/components/link.svelte';
  import { t } from '@/lib/i18n';
  import { me as about, site } from '@/wayfinder/routes/about';
  import { index as awards } from '@/wayfinder/routes/awards';
  import { index as blog } from '@/wayfinder/routes/blog';
  import { index as career } from '@/wayfinder/routes/career';
  import { index as disclaimer } from '@/wayfinder/routes/disclaimer';
  import { index as guestbook } from '@/wayfinder/routes/guestbook';
  import { index as notesIndex } from '@/wayfinder/routes/notes';
  import { index as statistic } from '@/wayfinder/routes/statistic';
  import { index as education } from '@/wayfinder/routes/education';
  import { index as portfolios } from '@/wayfinder/routes/portfolios';
  import { index as coffeeShops } from '@/wayfinder/routes/resources/coffee-shops';
  import { index as devCommunities } from '@/wayfinder/routes/resources/dev-communities';
  import { index as programmerChannels } from '@/wayfinder/routes/resources/programmer-channels';
  import { index as services } from '@/wayfinder/routes/services';
  import { index as clarifications } from '@/wayfinder/routes/clarifications';
  import { index as speaking } from '@/wayfinder/routes/speaking';

  const year = new Date().getFullYear();

  const socials = [
    {
      label: "Cak Adi's Facebook",
      href: 'https://www.facebook.com/cakadi190',
      icon: 'fa6-brands:facebook',
    },
    {
      label: "Cak Adi's Twitter",
      href: 'https://www.twitter.com/cakadi190',
      icon: 'fa6-brands:twitter',
    },
    {
      label: "Cak Adi's Figma",
      href: 'https://www.figma.com/@cakadi190',
      icon: 'fa6-brands:figma',
    },
    {
      label: "Cak Adi's Instagram",
      href: 'https://www.instagram.com/cakadi190',
      icon: 'fa6-brands:instagram',
    },
    {
      label: "Cak Adi's Linkedin",
      href: 'https://www.linkedin.com/in/cakadi190',
      icon: 'fa6-brands:linkedin-in',
    },
  ];

  const workLinks = $derived([
    { label: t('footer.education'), href: education().url },
    { label: t('footer.awards'), href: awards().url },
    { label: t('footer.speaking'), href: speaking().url },
    { label: t('footer.skills'), href: `${about().url}#skills` },
    { label: t('footer.services'), href: services().url },
    { label: t('footer.portfolio'), href: portfolios().url },
    { label: t('footer.career'), href: career().url },
  ]);

  const resourceLinks = $derived([
    { label: t('footer.blog'), href: blog().url },
    { label: t('footer.notes'), href: notesIndex().url },
    { label: t('footer.guestbook'), href: guestbook().url },
    { label: t('footer.statistic'), href: statistic().url },
    { label: t('footer.clarifications'), href: clarifications().url },
    { label: t('footer.channels'), href: programmerChannels().url },
    { label: t('footer.communities'), href: devCommunities().url },
    { label: t('footer.coffee'), href: coffeeShops().url },
  ]);

  const otherLinks = $derived([
    { label: t('footer.disclaimer'), href: disclaimer().url },
    { label: t('footer.credits'), href: '#' },
    { label: t('footer.donate.trakteer'), href: '#' },
    { label: t('footer.donate.kofi'), href: '#' },
  ]);
</script>

<footer class="site-footer border-top py-4 mt-auto">
  <div class="container py-4">
    <div class="row gy-5">
      <div class="col-md-6">
        <h2 class="h6">{t('footer.about.title')}</h2>
        <p class="opacity-75">{t('footer.about.text')}</p>
        <div class="d-flex gap-3 mt-4">
          {#each socials as social (social.href)}
            <a
              aria-label={social.label}
              class="social-link"
              href={social.href}
              target="_blank"
              rel="noopener"
            >
              <Icon icon={social.icon} width={20} height={20} />
            </a>
          {/each}
        </div>
      </div>

      <div class="col-lg-2 col-6">
        <h2 class="h6">{t('footer.work.title')}</h2>
        <ul class="list-unstyled">
          {#each workLinks as link (link.label)}
            <li><Link href={link.href}>{link.label}</Link></li>
          {/each}
        </ul>
      </div>

      <div class="col-lg-2 col-6">
        <h2 class="h6">{t('footer.resources.title')}</h2>
        <ul class="list-unstyled">
          {#each resourceLinks as link (link.label)}
            <li><Link href={link.href}>{link.label}</Link></li>
          {/each}
        </ul>
      </div>

      <div class="col-md-2">
        <h2 class="h6">{t('footer.other.title')}</h2>
        <ul class="list-unstyled">
          {#each otherLinks as link (link.label)}
            <li><Link href={link.href}>{link.label}</Link></li>
          {/each}
        </ul>
      </div>
    </div>
  </div>

  <div class="container mt-3">
    <div class="row">
      <div class="col-md-6 text-center text-lg-start">
        <p class="mb-0">{t('footer.copyright', { year })}</p>
      </div>
      <div class="col-md-6 text-center text-lg-end">
        <p class="mb-0">{t('footer.madewith')}</p>
      </div>
    </div>
  </div>
</footer>
