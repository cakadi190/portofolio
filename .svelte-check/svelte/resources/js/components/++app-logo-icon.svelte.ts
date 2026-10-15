///<reference types="svelte" />
;
import iconColor from '../../images/brands/icon-color.svg?no-inline';
import iconWhite from '../../images/brands/icon-white.svg?no-inline';

;type $$ComponentProps =  {
    class?: string;
    height?: number | string;
    [key: string]: unknown;
  };function $$render() {

  
  

  let {
    class: className = '',
    height = 32,
    ...rest
  }:/*Ωignore_startΩ*/$$ComponentProps/*Ωignore_endΩ*/ = $props();
;
async () => {

 { svelteHTML.createElement("span", {  "class":`d-inline-flex app-logo-icon ${className}`,...rest,});
   { svelteHTML.createElement("img", {            "class":`site-logo logo-light`,"src":iconColor,"alt":`Logo Catatan Cak Adi`,height,"style":`aspect-ratio: 1`,"decoding":`sync`,});}
   { svelteHTML.createElement("img", {            "class":`site-logo logo-dark`,"src":iconWhite,"alt":`Logo Catatan Cak Adi`,height,"style":`aspect-ratio: 1`,"decoding":`sync`,});}
 }
};
return { props: {} as any as $$ComponentProps, exports: {}, bindings: __sveltets_$$bindings(''), slots: {}, events: {} }}
const AppLogoIcon__SvelteComponent_ = __sveltets_2_fn_component($$render());
/*Ωignore_startΩ*/type AppLogoIcon__SvelteComponent_ = ReturnType<typeof AppLogoIcon__SvelteComponent_>;
/*Ωignore_endΩ*/export default AppLogoIcon__SvelteComponent_;