///<reference types="svelte" />
type $$ComponentProps = {
  message?: string;
  class?: string;
};
function $$render() {
  let {
    message = '',
    class: className = '',
  }: /*Ωignore_startΩ*/ $$ComponentProps /*Ωignore_endΩ*/ = $props();
  async () => {
    if (message) {
      {
        svelteHTML.createElement('div', { class: className });
        {
          svelteHTML.createElement('p', { class: `small text-danger mb-0` });
          message;
        }
      }
    }
  };
  return {
    props: {} as any as $$ComponentProps,
    exports: {},
    bindings: __sveltets_$$bindings(''),
    slots: {},
    events: {},
  };
}
const InputError__SvelteComponent_ = __sveltets_2_fn_component($$render());
/*Ωignore_startΩ*/ type InputError__SvelteComponent_ = ReturnType<
  typeof InputError__SvelteComponent_
>;
/*Ωignore_endΩ*/ export default InputError__SvelteComponent_;
