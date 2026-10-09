<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import BlogHomeSection from '@/components/home/blog-home-section.svelte';
  import ElkuslaSection from '@/components/home/elkusla-section.svelte';
  import HeaderHome from '@/components/home/header-home.svelte';
  import PortfolioHomeSection from '@/components/home/portfolio-home-section.svelte';
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

  type Speaking = {
    id: number;
    title: string;
    organizer: string;
    roleLabel: string;
    formatLabel: string;
    location: string | null;
    startsAt: string;
    poster: string | null;
  };

  let {
    portfolios = [],
    speakings = [],
    posts = [],
  }: { portfolios?: Portfolio[]; speakings?: Speaking[]; posts?: Post[] } = $props();
</script>

<AppHead title="Beranda" />

<div id="homepage">
  <HeaderHome />
  <ElkuslaSection />
  <PortfolioHomeSection {portfolios} />
  <SpeakingHomeSection {speakings} />
  <BlogHomeSection {posts} />
</div>
