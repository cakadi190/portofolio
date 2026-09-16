///<reference types="svelte" />
;
  export const layout = {
    title: 'Buat akun',
    description: 'Masukkan detail Anda di bawah untuk membuat akun',
  };
;;

import { Form } from '@inertiajs/svelte';
import AppHead from '@/components/app-head.svelte';
import InputError from '@/components/input-error.svelte';
import TextLink from '@/components/text-link.svelte';
import { Input } from '@/components/ui/input';
import { login } from '@/routes';
import { store } from '@/routes/register';

;type $$ComponentProps =  { passwordRules: string };function $$render() {

  
  
  
  
  
  
  

  let { passwordRules }:/*Ωignore_startΩ*/$$ComponentProps/*Ωignore_endΩ*/ = $props();
;
async () => {



 { const $$_daeHppA0C = __sveltets_2_ensureComponent(AppHead); new $$_daeHppA0C({ target: __sveltets_2_any(), props: {  "title":`Daftar`,}});}

  { const $$_mroF0C = __sveltets_2_ensureComponent(Form); const $$_mroF0 = new $$_mroF0C({ target: __sveltets_2_any(), props: {     ...store.form(),"resetOnSuccess":['password', 'password_confirmation'],"class":`d-flex flex-column gap-4`,children:({ errors, processing }) => { async ()/*Ωignore_positionΩ*/ => {
     { svelteHTML.createElement("div", { "class":`d-flex flex-column gap-4`,});
       { svelteHTML.createElement("div", { "class":`d-flex flex-column gap-2`,});
         { svelteHTML.createElement("label", {   "class":`form-label`,"for":`name`,});  }
         { const $$_tupnI2C = __sveltets_2_ensureComponent(Input); new $$_tupnI2C({ target: __sveltets_2_any(), props: {            "id":`name`,"type":`text`,"required":true,"autocomplete":`name`,"name":`name`,"placeholder":`Nama lengkap`,}});}
         { const $$_rorrEtupnI2C = __sveltets_2_ensureComponent(InputError); new $$_rorrEtupnI2C({ target: __sveltets_2_any(), props: {  "message":errors.name,}});}
       }

       { svelteHTML.createElement("div", { "class":`d-flex flex-column gap-2`,});
         { svelteHTML.createElement("label", {   "class":`form-label`,"for":`email`,});  }
         { const $$_tupnI2C = __sveltets_2_ensureComponent(Input); new $$_tupnI2C({ target: __sveltets_2_any(), props: {            "id":`email`,"type":`email`,"required":true,"autocomplete":`email`,"name":`email`,"placeholder":`email@contoh.com`,}});}
         { const $$_rorrEtupnI2C = __sveltets_2_ensureComponent(InputError); new $$_rorrEtupnI2C({ target: __sveltets_2_any(), props: {  "message":errors.email,}});}
       }

       { svelteHTML.createElement("div", { "class":`d-flex flex-column gap-2`,});
         { svelteHTML.createElement("label", {   "class":`form-label`,"for":`password`,});  }
         { const $$_drowssaP_tupnI2C = __sveltets_2_ensureComponent(Input.Password); new $$_drowssaP_tupnI2C({ target: __sveltets_2_any(), props: {            "id":`password`,"required":true,"autocomplete":`new-password`,"name":`password`,"placeholder":`Kata sandi`,"passwordrules":passwordRules,}});}
         { const $$_rorrEtupnI2C = __sveltets_2_ensureComponent(InputError); new $$_rorrEtupnI2C({ target: __sveltets_2_any(), props: {  "message":errors.password,}});}
       }

       { svelteHTML.createElement("div", { "class":`d-flex flex-column gap-2`,});
         { svelteHTML.createElement("label", {   "class":`form-label`,"for":`password_confirmation`,});   }
         { const $$_drowssaP_tupnI2C = __sveltets_2_ensureComponent(Input.Password); new $$_drowssaP_tupnI2C({ target: __sveltets_2_any(), props: {            "id":`password_confirmation`,"required":true,"autocomplete":`new-password`,"name":`password_confirmation`,"placeholder":`Konfirmasi kata sandi`,"passwordrules":passwordRules,}});}
         { const $$_rorrEtupnI2C = __sveltets_2_ensureComponent(InputError); new $$_rorrEtupnI2C({ target: __sveltets_2_any(), props: {  "message":errors.password_confirmation,}});}
       }

       { svelteHTML.createElement("button", {         "type":`submit`,"class":`btn btn-primary mt-2 w-100`,"disabled":processing,...__sveltets_2_empty({"data-test":`register-user-button`}),});
         
       }
     }

     { svelteHTML.createElement("div", { "class":`text-center small text-muted`,});
        
       { const $$_kniLtxeT1C = __sveltets_2_ensureComponent(TextLink); new $$_kniLtxeT1C({ target: __sveltets_2_any(), props: {   children:() => { return __sveltets_2_any(0); },"href":login(),"class":`text-decoration-underline`,}});
        
       TextLink}
     }
  };return __sveltets_2_any(0)},}});/*Ωignore_startΩ*/const {children} = $$_mroF0.$$prop_def;/*Ωignore_endΩ*/
  
 Form}
};
return { props: {} as any as $$ComponentProps, exports: {}, bindings: __sveltets_$$bindings(''), slots: {}, events: {} }}
const Register__SvelteComponent_ = __sveltets_2_fn_component($$render());
/*Ωignore_startΩ*/type Register__SvelteComponent_ = ReturnType<typeof Register__SvelteComponent_>;
/*Ωignore_endΩ*/export default Register__SvelteComponent_;