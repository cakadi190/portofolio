///<reference types="svelte" />
;
import type { Snippet } from 'svelte';
import AppLayout from '@/layouts/app/app-sidebar-layout.svelte';
import type { BreadcrumbItem } from '@/types';
import { Toaster } from 'svelte-french-toast';

;type $$ComponentProps =  {
    breadcrumbs?: BreadcrumbItem[];
    children?: Snippet;
  };function $$render() {

  
  
  
  

  let {
    breadcrumbs = [],
    children,
  }:/*Ωignore_startΩ*/$$ComponentProps/*Ωignore_endΩ*/ = $props();
;
async () => {

 { const $$_tuoyaLppA0C = __sveltets_2_ensureComponent(AppLayout); new $$_tuoyaLppA0C({ target: __sveltets_2_any(), props: {children:() => { return __sveltets_2_any(0); },breadcrumbs,}});
  ;__sveltets_2_ensureSnippet(children?.());

   { const $$_retsaoT1C = __sveltets_2_ensureComponent(Toaster); new $$_retsaoT1C({ target: __sveltets_2_any(), props: {    "position":`top-right`,"toastOptions":{ duration: 4000 },}});}
 AppLayout}
};
return { props: {} as any as $$ComponentProps, exports: {}, bindings: __sveltets_$$bindings(''), slots: {}, events: {} }}
const AppLayout__SvelteComponent_ = __sveltets_2_fn_component($$render());
/*Ωignore_startΩ*/type AppLayout__SvelteComponent_ = ReturnType<typeof AppLayout__SvelteComponent_>;
/*Ωignore_endΩ*/export default AppLayout__SvelteComponent_;