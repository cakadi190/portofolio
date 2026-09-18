<script lang="ts">
  import { Link } from '@inertiajs/svelte';
  import Circle from '@lucide/svelte/icons/circle';
  import ChevronDown from '@lucide/svelte/icons/chevron-down';
  import type { AdminSidebarEntry } from '@/types/admin-sidebar';
  import {
    FLOATING_MENU_GAP,
    FLOATING_MENU_VIEWPORT_PADDING,
    TRANSITION_DURATION_MS,
    useAdminSidebarState,
  } from './sidebar-state.svelte';

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

  const isFlyoutMode = $derived(
    level === 1 && sidebar.isDesktop && sidebar.collapsed,
  );

  /**
   * Anchors a level-1 flyout beside its trigger: dropping down from the
   * trigger's top edge, or up from its bottom edge when it doesn't fit below.
   * The anchored edge stays fixed so a nested accordion grows away from the
   * viewport edge. Counterpart of batamtix's SidebarFloatingMenu.
   */
  function computeFlyoutPosition(item: HTMLElement, id: string): void {
    const trigger = item.querySelector<HTMLElement>(':scope > .sm-link');
    const panel = item.querySelector<HTMLElement>(':scope > .sm-submenu');

    if (!trigger || !panel) {
      return;
    }

    const rect = trigger.getBoundingClientRect();
    const viewportHeight = window.innerHeight;
    const spaceBelow = viewportHeight - rect.top - FLOATING_MENU_VIEWPORT_PADDING;
    const spaceAbove = rect.bottom - FLOATING_MENU_VIEWPORT_PADDING;
    const dropUp = panel.scrollHeight > spaceBelow && spaceAbove > spaceBelow;
    const left = `left: ${rect.right + FLOATING_MENU_GAP}px;`;

    sidebar.floatingLayout[id] = {
      up: dropUp,
      style: dropUp
        ? `${left} top: auto; bottom: ${viewportHeight - rect.bottom}px; max-height: ${spaceAbove}px; overflow-y: auto;`
        : `${left} bottom: auto; top: ${rect.top}px; max-height: ${spaceBelow}px; overflow-y: auto;`,
    };
  }

  function openFlyout(item: HTMLElement, id: string): void {
    sidebar.openFloating(id, () => computeFlyoutPosition(item, id));
  }

  function onPointerEnter(event: PointerEvent, id: string, hasChildren: boolean): void {
    if (
      !isFlyoutMode ||
      !hasChildren ||
      event.pointerType !== 'mouse' ||
      Date.now() < sidebar.floatingSuspendedUntil
    ) {
      return;
    }

    if (sidebar.floatingId === id) {
      sidebar.cancelFloatingClose();
      return;
    }

    openFlyout(event.currentTarget as HTMLElement, id);
  }

  function onPointerLeave(event: PointerEvent, id: string): void {
    if (isFlyoutMode && event.pointerType === 'mouse') {
      sidebar.scheduleFloatingClose(id);
    }
  }

  function onToggleClick(event: MouseEvent, id: string): void {
    if (isFlyoutMode) {
      const item = (event.currentTarget as HTMLElement).closest<HTMLElement>('.sm-item');

      if (sidebar.floatingId === id) {
        sidebar.closeFloating();
      } else if (item) {
        openFlyout(item, id);
      }

      return;
    }

    sidebar.toggleBranch(id);

    // A nested branch changing height inside an open flyout: re-clamp now and
    // once the accordion transition settles.
    if (sidebar.floatingId !== null) {
      sidebar.repositionFloating();
      window.setTimeout(() => sidebar.repositionFloating(), TRANSITION_DURATION_MS);
    }
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
        class:sm-item--floating-open={isFlyoutMode &&
          sidebar.floatingId === id}
        class:sm-item--floating-up={isFlyoutMode &&
          sidebar.floatingLayout[id]?.up}
        onpointerenter={(event) => onPointerEnter(event, id, hasChildren)}
        onpointerleave={(event) => onPointerLeave(event, id)}
      >
        {#if hasChildren}
          <button
            type="button"
            class="sm-link"
            aria-expanded={isFlyoutMode ? sidebar.floatingId === id : open}
            onclick={(event) => onToggleClick(event, id)}
          >
            <span class="sm-icon">
              {#if entry.icon}
                <entry.icon />
              {:else}
                <Circle
                  size={16}
                  class={entry.active ? 'sm-icon--filled' : ''}
                  aria-hidden="true"
                />
              {/if}
            </span>
            <span class="sm-label">{entry.label}</span>
            <span class="sm-arrow" aria-hidden="true"
              ><ChevronDown size={16} /></span
            >
          </button>
          <div
            class="sm-submenu"
            class:sm-submenu--open={open}
            style={isFlyoutMode ? sidebar.floatingLayout[id]?.style : undefined}
          >
            <div class="sm-submenu-inner">
              <svelte:self
                entries={entry.children ?? []}
                level={level + 1}
                parentId={id}
              />
            </div>
          </div>
        {:else}
          <Link
            href={entry.href ?? '#'}
            class="sm-link"
            aria-current={entry.active ? 'page' : undefined}
          >
            <span class="sm-icon">
              {#if entry.icon}
                <entry.icon />
              {:else}
                <Circle
                  size={16}
                  class={entry.active ? 'sm-icon--filled' : ''}
                  aria-hidden="true"
                />
              {/if}
            </span>
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
