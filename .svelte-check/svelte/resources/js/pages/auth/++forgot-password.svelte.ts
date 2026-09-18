///<reference types="svelte" />
export const layout = {
  title: 'Lupa kata sandi',
  description:
    'Masukkan email Anda untuk menerima tautan atur ulang kata sandi',
};

import { Form } from '@inertiajs/svelte';
import AppHead from '@/components/app-head.svelte';
import InputError from '@/components/input-error.svelte';
import TextLink from '@/components/text-link.svelte';
import { Input } from '@/components/ui/input';
import { login } from '@/routes';
import { email } from '@/routes/password';

type $$ComponentProps = {
  status?: string;
};
function $$render() {
  let { status = '' }: /*Ωignore_startΩ*/ $$ComponentProps /*Ωignore_endΩ*/ =
    $props();
  async () => {
    {
      const $$_daeHppA0C = __sveltets_2_ensureComponent(AppHead);
      new $$_daeHppA0C({
        target: __sveltets_2_any(),
        props: { title: `Lupa kata sandi` },
      });
    }

    if (status) {
      {
        svelteHTML.createElement('div', {
          class: `mb-4 text-center small fw-medium text-success`,
        });
        status;
      }
    }

    {
      svelteHTML.createElement('div', { class: `d-flex flex-column gap-4` });
      {
        const $$_mroF1C = __sveltets_2_ensureComponent(Form);
        const $$_mroF1 = new $$_mroF1C({
          target: __sveltets_2_any(),
          props: {
            ...email.form(),
            children: ({ errors, processing }) => {
              async () /*Ωignore_positionΩ*/ => {
                {
                  svelteHTML.createElement('div', {
                    class: `d-flex flex-column gap-2`,
                  });
                  {
                    svelteHTML.createElement('label', {
                      class: `form-label`,
                      for: `email`,
                    });
                  }
                  {
                    const $$_tupnI1C = __sveltets_2_ensureComponent(Input);
                    new $$_tupnI1C({
                      target: __sveltets_2_any(),
                      props: {
                        id: `email`,
                        type: `email`,
                        name: `email`,
                        autocomplete: `off`,
                        placeholder: `email@contoh.com`,
                      },
                    });
                  }
                  {
                    const $$_rorrEtupnI1C =
                      __sveltets_2_ensureComponent(InputError);
                    new $$_rorrEtupnI1C({
                      target: __sveltets_2_any(),
                      props: { message: errors.email },
                    });
                  }
                }

                {
                  svelteHTML.createElement('div', {
                    class: `mt-4 d-flex align-items-center justify-content-start`,
                  });
                  {
                    svelteHTML.createElement('button', {
                      type: `submit`,
                      class: `btn btn-primary w-100`,
                      disabled: processing,
                      ...__sveltets_2_empty({
                        'data-test': `email-password-reset-link-button`,
                      }),
                    });
                  }
                }
              };
              return __sveltets_2_any(0);
            },
          },
        });
        /*Ωignore_startΩ*/ const { children } =
          $$_mroF1.$$prop_def; /*Ωignore_endΩ*/

        Form;
      }

      {
        svelteHTML.createElement('div', {
          class: `text-center small text-muted`,
        });
        {
          svelteHTML.createElement('span', {});
        }
        {
          const $$_kniLtxeT2C = __sveltets_2_ensureComponent(TextLink);
          new $$_kniLtxeT2C({
            target: __sveltets_2_any(),
            props: {
              children: () => {
                return __sveltets_2_any(0);
              },
              href: login(),
            },
          });
          TextLink;
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
const ForgotPassword__SvelteComponent_ = __sveltets_2_fn_component($$render());
/*Ωignore_startΩ*/ type ForgotPassword__SvelteComponent_ = ReturnType<
  typeof ForgotPassword__SvelteComponent_
>;
/*Ωignore_endΩ*/ export default ForgotPassword__SvelteComponent_;
