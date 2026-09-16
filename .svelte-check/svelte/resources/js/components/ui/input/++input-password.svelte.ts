///<reference types="svelte" />
;
import Eye from '@lucide/svelte/icons/eye';
import EyeOff from '@lucide/svelte/icons/eye-off';
import Input from './input.svelte';
import { cn } from '@/lib/utils';
function $$render() {

  
  
  
  /*Ωignore_startΩ*/;type $$ComponentProps = { class?: string } & Record<string, any>;/*Ωignore_endΩ*/

  let { class: className = '', ...rest }: $$ComponentProps = $props();

  let showPassword = $state(false);
;
async () => {

 { svelteHTML.createElement("div", { "class":`position-relative`,});
   { const $$_tupnI1C = __sveltets_2_ensureComponent(Input); new $$_tupnI1C({ target: __sveltets_2_any(), props: {      "type":showPassword ? 'text' : 'password',"class":cn('pe-5', className),...rest,}});}
   { svelteHTML.createElement("button", {           "type":`button`,"onclick":() => (showPassword = !showPassword),"class":`text-muted position-absolute top-0 bottom-0 end-0 d-flex align-items-center rounded-end px-3 border-0 bg-transparent`,"aria-label":showPassword ? 'Hide password' : 'Show password',"tabindex":-1,});
    if(showPassword){
       { const $$_ffOeyE2C = __sveltets_2_ensureComponent(EyeOff); new $$_ffOeyE2C({ target: __sveltets_2_any(), props: {  "class":`password-toggle-icon`,}});}
    }else{
       { const $$_eyE2C = __sveltets_2_ensureComponent(Eye); new $$_eyE2C({ target: __sveltets_2_any(), props: {  "class":`password-toggle-icon`,}});}
    }
   }
 }


};
return { props: {} as any as $$ComponentProps, exports: {}, bindings: __sveltets_$$bindings(''), slots: {}, events: {} }}
const InputPassword__SvelteComponent_ = __sveltets_2_fn_component($$render());
/*Ωignore_startΩ*/type InputPassword__SvelteComponent_ = ReturnType<typeof InputPassword__SvelteComponent_>;
/*Ωignore_endΩ*/export default InputPassword__SvelteComponent_;