<script lang="ts">
  import KeyRound from '@lucide/svelte/icons/key-round';
  import User from '@lucide/svelte/icons/user';
  import { Link } from '@inertiajs/svelte';
  import { edit as editProfile } from '@/wayfinder/routes/profile';
  import { edit as editSecurity } from '@/wayfinder/routes/security';

  let { current }: { current: 'profile' | 'security' } = $props();
</script>

<Link href={editProfile().url} class={['nav-link', current === 'profile' && 'active']}>
  <User size={16} aria-hidden="true" />
  <span>Profil</span>
</Link>
<Link href={editSecurity().url} class={['nav-link', current === 'security' && 'active']}>
  <KeyRound size={16} aria-hidden="true" />
  <span>Keamanan</span>
</Link>
