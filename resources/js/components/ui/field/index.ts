import FieldGroup from './group.svelte';
import FieldLabel from './label.svelte';
import { Input } from '../input';

const Field = Object.assign(FieldGroup, {
  Group: FieldGroup,
  Label: FieldLabel,
  Input,
});

export { Field };
