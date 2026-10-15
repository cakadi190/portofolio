///<reference types="svelte" />
;
import type { Snippet } from 'svelte';
import AuthLayout from '@/layouts/auth/auth-split-layout.svelte';
import { Toaster } from 'svelte-french-toast';

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

 { const $$_tuoyaLhtuA0C = __sveltets_2_ensureComponent(AuthLayout); new $$_tuoyaLhtuA0C({ target: __sveltets_2_any(), props: { children:() => { return __sveltets_2_any(0); },title,description,}});
  ;__sveltets_2_ensureSnippet(children?.());

   { const $$_retsaoT1C = __sveltets_2_ensureComponent(Toaster); new $$_retsaoT1C({ target: __sveltets_2_any(), props: {    "position":`top-right`,"toastOptions":{ duration: 4000 },}});}
 AuthLayout}
};
return { props: {} as any as $$ComponentProps, exports: {}, bindings: __sveltets_$$bindings(''), slots: {}, events: {} }}
const AuthLayout__SvelteComponent_ = __sveltets_2_fn_component($$render());
/*Ωignore_startΩ*/type AuthLayout__SvelteComponent_ = ReturnType<typeof AuthLayout__SvelteComponent_>;
/*Ωignore_endΩ*/export default AuthLayout__SvelteComponent_;