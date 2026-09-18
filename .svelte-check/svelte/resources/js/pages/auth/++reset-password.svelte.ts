///<reference types="svelte" />
export const layout = {
  title: 'Atur ulang kata sandi',
  description: 'Silakan masukkan kata sandi baru Anda di bawah ini',
};

import { Form } from '@inertiajs/svelte';
import AppHead from '@/components/app-head.svelte';
import InputError from '@/components/input-error.svelte';
import { Input } from '@/components/ui/input';
import { update } from '@/routes/password';

type $$ComponentProps = {
  token: string;
  email: string;
  passwordRules: string;
};
function $$render() {
  let {
    token,
    email,
    passwordRules,
  }: /*Ωignore_startΩ*/ $$ComponentProps /*Ωignore_endΩ*/ = $props();
  async () => {
    {
      const $$_daeHppA0C = __sveltets_2_ensureComponent(AppHead);
      new $$_daeHppA0C({
        target: __sveltets_2_any(),
        props: { title: `Atur ulang kata sandi` },
      });
    }

    {
      const $$_mroF0C = __sveltets_2_ensureComponent(Form);
      const $$_mroF0 = new $$_mroF0C({
        target: __sveltets_2_any(),
        props: {
          ...update.form(),
          transform: (data) => ({ ...data, token, email }),
          resetOnSuccess: ['password', 'password_confirmation'],
          children: ({ errors, processing }) => {
            async () /*Ωignore_positionΩ*/ => {
              {
                svelteHTML.createElement('div', {
                  class: `d-flex flex-column gap-4`,
                });
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
                    const $$_tupnI2C = __sveltets_2_ensureComponent(Input);
                    new $$_tupnI2C({
                      target: __sveltets_2_any(),
                      props: {
                        id: `email`,
                        type: `email`,
                        name: `email`,
                        autocomplete: `email`,
                        value: email,
                        class: `mt-1 d-block w-100`,
                        readonly: true,
                      },
                    });
                  }
                  {
                    const $$_rorrEtupnI2C =
                      __sveltets_2_ensureComponent(InputError);
                    new $$_rorrEtupnI2C({
                      target: __sveltets_2_any(),
                      props: { message: errors.email, class: `mt-2` },
                    });
                  }
                }

                {
                  svelteHTML.createElement('div', {
                    class: `d-flex flex-column gap-2`,
                  });
                  {
                    svelteHTML.createElement('label', {
                      class: `form-label`,
                      for: `password`,
                    });
                  }
                  {
                    const $$_drowssaP_tupnI2C = __sveltets_2_ensureComponent(
                      Input.Password,
                    );
                    new $$_drowssaP_tupnI2C({
                      target: __sveltets_2_any(),
                      props: {
                        id: `password`,
                        name: `password`,
                        autocomplete: `new-password`,
                        class: `mt-1 d-block w-100`,
                        placeholder: `Kata sandi`,
                        passwordrules: passwordRules,
                      },
                    });
                  }
                  {
                    const $$_rorrEtupnI2C =
                      __sveltets_2_ensureComponent(InputError);
                    new $$_rorrEtupnI2C({
                      target: __sveltets_2_any(),
                      props: { message: errors.password },
                    });
                  }
                }

                {
                  svelteHTML.createElement('div', {
                    class: `d-flex flex-column gap-2`,
                  });
                  {
                    svelteHTML.createElement('label', {
                      class: `form-label`,
                      for: `password_confirmation`,
                    });
                  }
                  {
                    const $$_drowssaP_tupnI2C = __sveltets_2_ensureComponent(
                      Input.Password,
                    );
                    new $$_drowssaP_tupnI2C({
                      target: __sveltets_2_any(),
                      props: {
                        id: `password_confirmation`,
                        name: `password_confirmation`,
                        autocomplete: `new-password`,
                        class: `mt-1 d-block w-100`,
                        placeholder: `Konfirmasi kata sandi`,
                        passwordrules: passwordRules,
                      },
                    });
                  }
                  {
                    const $$_rorrEtupnI2C =
                      __sveltets_2_ensureComponent(InputError);
                    new $$_rorrEtupnI2C({
                      target: __sveltets_2_any(),
                      props: { message: errors.password_confirmation },
                    });
                  }
                }

                {
                  svelteHTML.createElement('button', {
                    type: `submit`,
                    class: `btn btn-primary mt-3 w-100`,
                    disabled: processing,
                    ...__sveltets_2_empty({
                      'data-test': `reset-password-button`,
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
        $$_mroF0.$$prop_def; /*Ωignore_endΩ*/

      Form;
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
const ResetPassword__SvelteComponent_ = __sveltets_2_fn_component($$render());
/*Ωignore_startΩ*/ type ResetPassword__SvelteComponent_ = ReturnType<
  typeof ResetPassword__SvelteComponent_
>;
/*Ωignore_endΩ*/ export default ResetPassword__SvelteComponent_;
