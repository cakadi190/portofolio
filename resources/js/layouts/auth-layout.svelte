<script lang="ts">
  import type { Snippet } from 'svelte';
  import AuthLayout from '@/layouts/auth/auth-split-layout.svelte';
  import { Toaster } from 'svelte-french-toast';

  let {
    title = '',
    description = '',
    children,
  }: {
    title?: string;
    description?: string;
    children?: Snippet;
  } = $props();
</script>

<AuthLayout {title} {description}>
  {@render children?.()}

  <Toaster position="top-right" toastOptions={{ duration: 4000 }} />
</AuthLayout>
