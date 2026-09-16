///<reference types="svelte" />
;
  export const layout = {
    title: 'Masuk ke akun Anda',
    description: 'Masukkan email dan kata sandi Anda di bawah untuk masuk',
  };
;;

import { Form } from '@inertiajs/svelte';
import AppHead from '@/components/app-head.svelte';
import InputError from '@/components/input-error.svelte';
import TextLink from '@/components/text-link.svelte';
import { Input } from '@/components/ui/input';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

;type $$ComponentProps =  {
    status?: string;
    canResetPassword: boolean;
  };function $$render() {

  
  
  
  
  
  
  
  

  let {
    status = '',
    canResetPassword,
  }:/*Ωignore_startΩ*/$$ComponentProps/*Ωignore_endΩ*/ = $props();
;
async () => {



 { const $$_daeHppA0C = __sveltets_2_ensureComponent(AppHead); new $$_daeHppA0C({ target: __sveltets_2_any(), props: {  "title":`Masuk`,}});}

if(status){
   { svelteHTML.createElement("div", { "class":`mb-4 text-center small fw-medium text-success`,});
    status;
   }
}

  { const $$_mroF0C = __sveltets_2_ensureComponent(Form); const $$_mroF0 = new $$_mroF0C({ target: __sveltets_2_any(), props: {     ...store.form(),"resetOnSuccess":['password'],"class":`d-flex flex-column gap-3`,children:({ errors, processing }) => { async ()/*Ωignore_positionΩ*/ => {
     { svelteHTML.createElement("div", { "class":`d-flex flex-column gap-3`,});
       { svelteHTML.createElement("div", { "class":`d-flex flex-column gap-2`,});
         { svelteHTML.createElement("label", {   "class":`form-label`,"for":`email`,});  }
         { const $$_tupnI2C = __sveltets_2_ensureComponent(Input); new $$_tupnI2C({ target: __sveltets_2_any(), props: {            "id":`email`,"type":`email`,"name":`email`,"required":true,"autocomplete":`email`,"placeholder":`email@contoh.com`,}});}
         { const $$_rorrEtupnI2C = __sveltets_2_ensureComponent(InputError); new $$_rorrEtupnI2C({ target: __sveltets_2_any(), props: {  "message":errors.email,}});}
       }

       { svelteHTML.createElement("div", { "class":`d-flex flex-column gap-2`,});
         { svelteHTML.createElement("div", { "class":`d-flex align-items-center justify-content-between`,});
           { svelteHTML.createElement("label", {   "class":`form-label`,"for":`password`,});  }
          if(canResetPassword){
             { const $$_kniLtxeT3C = __sveltets_2_ensureComponent(TextLink); new $$_kniLtxeT3C({ target: __sveltets_2_any(), props: {   children:() => { return __sveltets_2_any(0); },"href":request(),"class":`small`,}});
                 
             TextLink}
          }
         }
         { const $$_drowssaP_tupnI2C = __sveltets_2_ensureComponent(Input.Password); new $$_drowssaP_tupnI2C({ target: __sveltets_2_any(), props: {          "id":`password`,"name":`password`,"required":true,"autocomplete":`current-password`,"placeholder":`Kata sandi`,}});}
         { const $$_rorrEtupnI2C = __sveltets_2_ensureComponent(InputError); new $$_rorrEtupnI2C({ target: __sveltets_2_any(), props: {  "message":errors.password,}});}
       }

       { svelteHTML.createElement("div", { "class":`form-check`,});
         { svelteHTML.createElement("input", {         "class":`form-check-input`,"type":`checkbox`,"id":`remember`,"name":`remember`,});}
         { svelteHTML.createElement("label", {   "class":`form-check-label`,"for":`remember`,});    }
       }

       { svelteHTML.createElement("button", {         "type":`submit`,"class":`btn btn-primary w-100`,"disabled":processing,...__sveltets_2_empty({"data-test":`login-button`}),});
        
       }
     }

     { svelteHTML.createElement("div", { "class":`text-center small text-muted`,});
        
       { const $$_kniLtxeT1C = __sveltets_2_ensureComponent(TextLink); new $$_kniLtxeT1C({ target: __sveltets_2_any(), props: { children:() => { return __sveltets_2_any(0); },"href":register(),}});  TextLink}
     }
  };return __sveltets_2_any(0)},}});/*Ωignore_startΩ*/const {children} = $$_mroF0.$$prop_def;/*Ωignore_endΩ*/
  
 Form}
};
return { props: {} as any as $$ComponentProps, exports: {}, bindings: __sveltets_$$bindings(''), slots: {}, events: {} }}
const Login__SvelteComponent_ = __sveltets_2_fn_component($$render());
/*Ωignore_startΩ*/type Login__SvelteComponent_ = ReturnType<typeof Login__SvelteComponent_>;
/*Ωignore_endΩ*/export default Login__SvelteComponent_;