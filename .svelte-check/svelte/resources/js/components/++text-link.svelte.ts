///<reference types="svelte" />
import type { LinkComponentBaseProps, Method } from '@inertiajs/core';
import { Link } from '@inertiajs/svelte';
import type { Snippet } from 'svelte';

type $$ComponentProps = {
  href: LinkComponentBaseProps['href'];
  tabindex?: number;
  method?: Method;
  as?: keyof HTMLElementTagNameMap;
  children?: Snippet;
  [key: string]: unknown;
};
function $$render() {
  let {
    href,
    tabindex,
    method,
    as,
    children,
    ...rest
  }: /*Ωignore_startΩ*/ $$ComponentProps /*Ωignore_endΩ*/ = $props();
  async () => {
    {
      const $$_kniL0C = __sveltets_2_ensureComponent(Link);
      new $$_kniL0C({
        target: __sveltets_2_any(),
        props: {
          children: () => {
            return __sveltets_2_any(0);
          },
          href,
          tabindex,
          method,
          as,
          class: `text-body text-decoration-underline`,
          ...rest,
        },
      });
      __sveltets_2_ensureSnippet(children?.());
      Link;
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
const TextLink__SvelteComponent_ = __sveltets_2_fn_component($$render());
/*Ωignore_startΩ*/ type TextLink__SvelteComponent_ = ReturnType<
  typeof TextLink__SvelteComponent_
>;
/*Ωignore_endΩ*/ export default TextLink__SvelteComponent_;
