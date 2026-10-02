<script lang="ts">
  import AppHead from '@/components/app-head.svelte';
  import BlogHomeSection from '@/components/home/blog-home-section.svelte';
  import ElkuslaSection from '@/components/home/elkusla-section.svelte';
  import HeaderHome from '@/components/home/header-home.svelte';
  import PortfolioHomeSection from '@/components/home/portfolio-home-section.svelte';

  type Portfolio = {
    name: string;
    slug: string;
    image: string;
    shortDesc: string | null;
    categories: { name: string; color: string | null }[];
    technologies: string[];
  };

  type Post = {
    title: string;
    slug: string;
    excerpt: string | null;
    coverImage: string | null;
    categories: { name: string; color: string | null }[];
    tags: string[];
  };

  let { portfolios = [], posts = [] }: { portfolios?: Portfolio[]; posts?: Post[] } = $props();
</script>

<AppHead title="Beranda" />

<div id="homepage">
  <HeaderHome />
  <ElkuslaSection />
  <PortfolioHomeSection {portfolios} />
  <BlogHomeSection {posts} />
</div>
