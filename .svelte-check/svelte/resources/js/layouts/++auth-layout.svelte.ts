///<reference types="svelte" />
import type { Snippet } from 'svelte';
import AuthLayout from '@/layouts/auth/auth-simple-layout.svelte';
import { FlatToast, ToastContainer } from 'svelte-toasts';

type $$ComponentProps = {
  title?: string;
  description?: string;
  children?: Snippet;
};
function $$render() {
  let {
    title = '',
    description = '',
    children,
  }: /*Ωignore_startΩ*/ $$ComponentProps /*Ωignore_endΩ*/ = $props();
  async () => {
    {
      const $$_tuoyaLhtuA0C = __sveltets_2_ensureComponent(AuthLayout);
      new $$_tuoyaLhtuA0C({
        target: __sveltets_2_any(),
        props: {
          children: () => {
            return __sveltets_2_any(0);
          },
          title,
          description,
        },
      });
      __sveltets_2_ensureSnippet(children?.());

      {
        const $$_reniatnoCtsaoT1C =
          __sveltets_2_ensureComponent(ToastContainer);
        const $$_reniatnoCtsaoT1 = new $$_reniatnoCtsaoT1C({
          target: __sveltets_2_any(),
          props: {
            children: () => {
              return __sveltets_2_any(0);
            },
          },
        });
        {
          const { /*Ωignore_startΩ*/ $$_$$ /*Ωignore_endΩ*/, data } =
            $$_reniatnoCtsaoT1.$$slot_def.default;
          $$_$$;
          {
            const $$_tsaoTtalF2C = __sveltets_2_ensureComponent(FlatToast);
            new $$_tsaoTtalF2C({ target: __sveltets_2_any(), props: { data } });
          }
        }
        ToastContainer;
      }
      AuthLayout;
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
const AuthLayout__SvelteComponent_ = __sveltets_2_fn_component($$render());
/*Ωignore_startΩ*/ type AuthLayout__SvelteComponent_ = ReturnType<
  typeof AuthLayout__SvelteComponent_
>;
/*Ωignore_endΩ*/ export default AuthLayout__SvelteComponent_;
