import InputRoot from './regular.svelte';
import InputPassword from './password.svelte';

const Input = Object.assign(InputRoot, {
  Password: InputPassword,
});

export { Input };
