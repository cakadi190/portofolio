///<reference types="svelte" />
;
import type { Snippet } from 'svelte';
import BackToTop from '@/components/back-to-top.svelte';
import SiteFooter from '@/components/site-footer.svelte';
import SiteNavbar from '@/components/site-navbar.svelte';
import type { BreadcrumbItem } from '@/types';

;type $$ComponentProps =  {
    breadcrumbs?: BreadcrumbItem[];
    children?: Snippet;
  };function $$render() {

  
  
  
  
  

  let {
    children,
  }:/*Ωignore_startΩ*/$$ComponentProps/*Ωignore_endΩ*/ = $props();
;
async () => {

 { svelteHTML.createElement("div", { "id":`site-shell`,});
   { const $$_rabvaNetiS1C = __sveltets_2_ensureComponent(SiteNavbar); new $$_rabvaNetiS1C({ target: __sveltets_2_any(), props: {}});}

   { svelteHTML.createElement("main", { "id":`main-content`,});
    ;__sveltets_2_ensureSnippet(children?.());
   }

   { const $$_retooFetiS1C = __sveltets_2_ensureComponent(SiteFooter); new $$_retooFetiS1C({ target: __sveltets_2_any(), props: {}});}
   { const $$_poToTkcaB1C = __sveltets_2_ensureComponent(BackToTop); new $$_poToTkcaB1C({ target: __sveltets_2_any(), props: {}});}
 }
};
return { props: {} as any as $$ComponentProps, exports: {}, bindings: __sveltets_$$bindings(''), slots: {}, events: {} }}
const AppSidebarLayout__SvelteComponent_ = __sveltets_2_fn_component($$render());
/*Ωignore_startΩ*/type AppSidebarLayout__SvelteComponent_ = ReturnType<typeof AppSidebarLayout__SvelteComponent_>;
/*Ωignore_endΩ*/export default AppSidebarLayout__SvelteComponent_;