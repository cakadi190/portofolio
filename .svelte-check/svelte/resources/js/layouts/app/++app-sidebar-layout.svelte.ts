///<reference types="svelte" />
import type { Snippet } from 'svelte';
import type { BreadcrumbItem } from '@/types';

type $$ComponentProps = {
  breadcrumbs?: BreadcrumbItem[];
  children?: Snippet;
};
function $$render() {
  let { children }: /*Ωignore_startΩ*/ $$ComponentProps /*Ωignore_endΩ*/ =
    $props();
  async () => {
    __sveltets_2_ensureSnippet(children?.());
  };
  return {
    props: {} as any as $$ComponentProps,
    exports: {},
    bindings: __sveltets_$$bindings(''),
    slots: {},
    events: {},
  };
}
const AppSidebarLayout__SvelteComponent_ =
  __sveltets_2_fn_component($$render());
/*Ωignore_startΩ*/ type AppSidebarLayout__SvelteComponent_ = ReturnType<
  typeof AppSidebarLayout__SvelteComponent_
>;
/*Ωignore_endΩ*/ export default AppSidebarLayout__SvelteComponent_;
