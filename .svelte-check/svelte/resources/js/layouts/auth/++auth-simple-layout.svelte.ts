///<reference types="svelte" />
;
import { Link } from '@inertiajs/svelte';
import type { Snippet } from 'svelte';
import AppLogoIcon from '@/components/app-logo-icon.svelte';
import { home } from '@/routes';

;type $$ComponentProps =  {
    title?: string;
    description?: string;
    children?: Snippet;
  };function $$render() {

  
  
  
  

  let {
    title = '',
    description = '',
    children,
  }:/*Ωignore_startΩ*/$$ComponentProps/*Ωignore_endΩ*/ = $props();
;
async () => {

  { svelteHTML.createElement("div", {  "class":`auth-layout d-flex flex-column align-items-center justify-content-center gap-4 p-4 p-md-5`,});
   { svelteHTML.createElement("div", { "class":`auth-layout-inner w-100`,});
     { svelteHTML.createElement("div", { "class":`card card-body rounded-4 p-4 d-flex flex-column gap-4`,});
       { svelteHTML.createElement("div", { "class":`d-flex flex-column align-items-center gap-3`,});
         { const $$_kniL4C = __sveltets_2_ensureComponent(Link); new $$_kniL4C({ target: __sveltets_2_any(), props: {     children:() => { return __sveltets_2_any(0); },"href":home(),"class":`d-flex flex-column align-items-center gap-2 fw-medium`,}});
           { svelteHTML.createElement("div", {   "class":`auth-layout-logo mb-1 d-flex align-items-center justify-content-center rounded-2`,});
             { const $$_nocIogoLppA6C = __sveltets_2_ensureComponent(AppLogoIcon); new $$_nocIogoLppA6C({ target: __sveltets_2_any(), props: {  "class":`auth-layout-logo-icon text-body`,}});}
           }
           { svelteHTML.createElement("span", { "class":`visually-hidden`,});title; }
         Link}
         { svelteHTML.createElement("div", { "class":`d-flex flex-column gap-2 text-center`,});
           { svelteHTML.createElement("h1", { "class":`fs-3 mb-0 fw-medium`,});title; }
           { svelteHTML.createElement("p", { "class":`text-center mb-0 small text-muted`,});
            description;
           }
         }
       }
      ;__sveltets_2_ensureSnippet(children?.());
     }
   }
 }


};
return { props: {} as any as $$ComponentProps, exports: {}, bindings: __sveltets_$$bindings(''), slots: {}, events: {} }}
const AuthSimpleLayout__SvelteComponent_ = __sveltets_2_fn_component($$render());
/*Ωignore_startΩ*/type AuthSimpleLayout__SvelteComponent_ = ReturnType<typeof AuthSimpleLayout__SvelteComponent_>;
/*Ωignore_endΩ*/export default AuthSimpleLayout__SvelteComponent_;