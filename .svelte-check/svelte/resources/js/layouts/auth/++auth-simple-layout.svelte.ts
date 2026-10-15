///<reference types="svelte" />
;
import { Link } from '@inertiajs/svelte';
import type { Snippet } from 'svelte';
import ThemeToggler from '@/components/theme-toggler.svelte';
import AppLogoIcon from '@/components/app-logo-icon.svelte';
import { home } from '@/wayfinder/routes';

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

 { svelteHTML.createElement("div", { "class":`auth-layout auth-layout-simple`,});
   { svelteHTML.createElement("div", { "class":`auth-layout-theme-toggler`,});
     { const $$_relggoTemehT2C = __sveltets_2_ensureComponent(ThemeToggler); new $$_relggoTemehT2C({ target: __sveltets_2_any(), props: {}});}
   }
   { svelteHTML.createElement("div", { "class":`auth-layout-container`,});
     { svelteHTML.createElement("div", { "class":`auth-layout-body`,});
       { svelteHTML.createElement("div", { "class":`auth-layout-header`,});
         { const $$_kniL4C = __sveltets_2_ensureComponent(Link); new $$_kniL4C({ target: __sveltets_2_any(), props: {   children:() => { return __sveltets_2_any(0); },"href":home(),"class":`auth-layout-brand`,}});
           { svelteHTML.createElement("div", { "class":`auth-layout-logo`,});
             { const $$_nocIogoLppA6C = __sveltets_2_ensureComponent(AppLogoIcon); new $$_nocIogoLppA6C({ target: __sveltets_2_any(), props: {  "class":`auth-layout-logo-icon`,}});}
           }
           { svelteHTML.createElement("span", { "class":`visually-hidden`,});title; }
         Link}
         { svelteHTML.createElement("div", { "class":`auth-layout-heading`,});
           { svelteHTML.createElement("h1", { "class":`auth-layout-title`,});title; }
           { svelteHTML.createElement("p", { "class":`auth-layout-description`,});
            description;
           }
         }
       }
      ;__sveltets_2_ensureSnippet(children?.());
     }

     { svelteHTML.createElement("div", { "class":`auth-layout-footer`,});
          { const $$_kniL3C = __sveltets_2_ensureComponent(Link); new $$_kniL3C({ target: __sveltets_2_any(), props: { children:() => { return __sveltets_2_any(0); },"href":`/`,}});   Link}
     }
   }
 }


};
return { props: {} as any as $$ComponentProps, exports: {}, bindings: __sveltets_$$bindings(''), slots: {}, events: {} }}
const AuthSimpleLayout__SvelteComponent_ = __sveltets_2_fn_component($$render());
/*Ωignore_startΩ*/type AuthSimpleLayout__SvelteComponent_ = ReturnType<typeof AuthSimpleLayout__SvelteComponent_>;
/*Ωignore_endΩ*/export default AuthSimpleLayout__SvelteComponent_;