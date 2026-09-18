///<reference types="svelte" />
import AppHead from '@/components/app-head.svelte';
function $$render() {
  async () => {
    {
      const $$_daeHppA0C = __sveltets_2_ensureComponent(AppHead);
      new $$_daeHppA0C({
        target: __sveltets_2_any(),
        props: { title: `Dasbor` },
      });
    }

    {
      svelteHTML.createElement('div', { class: `container py-5` });
      {
        svelteHTML.createElement('h1', {});
      }
      {
        svelteHTML.createElement('p', { class: `text-muted` });
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
const Dashboard__SvelteComponent_ = __sveltets_2_isomorphic_component(
  __sveltets_2_with_any_event($$render()),
);
/*Ωignore_startΩ*/ type Dashboard__SvelteComponent_ = InstanceType<
  typeof Dashboard__SvelteComponent_
>;
/*Ωignore_endΩ*/ export default Dashboard__SvelteComponent_;
