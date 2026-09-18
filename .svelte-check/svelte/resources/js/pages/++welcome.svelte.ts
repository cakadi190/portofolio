///<reference types="svelte" />
import AppHead from '@/components/app-head.svelte';
function $$render() {
  async () => {
    {
      const $$_daeHppA0C = __sveltets_2_ensureComponent(AppHead);
      new $$_daeHppA0C({
        target: __sveltets_2_any(),
        props: { title: `Selamat Datang` },
      });
    }

    {
      svelteHTML.createElement('div', { class: `container py-5` });
      {
        svelteHTML.createElement('h1', {});
      }

      {
        svelteHTML.createElement('div', { class: `dropdown` });
        {
          svelteHTML.createElement('button', {
            class: `btn btn-secondary dropdown-toggle`,
            type: `button`,
            ...__sveltets_2_empty({ 'data-bs-toggle': `dropdown` }),
            'aria-expanded': `false`,
          });
        }
        {
          svelteHTML.createElement('ul', { class: `dropdown-menu` });
          {
            svelteHTML.createElement('li', {});
            {
              svelteHTML.createElement('a', {
                class: `dropdown-item`,
                href: `#`,
              });
            }
          }
          {
            svelteHTML.createElement('li', {});
            {
              svelteHTML.createElement('a', {
                class: `dropdown-item`,
                href: `#`,
              });
            }
          }
          {
            svelteHTML.createElement('li', {});
            {
              svelteHTML.createElement('a', {
                class: `dropdown-item`,
                href: `#`,
              });
            }
          }
        }
      }
    }
  };
  return {
    props: {} as Record<string, never>,
    exports: {},
    bindings: '',
    slots: {},
    events: {},
  };
}
const Welcome__SvelteComponent_ = __sveltets_2_isomorphic_component(
  __sveltets_2_with_any_event($$render()),
);
/*Ωignore_startΩ*/ type Welcome__SvelteComponent_ = InstanceType<
  typeof Welcome__SvelteComponent_
>;
/*Ωignore_endΩ*/ export default Welcome__SvelteComponent_;
