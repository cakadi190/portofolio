<script lang="ts">
  import { t } from '@/lib/i18n';
  import type { Engagement } from '@/components/speaking-item.svelte';
  import AppHead from '@/components/app-head.svelte';
  import BlogHomeSection from '@/components/home/blog-home-section.svelte';
  import ElkuslaSection from '@/components/home/elkusla-section.svelte';
  import HeaderHome from '@/components/home/header-home.svelte';
  import PortfolioHomeSection from '@/components/home/portfolio-home-section.svelte';
  import StatsHomeSection from '@/components/home/stats-home-section.svelte';
  import SpeakingHomeSection from '@/components/home/speaking-home-section.svelte';

  type Portfolio = {
    name: string;
    slug: string;
    image: string;
    shortDesc: string | null;
    services: { name: string; color: string | null }[];
    technologies: string[];
  };

  type Post = {
    title: string;
    slug: string;
    excerpt: string | null;
    coverImage: string | null;
    categories: { name: string; color: string | null }[];
    tags: string[];
    author: string | null;
  };

  type Stat = { value: number; suffix: string; label: string };

  let {
    stats = [],
    portfolios = [],
    speakings = [],
    posts = [],
  }: { stats?: Stat[]; portfolios?: Portfolio[]; speakings?: Engagement[]; posts?: Post[] } = $props();
</script>

<AppHead title={t('home.title')} brandFirst />

<div id="homepage">
  <HeaderHome />
  <StatsHomeSection {stats} />
  <ElkuslaSection />
  <PortfolioHomeSection {portfolios} />
  <SpeakingHomeSection {speakings} />
  <BlogHomeSection {posts} />
</div>
