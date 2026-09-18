import type { LinkComponentBaseProps } from '@inertiajs/core';
import type { Component, SvelteComponent } from 'svelte';

type AdminSidebarIcon =
  | Component<{ class?: string }>
  | (new (...args: any[]) => SvelteComponent<{ class?: string }>);

/** Flat section label rendered as a sibling of the items below it, not a parent. */
export type AdminSidebarHeader = {
  type: 'header';
  label: string;
};

export type AdminSidebarMenuItem = {
  type?: 'item';
  label: string;
  icon?: AdminSidebarIcon;
  href?: NonNullable<LinkComponentBaseProps['href']>;
  active?: boolean;
  children?: AdminSidebarEntry[];
};

export type AdminSidebarEntry = AdminSidebarHeader | AdminSidebarMenuItem;
