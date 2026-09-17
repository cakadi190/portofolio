import InputRoot from './regular.svelte';
import InputPassword from './password.svelte';
import InputCheck from './check.svelte';
import InputRadio from './radio.svelte';

const Input = Object.assign(InputRoot, {
  Password: InputPassword,
  Check: InputCheck,
  Radio: InputRadio,
});

export { Input };
