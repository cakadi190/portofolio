///<reference types="svelte" />
import type { HTMLInputAttributes } from 'svelte/elements';
import { cn } from '@/lib/utils';
function $$render() {
  let {
    class: className = '',
    type = 'text',
    value = $bindable(),
    ...rest
  }: HTMLInputAttributes = $props(); /*Ωignore_startΩ*/
  value; /*Ωignore_endΩ*/
  async () => {
    {
      svelteHTML.createElement('input', {
        type,
        'bind:value': value,
        class: cn('form-control', className),
        ...rest,
      });
      /*Ωignore_startΩ*/ () =>
        (value = __sveltets_2_any(null)); /*Ωignore_endΩ*/
    }
  };
  return {
    props: {} as any as HTMLInputAttributes,
    exports: {},
    bindings: __sveltets_$$bindings('value'),
    slots: {},
    events: {},
  };
}
const Input__SvelteComponent_ = __sveltets_2_fn_component($$render());
/*Ωignore_startΩ*/ type Input__SvelteComponent_ = ReturnType<
  typeof Input__SvelteComponent_
>;
/*Ωignore_endΩ*/ export default Input__SvelteComponent_;
