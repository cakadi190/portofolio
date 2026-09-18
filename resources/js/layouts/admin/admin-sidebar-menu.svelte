<script lang="ts">
  import { Link } from '@inertiajs/svelte';
  import ChevronDown from '@lucide/svelte/icons/chevron-down';
  import type { AdminSidebarEntry } from '@/types/admin-sidebar';
  import { useAdminSidebarState } from './sidebar-state.svelte';

  let {
    entries,
    level = 1,
    parentId = 'root',
  }: {
    entries: AdminSidebarEntry[];
    level?: number;
    parentId?: string;
  } = $props();

  const sidebar = useAdminSidebarState();

  function matchesSearch(entry: AdminSidebarEntry, query: string): boolean {
    if (entry.type === 'header') {
      return entry.label.toLowerCase().includes(query);
    }

    if (entry.label.toLowerCase().includes(query)) {
      return true;
    }

    return (entry.children ?? []).some((child) => matchesSearch(child, query));
  }

  const query = $derived(sidebar.searchQuery.trim().toLowerCase());
  const visibleEntries = $derived(
    query ? entries.filter((entry) => matchesSearch(entry, query)) : entries,
  );

  /**
   * Collapsed rail only: the level-1 flyout is `position: fixed` (the scroll
   * container would clip it), so pin it beside the hovered item and keep it
   * inside the viewport. Counterpart of batamtix's SidebarFloatingMenu.
   */
  function positionFlyout(event: Event): void {
    if (level !== 1 || !sidebar.isDesktop || !sidebar.collapsed) {
      return;
    }

    const item = event.currentTarget as HTMLElement;
    const panel = item.querySelector<HTMLElement>(':scope > .sm-submenu');

    if (!panel) {
      return;
    }

    const rect = item.getBoundingClientRect();
    const margin = 8;
    const maxTop = window.innerHeight - panel.offsetHeight - margin;

    item.style.setProperty('--sm-flyout-left', `${rect.right}px`);
    item.style.setProperty(
      '--sm-flyout-top',
      `${Math.max(margin, Math.min(rect.top, maxTop))}px`,
    );
  }

  function branchId(index: number): string {
    return `${parentId}-${level}-${index}`;
  }

  function isOpen(id: string, hasActiveChild: boolean): boolean {
    return sidebar.searching || sidebar.isBranchOpen(id) || hasActiveChild;
  }

  function hasActiveDescendant(entry: AdminSidebarEntry): boolean {
    if (entry.type === 'header') {
      return false;
    }

    if (entry.active) {
      return true;
    }

    return (entry.children ?? []).some((child) => hasActiveDescendant(child));
  }
</script>

<ul class="sm-menu" class:sm-menu--nested={level > 1}>
  {#each visibleEntries as entry, index (branchId(index))}
    {#if entry.type === 'header'}
      <li class="sm-header" role="presentation">
        <span>{entry.label}</span>
      </li>
    {:else}
      {@const id = branchId(index)}
      {@const hasChildren = (entry.children?.length ?? 0) > 0}
      {@const childActive = hasActiveDescendant(entry)}
      {@const open = hasChildren && isOpen(id, childActive)}
      <li
        class="sm-item"
        class:sm-item--active={entry.active}
        class:sm-item--open={open}
        class:sm-item--child-active={childActive && !entry.active}
        data-level={level}
        onmouseenter={positionFlyout}
        onfocusin={positionFlyout}
      >
        {#if hasChildren}
          <button
            type="button"
            class="sm-link"
            aria-expanded={open}
            onclick={() => sidebar.toggleBranch(id)}
          >
            {#if entry.icon}
              <span class="sm-icon"><entry.icon /></span>
            {/if}
            <span class="sm-label">{entry.label}</span>
            <span class="sm-arrow" aria-hidden="true"
              ><ChevronDown size={16} /></span
            >
          </button>
          <div class="sm-submenu" class:sm-submenu--open={open}>
            <div class="sm-submenu-inner">
              <svelte:self
                entries={entry.children ?? []}
                level={level + 1}
                {parentId}
              />
            </div>
          </div>
        {:else}
          <Link
            href={entry.href ?? '#'}
            class="sm-link"
            aria-current={entry.active ? 'page' : undefined}
          >
            {#if entry.icon}
              <span class="sm-icon"><entry.icon /></span>
            {/if}
            <span class="sm-label">{entry.label}</span>
          </Link>
        {/if}
      </li>
    {/if}
  {/each}

  {#if level === 1 && sidebar.searching && visibleEntries.length === 0}
    <li class="sidebar-search-empty" role="status">Tidak ada menu yang cocok.</li>
  {/if}
</ul>
