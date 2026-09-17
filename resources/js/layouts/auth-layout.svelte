<script lang="ts">
  import type { Snippet } from 'svelte';
  import AuthLayout from '@/layouts/auth/auth-split-layout.svelte';
  import { FlatToast, ToastContainer } from 'svelte-toasts';

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

  <ToastContainer let:data>
    <FlatToast {data} />
  </ToastContainer>
</AuthLayout>
