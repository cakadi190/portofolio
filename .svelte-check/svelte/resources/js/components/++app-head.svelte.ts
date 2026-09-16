///<reference types="svelte" />
;
import type { Snippet } from 'svelte';

;type $$ComponentProps =  {
    title?: string;
    children?: Snippet;
  };function $$render() {

  

  let {
    title = '',
    children,
  }:/*Ωignore_startΩ*/$$ComponentProps/*Ωignore_endΩ*/ = $props();

  const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
  const fullTitle = $derived(title ? `${title} - ${appName}` : appName);
;
async () => {

 { svelteHTML.createElement("svelte:head", {});
   { svelteHTML.createElement("title", {});fullTitle; }
  ;__sveltets_2_ensureSnippet(children?.());
 }
};
return { props: {} as any as $$ComponentProps, exports: {}, bindings: __sveltets_$$bindings(''), slots: {}, events: {} }}
const AppHead__SvelteComponent_ = __sveltets_2_fn_component($$render());
/*Ωignore_startΩ*/type AppHead__SvelteComponent_ = ReturnType<typeof AppHead__SvelteComponent_>;
/*Ωignore_endΩ*/export default AppHead__SvelteComponent_;